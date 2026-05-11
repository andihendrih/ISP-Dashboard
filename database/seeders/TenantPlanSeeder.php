<?php

namespace Database\Seeders;

use App\Models\TenantPlan;
use Illuminate\Database\Seeder;

/**
 * Seed default 3 tier plan: Basic, Pro, Enterprise.
 * Lo bisa edit / tambah custom plan lewat menu superadmin.
 */
class TenantPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code'          => 'basic',
                'name'          => 'Basic',
                'price'         => 300000,
                'max_customers' => 100,
                'max_devices'   => 50,
                'features'      => [
                    'support_priority' => 'standard',
                    'white_label'      => false,
                    'api_access'       => false,
                ],
                'sort_order'    => 1,
            ],
            [
                'code'          => 'pro',
                'name'          => 'Pro',
                'price'         => 750000,
                'max_customers' => 500,
                'max_devices'   => 200,
                'features'      => [
                    'support_priority' => 'priority',
                    'white_label'      => false,
                    'api_access'       => true,
                ],
                'sort_order'    => 2,
            ],
            [
                'code'          => 'enterprise',
                'name'          => 'Enterprise',
                'price'         => 1500000,
                'max_customers' => null, // unlimited
                'max_devices'   => null,
                'features'      => [
                    'support_priority' => 'dedicated',
                    'white_label'      => true,
                    'api_access'       => true,
                ],
                'sort_order'    => 3,
            ],
        ];

        foreach ($plans as $p) {
            TenantPlan::updateOrCreate(['code' => $p['code']], $p);
        }
    }
}
