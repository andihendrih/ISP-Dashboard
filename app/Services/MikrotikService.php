<?php

namespace App\Services;

use App\Models\DeviceMikrotik;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Exceptions\ConnectException;
use RouterOS\Exceptions\QueryException;
use RouterOS\Query;

/**
 * Wraps evilfreelancer/routeros-api-php for Mikrotik RouterOS integration.
 *
 * Connects on demand using either a DeviceMikrotik record or the default
 * config from .env. All public methods throw RuntimeException on connection
 * failure so callers can surface a clean error to the UI.
 */
class MikrotikService
{
    /** Build a connected RouterOS client for a given device or the default. */
    public function connect(?DeviceMikrotik $device = null): Client
    {
        if ($device) {
            $cfg = [
                'host'    => $device->host,
                'user'    => $device->username,
                'pass'    => $device->password,
                'port'    => (int) ($device->api_port ?: 8728),
                'ssl'     => (bool) $device->use_ssl,
                'timeout' => (int) config('ahnet.mikrotik.timeout', 5),
            ];
        } else {
            $cfg = [
                'host'    => config('ahnet.mikrotik.host'),
                'user'    => config('ahnet.mikrotik.user'),
                'pass'    => config('ahnet.mikrotik.password'),
                'port'    => (int) config('ahnet.mikrotik.port', 8728),
                'ssl'     => (bool) config('ahnet.mikrotik.ssl', false),
                'timeout' => (int) config('ahnet.mikrotik.timeout', 5),
            ];
        }

        try {
            return new Client(new Config($cfg));
        } catch (ConnectException|\Throwable $e) {
            throw new \RuntimeException(
                "Mikrotik connection failed ({$cfg['host']}:{$cfg['port']}): " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /** Fetch /system/identity + /system/resource for a device. */
    public function ping(?DeviceMikrotik $device = null): array
    {
        $client = $this->connect($device);
        $identity = $client->query('/system/identity/print')->read();
        $resource = $client->query('/system/resource/print')->read();
        return [
            'identity' => $identity[0] ?? [],
            'resource' => $resource[0] ?? [],
        ];
    }

    /** Sync a PPPoE secret to the router (creates or updates). */
    public function syncPppoeSecret(
        ?DeviceMikrotik $device,
        string $username,
        string $password,
        string $profile = 'default',
        ?string $remoteAddress = null,
        ?string $localAddress = null,
        string $service = 'pppoe'
    ): array {
        $client = $this->connect($device);

        // Find existing
        $existing = $client->query(
            (new Query('/ppp/secret/print'))->where('name', $username)
        )->read();

        $attrs = [
            'name'     => $username,
            'password' => $password,
            'profile'  => $profile,
            'service'  => $service,
        ];
        if ($remoteAddress) $attrs['remote-address'] = $remoteAddress;
        if ($localAddress)  $attrs['local-address']  = $localAddress;

        if (!empty($existing) && isset($existing[0]['.id'])) {
            $q = new Query('/ppp/secret/set');
            $q->equal('.id', $existing[0]['.id']);
            foreach ($attrs as $k => $v) $q->equal($k, $v);
            $client->query($q)->read();
            return ['action' => 'updated', 'id' => $existing[0]['.id']];
        }

        $q = new Query('/ppp/secret/add');
        foreach ($attrs as $k => $v) $q->equal($k, $v);
        $res = $client->query($q)->read();
        return ['action' => 'created', 'id' => $res['ret'] ?? null];
    }

    /** Disconnect an active PPPoE session by username. */
    public function disconnectPppoeUser(?DeviceMikrotik $device, string $username): bool
    {
        $client = $this->connect($device);
        $active = $client->query(
            (new Query('/ppp/active/print'))->where('name', $username)
        )->read();

        if (empty($active)) return false;

        foreach ($active as $row) {
            if (!isset($row['.id'])) continue;
            $client->query(
                (new Query('/ppp/active/remove'))->equal('.id', $row['.id'])
            )->read();
        }
        return true;
    }

    /** Active PPPoE sessions on the router. */
    public function activeSessions(?DeviceMikrotik $device = null): array
    {
        $client = $this->connect($device);
        return $client->query('/ppp/active/print')->read();
    }

    /** List PPP profiles available on the router. */
    public function listProfiles(?DeviceMikrotik $device = null): array
    {
        $client = $this->connect($device);
        return $client->query('/ppp/profile/print')->read();
    }

    /** Apply a profile (and optional rate-limit) to a PPPoE secret. */
    public function applyProfile(
        ?DeviceMikrotik $device,
        string $username,
        string $profile,
        ?string $rateLimit = null
    ): bool {
        $client = $this->connect($device);
        $existing = $client->query(
            (new Query('/ppp/secret/print'))->where('name', $username)
        )->read();
        if (empty($existing) || !isset($existing[0]['.id'])) return false;

        $q = new Query('/ppp/secret/set');
        $q->equal('.id', $existing[0]['.id']);
        $q->equal('profile', $profile);
        if ($rateLimit !== null) $q->equal('rate-limit', $rateLimit);
        $client->query($q)->read();
        return true;
    }

    /** Refresh device.identity / board / version from /system/resource. */
    public function refreshDeviceMetadata(DeviceMikrotik $device): DeviceMikrotik
    {
        try {
            $info = $this->ping($device);
            $device->identity   = $info['identity']['name'] ?? null;
            $device->board_name = $info['resource']['board-name'] ?? null;
            $device->version    = $info['resource']['version'] ?? null;
            $device->last_seen_at = Carbon::now();
            $device->save();
        } catch (\Throwable $e) {
            Log::warning("Mikrotik refresh failed for {$device->host}: " . $e->getMessage());
        }
        return $device;
    }
}
