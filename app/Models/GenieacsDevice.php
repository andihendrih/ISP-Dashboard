<?php

namespace App\Models;

use App\Services\GenieacsParameterExtractor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenieacsDevice extends Model
{
    use HasFactory;

    protected $table = 'genieacs_devices';

    protected $fillable = [
        'device_id', 'serial_number', 'manufacturer', 'product_class',
        'model_name', 'software_version', 'hardware_version',
        'ssid', 'ip', 'tag', 'status', 'last_inform_at', 'raw',
        'pppoe_username', 'rx_power', 'wifi_ssid_24', 'wifi_ssid_5g', 'wan_external_ip',
    ];

    protected $casts = [
        'last_inform_at' => 'datetime',
        'raw'            => 'array',
        'rx_power'       => 'float',
    ];

    private ?array $cachedParams = null;

    /**
     * Decoded TR-069 parameters from `raw` JSON. Cached per instance.
     * Keys: pppoe, wan_ip, wifi_24, wifi_5g, rx_power, registered_at, tags.
     */
    public function params(): array
    {
        if ($this->cachedParams !== null) return $this->cachedParams;
        if (!is_array($this->raw)) return $this->cachedParams = [];
        return $this->cachedParams = GenieacsParameterExtractor::from($this->raw)->all();
    }

    public function pppoeUsername(): ?string
    {
        return $this->pppoe_username ?? ($this->params()['pppoe']['username'] ?? null);
    }

    public function rxPower(): ?float
    {
        return $this->rx_power ?? ($this->params()['rx_power'] ?? null);
    }

    public function tagsList(): array
    {
        if ($this->tag) {
            return array_filter(array_map('trim', explode(',', $this->tag)));
        }
        return $this->params()['tags'] ?? [];
    }

    public function tagsLabel(): string
    {
        $tags = $this->tagsList();
        return $tags ? implode(', ', $tags) : '';
    }
}
