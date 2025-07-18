<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissions = [

            // Student-related
            'view-students',
            'add-students',
            'edit-students',
            'delete-students',

            // lecturer-related
            'view-lecturers',
            'add-lecturers',
            'edit-lecturers',
            'delete-lecturers',

            // Roles management
            'view-roles',
            'add-roles',
            'edit-roles',
            'delete-roles',

            // Courses
            'view-courses',
            'add-courses',
            'edit-courses',
            'delete-courses',

            // Accounting
            'manage-money',
            'view-transactions',
            'generate-financial-reports',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define specific role-permission mappings
        $rolePermissions = [

            'super-admin' => 'all',

            'admin' => 'all',

            'finance-manager' => [
                'manage-money',
                'view-transactions',
                'generate-financial-reports',
            ],

            'student' => [
                'view-courses',
            ],

            'lecturer' => [
                'view-courses',
            ],
        ];

        // Assign permissions
        foreach ($rolePermissions as $role => $perms) {
            $roleInstance = Role::firstOrCreate(['name' => $role]);

            if ($perms === 'all') {
                $roleInstance->syncPermissions(Permission::all());
            } else {
                $roleInstance->syncPermissions($perms);
            }
        }
    }
}
