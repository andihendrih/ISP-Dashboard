<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => Role::ADMIN,   'label' => 'Administrator', 'permissions' => ['*']],
            ['name' => Role::NOC,     'label' => 'NOC',           'permissions' => ['dashboard', 'pppoe', 'hotspot', 'users', 'mikrotik', 'snmp', 'genieacs']],
            ['name' => Role::FINANCE, 'label' => 'Finance',       'permissions' => ['dashboard', 'users', 'reports']],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['name' => $r['name']], $r);
        }
    }
}
