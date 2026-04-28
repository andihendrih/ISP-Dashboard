<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin   = Role::where('name', Role::ADMIN)->firstOrFail();
        $noc     = Role::where('name', Role::NOC)->firstOrFail();
        $finance = Role::where('name', Role::FINANCE)->firstOrFail();

        $users = [
            ['name' => 'Administrator', 'email' => 'admin@ahnet.local',   'password' => 'password', 'role_id' => $admin->id],
            ['name' => 'NOC Operator',  'email' => 'noc@ahnet.local',     'password' => 'password', 'role_id' => $noc->id],
            ['name' => 'Finance Staff', 'email' => 'finance@ahnet.local', 'password' => 'password', 'role_id' => $finance->id],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email' => $u['email']],
                array_merge($u, ['password' => Hash::make($u['password']), 'is_active' => true])
            );
        }
    }
}
