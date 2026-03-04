<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions
        $permissions = [
            'seller.view',
            'seller.create',
            'seller.edit',
            'seller.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $manager = Role::firstOrCreate(['name' => 'Manager']);

        // Assign permissions
        $admin->givePermissionTo(Permission::all());

        $manager->givePermissionTo([
            'seller.view',
            'seller.create',
            'seller.edit',
        ]);
    }
}
