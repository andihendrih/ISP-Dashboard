<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Promote user existing menjadi superadmin (lihat semua tenant).
 *
 * Dipakai sekali pas onboarding awal Phase 3 — platform owner perlu role
 * superadmin supaya bisa lihat menu Tenants & view-as feature.
 *
 * Usage:
 *   php artisan tenants:make-superadmin admin@ahnet.local
 */
class TenantsMakeSuperadmin extends Command
{
    protected $signature = 'tenants:make-superadmin {email : Email user yang akan dijadikan superadmin}';
    protected $description = 'Promote user existing jadi superadmin (lihat semua tenant)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User dengan email '{$email}' tidak ditemukan.");
            return self::FAILURE;
        }

        $superRole = Role::where('name', Role::SUPERADMIN)->first();
        if (!$superRole) {
            $this->error("Role 'superadmin' belum ada. Run: php artisan db:seed --class=RoleSeeder");
            return self::FAILURE;
        }

        $user->update([
            'role_id'   => $superRole->id,
            'is_active' => true,
        ]);

        $this->info("OK — {$user->name} ({$user->email}) sekarang superadmin.");
        $this->line("  tenant_id: " . ($user->tenant_id ?? 'null'));
        $this->line("  Buka /settings/tenants untuk manage tenants.");
        return self::SUCCESS;
    }
}
