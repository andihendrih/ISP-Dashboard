<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom `tenant_id` ke semua tabel business + backfill ke
 * tenant default. Setelah migration ini jalan:
 *   - Tenant default "ahnet" (id=1) ada di tabel `tenants`
 *   - Semua row existing di tabel-tabel di bawah punya tenant_id=1
 *
 * Tabel yang di-scope per-tenant:
 *   - users, customer_profiles, service_plans
 *   - invoices, payments
 *   - devices_mikrotik, devices_inventory, device_assignments
 *   - support_tickets, ticket_comments
 *   - notification_logs, notification_settings
 *   - voucher_batches, hotspot_vouchers
 *   - genieacs_devices
 *
 * Tabel yang TIDAK di-scope (shared / system):
 *   - roles (RBAC global)
 *   - migrations, password_reset_tokens, failed_jobs, personal_access_tokens
 *   - audit_logs (system-wide, tetep simpan tenant_id sbg context)
 *   - snmp_logs (network monitoring, gak terikat tenant)
 *   - radius_* (external DB, scoping via prefix username)
 */
return new class extends Migration {
    /** Tabel yang dapet tenant_id (FK nullable awal supaya migration sukses, di-NOT-NULL setelah backfill di phase berikutnya). */
    private array $tables = [
        'users',
        'customer_profiles',
        'service_plans',
        'invoices',
        'payments',
        'devices_mikrotik',
        'devices_inventory',
        'device_assignments',
        'support_tickets',
        'ticket_comments',
        'notification_logs',
        'notification_settings',
        'voucher_batches',
        'hotspot_vouchers',
        'genieacs_devices',
        'audit_logs',
    ];

    public function up(): void
    {
        // 1. Tambah kolom tenant_id ke semua tabel business (nullable awalnya)
        foreach ($this->tables as $tbl) {
            if (!Schema::hasTable($tbl)) {
                continue;
            }
            if (Schema::hasColumn($tbl, 'tenant_id')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
                $table->index('tenant_id');
            });
        }

        // 2. Pastikan tenant default ada (id=1, code=ahnet)
        $defaultId = DB::table('tenants')->where('code', 'ahnet')->value('id');
        if (!$defaultId) {
            $defaultId = DB::table('tenants')->insertGetId([
                'code'          => 'ahnet',
                'slug'          => 'ahnet',
                'name'          => 'AHNet (Default)',
                'plan'          => 'enterprise',
                'is_active'     => true,
                'brand_company' => config('ahnet.company', 'PT. AHNet'),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // 3. Backfill: semua row existing yang masih NULL → tenant default
        foreach ($this->tables as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'tenant_id')) {
                continue;
            }
            DB::table($tbl)->whereNull('tenant_id')->update(['tenant_id' => $defaultId]);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tbl) {
            if (!Schema::hasTable($tbl) || !Schema::hasColumn($tbl, 'tenant_id')) {
                continue;
            }
            Schema::table($tbl, function (Blueprint $table) use ($tbl) {
                // SQLite gak support drop foreign key dengan nama otomatis,
                // jadi pakai dropConstrainedForeignId.
                try {
                    $table->dropConstrainedForeignId('tenant_id');
                } catch (\Throwable $e) {
                    // fallback untuk driver yang berbeda
                    $table->dropColumn('tenant_id');
                }
            });
        }
    }
};
