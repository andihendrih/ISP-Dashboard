<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Tenancy\BelongsToTenant;

class NotificationSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = ['key', 'enabled', 'value'];
    protected $casts = ['enabled' => 'bool'];

    public static function isEnabled(string $key, bool $default = true): bool
    {
        $row = static::where('key', $key)->first();
        return $row ? (bool) $row->enabled : $default;
    }

    public static function setEnabled(string $key, bool $enabled): void
    {
        static::updateOrCreate(['key' => $key], ['enabled' => $enabled]);
    }
}
