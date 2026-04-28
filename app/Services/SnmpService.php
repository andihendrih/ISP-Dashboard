<?php

namespace App\Services;

use App\Models\DeviceMikrotik;
use App\Models\SnmpLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * SNMP poller for Mikrotik routers (and any RFC1213-compliant device).
 *
 * Uses PHP's built-in SNMP class. Polls IF-MIB counters for each interface
 * and persists results to snmp_logs. Computes bps deltas using the previous
 * sample for the same (device, if_index).
 */
class SnmpService
{
    private const OID_IF_INDEX     = '.1.3.6.1.2.1.2.2.1.1';
    private const OID_IF_DESCR     = '.1.3.6.1.2.1.2.2.1.2';
    private const OID_IF_OPER      = '.1.3.6.1.2.1.2.2.1.8';
    private const OID_IF_HC_IN     = '.1.3.6.1.2.1.31.1.1.1.6';   // ifHCInOctets
    private const OID_IF_HC_OUT    = '.1.3.6.1.2.1.31.1.1.1.10';  // ifHCOutOctets

    /** Poll a Mikrotik device, persist all interfaces' counters. */
    public function pollDevice(DeviceMikrotik $device): array
    {
        $community = $device->snmp_community ?: config('ahnet.snmp.community');
        $version   = (string) config('ahnet.snmp.version', '2c');
        $timeout   = (int) config('ahnet.snmp.timeout', 1_000_000);
        $retries   = (int) config('ahnet.snmp.retries', 2);
        $host      = $device->host;

        $snmp = $this->openSession($host, $community, $version, $timeout, $retries);

        try {
            $indexes = $this->walk($snmp, self::OID_IF_INDEX);
            $names   = $this->walk($snmp, self::OID_IF_DESCR);
            $opers   = $this->walk($snmp, self::OID_IF_OPER);
            $ins     = $this->walk($snmp, self::OID_IF_HC_IN);
            $outs    = $this->walk($snmp, self::OID_IF_HC_OUT);
        } finally {
            $snmp->close();
        }

        $now = Carbon::now();
        $results = [];

        foreach ($indexes as $key => $idx) {
            $idx = (int) $this->cleanValue($idx);
            $name = $this->cleanValue($names[$key] ?? "if{$idx}");
            $oper = (int) $this->cleanValue($opers[$key] ?? '2');
            $in   = (int) $this->cleanValue($ins[$key] ?? '0');
            $out  = (int) $this->cleanValue($outs[$key] ?? '0');

            // Previous sample for delta calc
            $prev = SnmpLog::where('device_id', $device->id)
                ->where('if_index', $idx)
                ->orderByDesc('polled_at')
                ->first();

            $inBps = 0;
            $outBps = 0;
            if ($prev) {
                $deltaT = max($now->diffInSeconds($prev->polled_at, true), 1);
                $deltaIn  = max($in  - (int) $prev->in_octets, 0);
                $deltaOut = max($out - (int) $prev->out_octets, 0);
                $inBps  = (int) round(($deltaIn  * 8) / $deltaT);
                $outBps = (int) round(($deltaOut * 8) / $deltaT);
            }

            $log = SnmpLog::create([
                'device_id'   => $device->id,
                'if_index'    => $idx,
                'if_name'     => $name,
                'in_octets'   => $in,
                'out_octets'  => $out,
                'in_bps'      => $inBps,
                'out_bps'     => $outBps,
                'oper_status' => $oper === 1,
                'polled_at'   => $now,
            ]);
            $results[] = $log;
        }

        $device->last_seen_at = $now;
        $device->save();

        return $results;
    }

    /** Recent history of an interface. */
    public function history(DeviceMikrotik $device, int $ifIndex, int $minutes = 60)
    {
        return SnmpLog::where('device_id', $device->id)
            ->where('if_index', $ifIndex)
            ->where('polled_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderBy('polled_at')
            ->get();
    }

    /** Latest one row per interface. */
    public function latestPerInterface(DeviceMikrotik $device)
    {
        $sub = SnmpLog::selectRaw('MAX(id) as max_id')
            ->where('device_id', $device->id)
            ->groupBy('if_index');

        return SnmpLog::whereIn('id', $sub->pluck('max_id'))
            ->orderBy('if_index')
            ->get();
    }

    /* ----------------------------------------------------------------- */

    private function openSession(string $host, string $community, string $version, int $timeout, int $retries): \SNMP
    {
        $v = match ($version) {
            '1'    => \SNMP::VERSION_1,
            '2c', '2' => \SNMP::VERSION_2C,
            '3'    => \SNMP::VERSION_3,
            default => \SNMP::VERSION_2C,
        };
        $session = new \SNMP($v, $host, $community, $timeout, $retries);
        $session->valueretrieval = SNMP_VALUE_PLAIN;
        $session->oid_output_format = SNMP_OID_OUTPUT_NUMERIC;
        $session->exceptions_enabled = \SNMP::ERRNO_ANY;
        return $session;
    }

    private function walk(\SNMP $session, string $oid): array
    {
        try {
            $res = $session->walk($oid, true);
            return is_array($res) ? $res : [];
        } catch (\Throwable $e) {
            Log::warning("SNMP walk failed for {$oid}: " . $e->getMessage());
            return [];
        }
    }

    private function cleanValue(string $raw): string
    {
        // SNMP returns values like `STRING: "ether1"` when valueretrieval !=
        // PLAIN. With PLAIN it's bare. Strip quotes just in case.
        return trim($raw, " \t\n\r\0\x0B\"");
    }
}
