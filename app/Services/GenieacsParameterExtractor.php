<?php

namespace App\Services;

/**
 * Parses GenieACS device "raw" JSON (TR-069 parameter tree) and pulls out the
 * fields we actually care about for ISP UI: PPPoE creds, WAN IP, WiFi
 * 2.4/5 GHz, optical RX power. Tries Huawei / ZTE / FiberHome paths in turn.
 *
 * Output structure:
 * [
 *   'pppoe'   => ['username','password','vlan','enable','service_type','path'],
 *   'wan_ip'  => ['external_ip','subnet_mask','gateway','vlan','service_type','path'],
 *   'wifi_24' => ['ssid','password','security','enable','path'],
 *   'wifi_5g' => ['ssid','password','security','enable','path'],
 *   'rx_power'      => -28.5,    // dBm or null
 *   'registered_at' => '...',
 * ]
 */
class GenieacsParameterExtractor
{
    /** @var array */
    private array $raw;

    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    public static function from(array $raw): self
    {
        return new self($raw);
    }

    public function all(): array
    {
        return [
            'pppoe'         => $this->extractPppoe(),
            'wan_ip'        => $this->extractWanIp(),
            'wifi_24'       => $this->extractWifi('2.4'),
            'wifi_5g'       => $this->extractWifi('5'),
            'rx_power'      => $this->extractRxPower(),
            'registered_at' => $this->pluck($this->raw, '_registered'),
            'last_inform'   => $this->pluck($this->raw, '_lastInform'),
            'tags'          => $this->raw['_tags'] ?? [],
        ];
    }

    /* ----------------------------------------------------------------- */
    /* PPPoE                                                              */
    /* ----------------------------------------------------------------- */

    private function extractPppoe(): array
    {
        $prefix = $this->detectPrefix();
        $wanDev = $this->getWanDeviceIndexes($prefix);

        foreach ($wanDev as $wIdx) {
            $cdPath = "{$prefix}.WANDevice.{$wIdx}.WANConnectionDevice";
            $cdIdxs = $this->getChildIndexes($cdPath);

            foreach ($cdIdxs as $cIdx) {
                $pppPath = "{$cdPath}.{$cIdx}.WANPPPConnection";
                $pppIdxs = $this->getChildIndexes($pppPath);

                foreach ($pppIdxs as $pIdx) {
                    $base = "{$pppPath}.{$pIdx}";
                    $user = $this->pluck($this->raw, "{$base}.Username");
                    if ($user === null || $user === '') continue;

                    return [
                        'username'     => $user,
                        'password'     => $this->pluck($this->raw, "{$base}.Password"),
                        'vlan'         => $this->extractVlan("{$cdPath}.{$cIdx}", $base),
                        'enable'       => $this->boolish($this->pluck($this->raw, "{$base}.Enable")),
                        'service_type' => $this->extractServiceType($base),
                        'path'         => $base,
                    ];
                }
            }
        }
        return [];
    }

    /* ----------------------------------------------------------------- */
    /* WAN IP (Static / DHCP)                                             */
    /* ----------------------------------------------------------------- */

    private function extractWanIp(): array
    {
        $prefix = $this->detectPrefix();
        $wanDev = $this->getWanDeviceIndexes($prefix);

        foreach ($wanDev as $wIdx) {
            $cdPath = "{$prefix}.WANDevice.{$wIdx}.WANConnectionDevice";
            $cdIdxs = $this->getChildIndexes($cdPath);

            foreach ($cdIdxs as $cIdx) {
                $ipPath  = "{$cdPath}.{$cIdx}.WANIPConnection";
                $ipIdxs  = $this->getChildIndexes($ipPath);

                foreach ($ipIdxs as $iIdx) {
                    $base = "{$ipPath}.{$iIdx}";
                    $ext  = $this->pluck($this->raw, "{$base}.ExternalIPAddress");
                    if ($ext === null || $ext === '' || $ext === '0.0.0.0') continue;

                    return [
                        'external_ip'  => $ext,
                        'subnet_mask'  => $this->pluck($this->raw, "{$base}.SubnetMask"),
                        'gateway'      => $this->pluck($this->raw, "{$base}.DefaultGateway"),
                        'vlan'         => $this->extractVlan("{$cdPath}.{$cIdx}", $base),
                        'service_type' => $this->extractServiceType($base),
                        'path'         => $base,
                    ];
                }
            }
        }
        return [];
    }

    /* ----------------------------------------------------------------- */
    /* WiFi (2.4 / 5 GHz)                                                 */
    /* ----------------------------------------------------------------- */

    private function extractWifi(string $band): array
    {
        $prefix  = $this->detectPrefix();
        $lanIdxs = $this->getChildIndexes("{$prefix}.LANDevice");
        $bandKey = $band === '5' ? '5' : '2.4';

        foreach ($lanIdxs as $lIdx) {
            $wlanPath = "{$prefix}.LANDevice.{$lIdx}.WLANConfiguration";
            $wlanIdxs = $this->getChildIndexes($wlanPath);

            foreach ($wlanIdxs as $wIdx) {
                $base = "{$wlanPath}.{$wIdx}";

                // Heuristic to detect band
                $opFreq = $this->pluck($this->raw, "{$base}.X_HW_OperatingFrequencyBand")
                    ?? $this->pluck($this->raw, "{$base}.OperatingFrequencyBand")
                    ?? $this->pluck($this->raw, "{$base}.X_CT-COM_OperatingFrequencyBand")
                    ?? $this->pluck($this->raw, "{$base}.X_ZTE-COM_FrequencyBand")
                    ?? null;

                $stdLine = (string) ($this->pluck($this->raw, "{$base}.Standard") ?? '');
                $isFive  = str_contains((string) $opFreq, '5') || str_contains($stdLine, 'a') || str_contains($stdLine, 'ac') || str_contains($stdLine, 'ax');

                if ($band === '5' && !$isFive) continue;
                if ($band === '2.4' && $isFive) continue;

                $ssid = $this->pluck($this->raw, "{$base}.SSID");
                if ($ssid === null) continue;

                return [
                    'ssid'     => $ssid,
                    'password' => $this->pluck($this->raw, "{$base}.PreSharedKey.1.KeyPassphrase")
                                ?? $this->pluck($this->raw, "{$base}.KeyPassphrase")
                                ?? $this->pluck($this->raw, "{$base}.PreSharedKey.1.PreSharedKey"),
                    'security' => $this->pluck($this->raw, "{$base}.BeaconType") ?? 'Unknown',
                    'enable'   => $this->boolish($this->pluck($this->raw, "{$base}.Enable")),
                    'op_freq'  => $opFreq,
                    'path'     => $base,
                ];
            }
        }
        return [];
    }

    /* ----------------------------------------------------------------- */
    /* RX Power (Optical / GPON)                                          */
    /* ----------------------------------------------------------------- */

    private function extractRxPower(): ?float
    {
        $prefix = $this->detectPrefix();
        $candidates = [
            "{$prefix}.WANDevice.1.X_HW_GponInterfaceConfig.RXPower",
            "{$prefix}.WANDevice.1.X_GponInterfaceConfig.Stats.RXPower",
            "{$prefix}.WANDevice.1.X_CT-COM_GponInterfaceConfig.RXPower",
            "{$prefix}.WANDevice.1.X_ZTE-COM_GponLinkInfo.RXPower",
            "{$prefix}.WANDevice.1.X_GponInterfaceConfig.RXPower",
            "Device.Optical.Interface.1.LowerOpticalThreshold",
            "Device.X_GponInterfaceConfig.Stats.RXPower",
        ];

        foreach ($candidates as $path) {
            $val = $this->pluck($this->raw, $path);
            if ($val === null || $val === '') continue;
            $f = is_numeric($val) ? (float) $val : null;
            if ($f === null) continue;
            // GenieACS often stores as 0.1 dBm units (e.g. -2800 = -28.0 dBm)
            return abs($f) > 100 ? round($f / 100, 2) : round($f, 2);
        }
        return null;
    }

    /* ----------------------------------------------------------------- */
    /* helpers                                                            */
    /* ----------------------------------------------------------------- */

    private function detectPrefix(): string
    {
        if (isset($this->raw['InternetGatewayDevice'])) return 'InternetGatewayDevice';
        if (isset($this->raw['Device'])) return 'Device';
        return 'InternetGatewayDevice';
    }

    private function getWanDeviceIndexes(string $prefix): array
    {
        return $this->getChildIndexes("{$prefix}.WANDevice");
    }

    /** Return numeric child keys at the given dotted path. */
    private function getChildIndexes(string $path): array
    {
        $node = $this->getNode($path);
        if (!is_array($node)) return [];
        $keys = [];
        foreach (array_keys($node) as $k) {
            if (is_int($k) || ctype_digit((string) $k)) $keys[] = (int) $k;
        }
        sort($keys);
        return $keys;
    }

    private function getNode(string $path): mixed
    {
        $cursor = $this->raw;
        foreach (explode('.', $path) as $seg) {
            if (!is_array($cursor)) return null;
            $cursor = $cursor[$seg] ?? null;
            if ($cursor === null) return null;
        }
        return $cursor;
    }

    private function pluck(array $row, string $path): ?string
    {
        $cursor = $row;
        foreach (explode('.', $path) as $seg) {
            if (!is_array($cursor)) return null;
            $cursor = $cursor[$seg] ?? null;
            if ($cursor === null) return null;
        }
        if (is_array($cursor) && array_key_exists('_value', $cursor)) {
            $val = $cursor['_value'];
            return $val === null ? null : (string) $val;
        }
        return is_scalar($cursor) ? (string) $cursor : null;
    }

    private function extractVlan(string $cdBase, string $connBase): ?string
    {
        // Try various vendor-specific VLAN parameter paths
        $candidates = [
            "{$cdBase}.X_HW_VLAN",
            "{$cdBase}.X_HW_SERVICELIST",
            "{$connBase}.X_HW_VLAN",
            "{$connBase}.X_VLANIDMark",
            "{$connBase}.X_CT-COM_VLAN",
            "{$cdBase}.WANEthernetLinkConfig.X_VLAN_ID",
            "{$cdBase}.WANEthernetLinkConfig.X_VLAN",
        ];
        foreach ($candidates as $path) {
            $v = $this->pluck($this->raw, $path);
            if ($v !== null && $v !== '' && $v !== '0') return $v;
        }
        return null;
    }

    private function extractServiceType(string $base): ?string
    {
        $candidates = [
            "{$base}.X_HW_SERVICELIST",
            "{$base}.X_HW_ServiceList",
            "{$base}.Name",
            "{$base}.X_CT-COM_ServiceList",
        ];
        foreach ($candidates as $path) {
            $v = $this->pluck($this->raw, $path);
            if ($v !== null && $v !== '') return $v;
        }
        return null;
    }

    private function boolish(?string $v): bool
    {
        if ($v === null) return false;
        $v = strtolower(trim($v));
        return in_array($v, ['1', 'true', 'yes', 'on', 'enabled'], true);
    }
}
