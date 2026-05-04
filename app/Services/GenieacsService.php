<?php

namespace App\Services;

use App\Models\GenieacsDevice;
use App\Services\GenieacsParameterExtractor;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * GenieACS NBI client. The NBI is normally exposed at port 7557. Requests
 * MAY require basic auth depending on the deployment.
 *
 * https://docs.genieacs.com/en/latest/api-reference.html
 */
class GenieacsService
{
    private function http(): PendingRequest
    {
        $client = Http::baseUrl(rtrim(config('ahnet.genieacs.nbi_url'), '/'))
            ->timeout((int) config('ahnet.genieacs.timeout', 10))
            ->acceptJson();

        $user = config('ahnet.genieacs.username');
        $pass = config('ahnet.genieacs.password');
        if ($user) {
            $client = $client->withBasicAuth($user, (string) $pass);
        }

        return $client;
    }

    /** Raw list of ONUs from GenieACS, with optional MongoDB-style query. */
    public function listOnu(array $query = [], int $limit = 100): array
    {
        $params = [];
        if (!empty($query)) $params['query'] = json_encode($query);
        if ($limit) $params['limit'] = $limit;

        $resp = $this->http()->get('/devices', $params);
        $resp->throw();
        return $resp->json() ?? [];
    }

    /** Sync GenieACS devices into our local genieacs_devices table. */
    public function syncDevices(int $limit = 200): int
    {
        $rows = $this->listOnu([], $limit);
        $count = 0;
        foreach ($rows as $row) {
            $deviceId = $row['_id'] ?? null;
            if (!$deviceId) continue;

            $params = GenieacsParameterExtractor::from($row)->all();

            GenieacsDevice::updateOrCreate(
                ['device_id' => $deviceId],
                [
                    'serial_number'    => $this->pluck($row, 'DeviceID.SerialNumber'),
                    'manufacturer'     => $this->pluck($row, 'DeviceID.Manufacturer'),
                    'product_class'    => $this->pluck($row, 'DeviceID.ProductClass'),
                    'model_name'       => $this->pluck($row, 'InternetGatewayDevice.DeviceInfo.ModelName')
                                         ?? $this->pluck($row, 'Device.DeviceInfo.ModelName'),
                    'software_version' => $this->pluck($row, 'InternetGatewayDevice.DeviceInfo.SoftwareVersion')
                                         ?? $this->pluck($row, 'Device.DeviceInfo.SoftwareVersion'),
                    'hardware_version' => $this->pluck($row, 'InternetGatewayDevice.DeviceInfo.HardwareVersion')
                                         ?? $this->pluck($row, 'Device.DeviceInfo.HardwareVersion'),
                    'ssid'             => $params['wifi_24']['ssid'] ?? null,
                    'ip'               => $params['wan_ip']['external_ip'] ?? null,
                    'tag'              => isset($row['_tags']) && is_array($row['_tags']) ? implode(',', $row['_tags']) : null,
                    'status'           => $this->statusFromLastInform($row),
                    'last_inform_at'   => isset($row['_lastInform']) ? Carbon::parse($row['_lastInform']) : null,
                    'raw'              => $row,
                    // Cached extracted fields (avoid loading raw JSON on list page)
                    'pppoe_username'   => $params['pppoe']['username'] ?? null,
                    'rx_power'         => $params['rx_power'] ?? null,
                    'wifi_ssid_24'     => $params['wifi_24']['ssid'] ?? null,
                    'wifi_ssid_5g'    => $params['wifi_5g']['ssid'] ?? null,
                    'wan_external_ip' => $params['wan_ip']['external_ip'] ?? null,
                ]
            );
            $count++;
        }
        return $count;
    }

    /** Re-extract cache columns for all devices from existing raw JSON. */
    public function rebuildExtractedFields(int $chunk = 100): int
    {
        $count = 0;
        GenieacsDevice::query()->orderBy('id')->chunkById($chunk, function ($rows) use (&$count) {
            foreach ($rows as $d) {
                if (!is_array($d->raw)) continue;
                $p = GenieacsParameterExtractor::from($d->raw)->all();
                $d->forceFill([
                    'pppoe_username'  => $p['pppoe']['username'] ?? null,
                    'rx_power'        => $p['rx_power'] ?? null,
                    'wifi_ssid_24'    => $p['wifi_24']['ssid'] ?? null,
                    'wifi_ssid_5g'    => $p['wifi_5g']['ssid'] ?? null,
                    'wan_external_ip' => $p['wan_ip']['external_ip'] ?? null,
                ])->save();
                $count++;
            }
        });
        return $count;
    }

    /**
     * Submit a refresh / commit task and process it immediately.
     * https://docs.genieacs.com/en/latest/api-reference.html#post--devices--device_id--tasks
     */
    public function reboot(string $deviceId): Response
    {
        return $this->postTask($deviceId, ['name' => 'reboot']);
    }

    /**
     * Generic setParameterValues task. $params is an array of
     * [path, value, type] tuples (type defaults to xsd:string).
     */
    public function setParameters(string $deviceId, array $params): Response
    {
        $values = [];
        foreach ($params as $row) {
            [$path, $value] = [$row[0], $row[1]];
            $type = $row[2] ?? (is_bool($value) ? 'xsd:boolean' : 'xsd:string');
            // Filter null/empty paths to avoid GenieACS validation errors
            if ($path === null || $path === '') continue;
            $values[] = [$path, $value, $type];
        }
        if (empty($values)) {
            throw new \InvalidArgumentException('No parameters to set.');
        }
        return $this->postTask($deviceId, [
            'name'            => 'setParameterValues',
            'parameterValues' => $values,
        ]);
    }

    public function setParameter(string $deviceId, string $path, $value, ?string $type = null): Response
    {
        return $this->setParameters($deviceId, [[$path, $value, $type]]);
    }

    public function setSsid(string $deviceId, string $ssid, ?string $ssidPath = null): Response
    {
        $path = $ssidPath ?: 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID';
        return $this->setParameter($deviceId, $path, $ssid);
    }

    /**
     * Set WiFi password. The exact parameter path varies by product family
     * (Huawei HG/EG/HS use PreSharedKey.1.KeyPassphrase, others use
     * KeyPassphrase) — caller passes the resolved path from extractor.
     */
    public function setWifiPassword(string $deviceId, string $password, ?string $passwordPath = null): Response
    {
        if ($passwordPath) {
            return $this->setParameter($deviceId, $passwordPath, $password);
        }
        // Compatibility fallback: write to common Huawei + standard paths
        $base = 'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1';
        return $this->setParameters($deviceId, [
            ["{$base}.PreSharedKey.1.KeyPassphrase", $password, 'xsd:string'],
            ["{$base}.KeyPassphrase", $password, 'xsd:string'],
        ]);
    }

    /**
     * Set PPPoE credentials. Caller passes resolved username_path and
     * password_path from extractor (VirtualParameters.* if user setup
     * supports them, else TR-069 path).
     */
    public function setPppoeCredentials(string $deviceId, string $usernamePath, string $passwordPath, string $username, string $password): Response
    {
        return $this->setParameters($deviceId, [
            [$usernamePath, $username, 'xsd:string'],
            [$passwordPath, $password, 'xsd:string'],
        ]);
    }

    /** Toggle WAN PPPoE enable flag. Path is from extractor['pppoe']['enable_path']. */
    public function setWanEnable(string $deviceId, string $enablePath, bool $enable): Response
    {
        // VirtualParameters.WANPPPEnable expects boolean; some setups want string
        return $this->setParameters($deviceId, [
            [$enablePath, $enable, 'xsd:boolean'],
        ]);
    }

    public function suspendWan(string $deviceId, string $enablePath): Response
    {
        return $this->setWanEnable($deviceId, $enablePath, false);
    }

    public function enableWan(string $deviceId, string $enablePath): Response
    {
        return $this->setWanEnable($deviceId, $enablePath, true);
    }

    /** Update WAN IP static config — paths from extractor['wan_ip']. */
    public function setWanIp(string $deviceId, string $ipPath, string $subnetPath, string $gatewayPath, string $ip, string $subnet, string $gateway): Response
    {
        return $this->setParameters($deviceId, [
            [$ipPath, $ip, 'xsd:string'],
            [$subnetPath, $subnet, 'xsd:string'],
            [$gatewayPath, $gateway, 'xsd:string'],
        ]);
    }

    /** Trigger TR-069 factory reset task. */
    public function factoryReset(string $deviceId): Response
    {
        return $this->postTask($deviceId, ['name' => 'factoryReset']);
    }

    public function refreshDevice(string $deviceId): Response
    {
        return $this->postTask($deviceId, [
            'name'            => 'refreshObject',
            'objectName'      => '',
        ]);
    }

    public function status(string $deviceId): array
    {
        $resp = $this->http()->get('/devices', [
            'query' => json_encode(['_id' => $deviceId]),
        ]);
        $resp->throw();
        $rows = $resp->json() ?? [];
        return $rows[0] ?? [];
    }

    /* ----------------------------------------------------------------- */

    private function postTask(string $deviceId, array $task): Response
    {
        $resp = $this->http()->asJson()->post(
            '/devices/' . rawurlencode($deviceId) . '/tasks?connection_request',
            $task
        );
        $resp->throw();
        return $resp;
    }

    private function pluck(array $row, string $path): ?string
    {
        $segments = explode('.', $path);
        $cursor = $row;
        foreach ($segments as $seg) {
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

    private function statusFromLastInform(array $row): string
    {
        if (!isset($row['_lastInform'])) return 'unknown';
        $last = Carbon::parse($row['_lastInform']);
        // Online if informed within the last 10 minutes (heuristic)
        return $last->gt(Carbon::now()->subMinutes(10)) ? 'online' : 'offline';
    }
}
