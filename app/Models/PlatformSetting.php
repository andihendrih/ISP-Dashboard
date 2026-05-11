<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Single-row platform-level settings (superadmin-only).
 * Dipakai untuk:
 *  - Branding di invoice tenant billing (SUB-xxxx)
 *  - Bank info platform untuk tenant transfer subscription
 *  - WA / Email provider platform untuk kirim reminder ke tenant PIC
 */
class PlatformSetting extends Model
{
    protected $fillable = [
        'brand_name', 'brand_logo_path', 'brand_address', 'brand_phone',
        'brand_email', 'brand_tagline', 'bank_info',
        'wa_provider', 'wa_credentials',
        'email_provider', 'email_credentials',
    ];

    protected $casts = [
        'wa_credentials'    => 'encrypted:array',
        'email_credentials' => 'encrypted:array',
    ];

    /**
     * Singleton accessor — selalu return row id=1, bikin kalau belum ada.
     * Pakai instead of firstOrCreate setiap pemakaian.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function waCreds(): array
    {
        return is_array($this->wa_credentials) ? $this->wa_credentials : [];
    }

    public function emailCreds(): array
    {
        return is_array($this->email_credentials) ? $this->email_credentials : [];
    }

    public function effectiveBrandName(): string
    {
        return $this->brand_name ?: config('app.name', 'ISP Platform');
    }

    /** Parse `bank_info` (one line per rekening, format BANK|NO|NAMA) ke array. */
    public function bankAccounts(): array
    {
        $out = [];
        foreach (preg_split('/\r?\n/', (string) $this->bank_info) as $line) {
            $line = trim($line);
            if (!$line) continue;
            $parts = array_map('trim', explode('|', $line, 3));
            $out[] = [
                'bank'    => $parts[0] ?? '',
                'account' => $parts[1] ?? '',
                'name'    => $parts[2] ?? '',
            ];
        }
        return $out;
    }
}
