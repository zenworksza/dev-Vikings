<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['platform_admin', 'franchisee'] as $role) {
            Role::findOrCreate($role);
        }
    }
}
