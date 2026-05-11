<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => Role::SUPERADMIN, 'label' => 'Super Admin',   'permissions' => ['*']],
            ['name' => Role::ADMIN,      'label' => 'Administrator', 'permissions' => ['*']],
            ['name' => Role::NOC,        'label' => 'NOC',           'permissions' => ['dashboard', 'pppoe', 'hotspot', 'users', 'mikrotik', 'snmp', 'genieacs']],
            ['name' => Role::FINANCE,    'label' => 'Finance',       'permissions' => ['dashboard', 'users', 'reports']],
            ['name' => Role::TEKNISI,    'label' => 'Teknisi',       'permissions' => ['dashboard', 'devices', 'genieacs', 'mikrotik', 'snmp']],
            ['name' => Role::CUSTOMER,   'label' => 'Pelanggan',     'permissions' => ['portal']],
        ];

        foreach ($roles as $r) {
            Role::updateOrCreate(['name' => $r['name']], $r);
        }
    }
}
