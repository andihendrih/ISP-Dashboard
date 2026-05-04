<?php

namespace App\Services;

/**
 * Parses GenieACS device "raw" JSON (TR-069 parameter tree). Prefers
 * VirtualParameters when available (matches user's GenieACS provision setup),
 * falls back to standard TR-069 paths otherwise.
 *
 * VirtualParameter mapping (from user's GenieACS setup):
 *   Redaman           -> VirtualParameters.Redaman (may include " dBm")
 *   Uptime            -> VirtualParameters.Uptime
 *   PPPoE.Username    -> VirtualParameters.PPPoEUsername
 *   PPPoE.Password    -> VirtualParameters.PPPoEPassword
 *   PPPoE.ServiceType -> VirtualParameters.ServiceList
 *   PPPoE.VLAN        -> VirtualParameters.WANPPPVlanID
 *   PPPoE.Enable      -> VirtualParameters.WANPPPEnable
 *   WAN_IP.External   -> VirtualParameters.MGMTIP
 *   WAN_IP.Subnet     -> VirtualParameters.MGMTMASK
 *   WAN_IP.Gateway    -> VirtualParameters.MGMTGW
 *   WAN_IP.VLAN       -> VirtualParameters.VLANIDTR
 *   WAN_IP.Service    -> VirtualParameters.ServiceListTR
 */
class GenieacsParameterExtractor
{
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
        $productClass = $this->pluck($this->raw, 'DeviceID.ProductClass');
        return [
            'pppoe'         => $this->extractPppoe(),
            'wan_ip'        => $this->extractWanIp(),
            'wifi_24'       => $this->extractWifi('2.4', $productClass),
            'wifi_5g'       => $this->extractWifi('5', $productClass),
            'rx_power'      => $this->extractRxPower(),
            'uptime'        => $this->extractUptime(),
            'product_class' => $productClass,
            'registered_at' => $this->pluck($this->raw, '_registered'),
            'last_inform'   => $this->pluck($this->raw, '_lastInform'),
            'tags'          => $this->raw['_tags'] ?? [],
        ];
    }

    /* ----------------------------------------------------------------- */
    /* PPPoE  (prefer VirtualParameters)                                   */
    /* ----------------------------------------------------------------- */

    private function extractPppoe(): array
    {
        $vUser = $this->pluck($this->raw, 'VirtualParameters.PPPoEUsername');
        if ($vUser !== null && $vUser !== '') {
            return [
                'username'         => $vUser,
                'username_path'    => 'VirtualParameters.PPPoEUsername',
                'password'         => $this->pluck($this->raw, 'VirtualParameters.PPPoEPassword'),
                'password_path'    => 'VirtualParameters.PPPoEPassword',
                'vlan'             => $this->pluck($this->raw, 'VirtualParameters.WANPPPVlanID'),
                'vlan_path'        => 'VirtualParameters.WANPPPVlanID',
                'enable'           => $this->boolish($this->pluck($this->raw, 'VirtualParameters.WANPPPEnable')),
                'enable_path'      => 'VirtualParameters.WANPPPEnable',
                'service_type'     => $this->pluck($this->raw, 'VirtualParameters.ServiceList'),
                'service_type_path' => 'VirtualParameters.ServiceList',
                'path'             => null, // virtual-only
                'source'           => 'virtual',
            ];
        }

        // Fallback: walk TR-069 tree
        $prefix = $this->detectPrefix();
        foreach ($this->getChildIndexes("{$prefix}.WANDevice") as $wIdx) {
            $cdPath = "{$prefix}.WANDevice.{$wIdx}.WANConnectionDevice";
            foreach ($this->getChildIndexes($cdPath) as $cIdx) {
                $pppPath = "{$cdPath}.{$cIdx}.WANPPPConnection";
                foreach ($this->getChildIndexes($pppPath) as $pIdx) {
                    $base = "{$pppPath}.{$pIdx}";
                    $user = $this->pluck($this->raw, "{$base}.Username");
                    if ($user === null || $user === '') continue;
                    return [
                        'username'      => $user,
                        'username_path' => "{$base}.Username",
                        'password'      => $this->pluck($this->raw, "{$base}.Password"),
                        'password_path' => "{$base}.Password",
                        'vlan'          => $this->extractVlan("{$cdPath}.{$cIdx}", $base),
                        'vlan_path'     => null,
                        'enable'        => $this->boolish($this->pluck($this->raw, "{$base}.Enable")),
                        'enable_path'   => "{$base}.Enable",
                        'service_type'  => $this->extractServiceType($base),
                        'service_type_path' => null,
                        'path'          => $base,
                        'source'        => 'tr069',
                    ];
                }
            }
        }
        return [];
    }

    /* ----------------------------------------------------------------- */
    /* WAN IP (prefer VirtualParameters MGMT*)                             */
    /* ----------------------------------------------------------------- */

    private function extractWanIp(): array
    {
        $vIp = $this->pluck($this->raw, 'VirtualParameters.MGMTIP');
        if ($vIp !== null && $vIp !== '' && $vIp !== '0.0.0.0') {
            return [
                'external_ip'       => $vIp,
                'ip_path'           => 'VirtualParameters.MGMTIP',
                'subnet_mask'       => $this->pluck($this->raw, 'VirtualParameters.MGMTMASK'),
                'subnet_path'       => 'VirtualParameters.MGMTMASK',
                'gateway'           => $this->pluck($this->raw, 'VirtualParameters.MGMTGW'),
                'gateway_path'      => 'VirtualParameters.MGMTGW',
                'vlan'              => $this->pluck($this->raw, 'VirtualParameters.VLANIDTR'),
                'vlan_path'         => 'VirtualParameters.VLANIDTR',
                'service_type'      => $this->pluck($this->raw, 'VirtualParameters.ServiceListTR'),
                'service_type_path' => 'VirtualParameters.ServiceListTR',
                'path'              => null,
                'source'            => 'virtual',
            ];
        }

        // Fallback to TR-069 walk
        $prefix = $this->detectPrefix();
        foreach ($this->getChildIndexes("{$prefix}.WANDevice") as $wIdx) {
            $cdPath = "{$prefix}.WANDevice.{$wIdx}.WANConnectionDevice";
            foreach ($this->getChildIndexes($cdPath) as $cIdx) {
                $ipPath = "{$cdPath}.{$cIdx}.WANIPConnection";
                foreach ($this->getChildIndexes($ipPath) as $iIdx) {
                    $base = "{$ipPath}.{$iIdx}";
                    $ext  = $this->pluck($this->raw, "{$base}.ExternalIPAddress");
                    if ($ext === null || $ext === '' || $ext === '0.0.0.0') continue;
                    return [
                        'external_ip'  => $ext,
                        'ip_path'      => "{$base}.ExternalIPAddress",
                        'subnet_mask'  => $this->pluck($this->raw, "{$base}.SubnetMask"),
                        'subnet_path'  => "{$base}.SubnetMask",
                        'gateway'      => $this->pluck($this->raw, "{$base}.DefaultGateway"),
                        'gateway_path' => "{$base}.DefaultGateway",
                        'vlan'         => $this->extractVlan("{$cdPath}.{$cIdx}", $base),
                        'vlan_path'    => null,
                        'service_type' => $this->extractServiceType($base),
                        'service_type_path' => null,
                        'path'         => $base,
                        'source'       => 'tr069',
                    ];
                }
            }
        }
        return [];
    }

    /* ----------------------------------------------------------------- */
    /* WiFi 2.4 / 5 GHz                                                    */
    /* Password path differs by product family:                            */
    /*   - Huawei HG / EG / HS  -> PreSharedKey.1.KeyPassphrase            */
    /*   - others               -> KeyPassphrase                            */
    /* ----------------------------------------------------------------- */

    private function extractWifi(string $band, ?string $productClass): array
    {
        $prefix = $this->detectPrefix();
        // Standard model layout:
        //   2.4 GHz -> LANDevice.1.WLANConfiguration.1
        //   5   GHz -> LANDevice.1.WLANConfiguration.5
        $wlanIdx = $band === '5' ? 5 : 1;
        $base = "{$prefix}.LANDevice.1.WLANConfiguration.{$wlanIdx}";

        $ssid = $this->pluck($this->raw, "{$base}.SSID");
        if ($ssid === null) {
            // Fallback: search all WLANConfig indexes for one whose freq matches
            foreach ($this->getChildIndexes("{$prefix}.LANDevice.1.WLANConfiguration") as $idx) {
                $b2 = "{$prefix}.LANDevice.1.WLANConfiguration.{$idx}";
                $freq = (string) ($this->pluck($this->raw, "{$b2}.X_HW_OperatingFrequencyBand")
                    ?? $this->pluck($this->raw, "{$b2}.OperatingFrequencyBand")
                    ?? '');
                $isFive = str_contains($freq, '5');
                if (($band === '5' && $isFive) || ($band === '2.4' && !$isFive)) {
                    $base = $b2;
                    $ssid = $this->pluck($this->raw, "{$base}.SSID");
                    $wlanIdx = $idx;
                    if ($ssid !== null) break;
                }
            }
        }
        if ($ssid === null) return [];

        // Determine password parameter path based on product family.
        $isHuaweiSeries = $productClass !== null
            && preg_match('/^(HG|EG|HS)/i', $productClass);

        $pskPath  = "{$base}.PreSharedKey.1.KeyPassphrase";
        $kpPath   = "{$base}.KeyPassphrase";

        // Special-case: user's setup uses LANDevice.5.WLANConfiguration.1.KeyPassphrase
        // for 5 GHz on non-Huawei models.
        if ($band === '5' && !$isHuaweiSeries) {
            $altPath = "{$prefix}.LANDevice.5.WLANConfiguration.1.KeyPassphrase";
            if ($this->pluck($this->raw, $altPath) !== null) {
                $kpPath = $altPath;
            }
        }

        $password     = $isHuaweiSeries
            ? ($this->pluck($this->raw, $pskPath) ?? $this->pluck($this->raw, $kpPath))
            : ($this->pluck($this->raw, $kpPath) ?? $this->pluck($this->raw, $pskPath));
        $passwordPath = $isHuaweiSeries ? $pskPath : $kpPath;

        return [
            'ssid'          => $ssid,
            'ssid_path'     => "{$base}.SSID",
            'password'      => $password,
            'password_path' => $passwordPath,
            'security'      => $this->pluck($this->raw, "{$base}.BeaconType") ?? 'Unknown',
            'enable'        => $this->boolish($this->pluck($this->raw, "{$base}.Enable")),
            'enable_path'   => "{$base}.Enable",
            'path'          => $base,
            'wlan_idx'      => $wlanIdx,
        ];
    }

    /* ----------------------------------------------------------------- */
    /* RX Power / Redaman                                                  */
    /* ----------------------------------------------------------------- */

    private function extractRxPower(): ?float
    {
        $candidates = [
            'VirtualParameters.Redaman',
            'InternetGatewayDevice.WANDevice.1.X_HW_GponInterfaceConfig.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_GponInterfaceConfig.Stats.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_CT-COM_GponInterfaceConfig.RXPower',
            'InternetGatewayDevice.WANDevice.1.X_ZTE-COM_GponLinkInfo.RXPower',
            'Device.X_GponInterfaceConfig.Stats.RXPower',
        ];
        foreach ($candidates as $path) {
            $val = $this->pluck($this->raw, $path);
            if ($val === null || $val === '') continue;
            // Strip trailing " dBm" if present (VirtualParameters.Redaman returns string)
            $stripped = trim(preg_replace('/\s*dBm\s*$/i', '', (string) $val));
            if (!is_numeric($stripped)) continue;
            $f = (float) $stripped;
            return abs($f) > 100 ? round($f / 100, 2) : round($f, 2);
        }
        return null;
    }

    private function extractUptime(): ?string
    {
        $candidates = [
            'VirtualParameters.Uptime',
            'InternetGatewayDevice.DeviceInfo.UpTime',
            'Device.DeviceInfo.UpTime',
        ];
        foreach ($candidates as $path) {
            $v = $this->pluck($this->raw, $path);
            if ($v !== null && $v !== '') return (string) $v;
        }
        return null;
    }

    /* ----------------------------------------------------------------- */
    /* helpers                                                             */
    /* ----------------------------------------------------------------- */

    private function detectPrefix(): string
    {
        if (isset($this->raw['InternetGatewayDevice'])) return 'InternetGatewayDevice';
        if (isset($this->raw['Device'])) return 'Device';
        return 'InternetGatewayDevice';
    }

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
        $candidates = [
            "{$cdBase}.X_HW_VLAN",
            "{$connBase}.X_HW_VLAN",
            "{$connBase}.X_VLANIDMark",
            "{$connBase}.X_CT-COM_VLAN",
            "{$cdBase}.WANEthernetLinkConfig.X_VLAN_ID",
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
