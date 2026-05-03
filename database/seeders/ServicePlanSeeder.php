<?php

namespace Database\Seeders;

use App\Models\ServicePlan;
use Illuminate\Database\Seeder;

class ServicePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'code'         => 'PPP-10M',
                'name'         => 'Home 10 Mbps',
                'service_type' => 'pppoe',
                'rate_limit'   => '10M/10M',
                'radius_group' => 'pppoe-10mbps',
                'price'        => 150000,
                'tax_percent'  => 0,
                'description'  => 'Paket rumahan 10 Mbps unlimited.',
                'is_active'    => true,
            ],
            [
                'code'         => 'PPP-20M',
                'name'         => 'Home 20 Mbps',
                'service_type' => 'pppoe',
                'rate_limit'   => '20M/20M',
                'radius_group' => 'pppoe-20mbps',
                'price'        => 250000,
                'tax_percent'  => 0,
                'description'  => 'Paket rumahan 20 Mbps unlimited.',
                'is_active'    => true,
            ],
            [
                'code'         => 'PPP-50M',
                'name'         => 'Bisnis 50 Mbps',
                'service_type' => 'pppoe',
                'rate_limit'   => '50M/50M',
                'radius_group' => 'pppoe-50mbps',
                'price'        => 500000,
                'tax_percent'  => 0,
                'description'  => 'Paket bisnis 50 Mbps prioritas.',
                'is_active'    => true,
            ],
        ];

        foreach ($plans as $plan) {
            ServicePlan::updateOrCreate(['code' => $plan['code']], $plan);
        }
    }
}
