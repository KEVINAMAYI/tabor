<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void{
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();


        $permissions = [
            // Students module
            'view-students',
            'add-students',
            'edit-students',
            'delete-students',

            // Roles module
            'view-roles',
            'add-roles',
            'edit-roles',
            'delete-roles',

            //courses
            'view-courses',
            'add-courses',
            'edit-courses',
            'delete-courses',

            //accountant
            'manage-money',
        ];

        // Create each permission if it doesn’t exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // === Define Roles and Assign Permissions ===

        // Admin - Full Access
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

    }
}
