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

            // Class groups
            'view-class-groups',
            'add-class-groups',
            'edit-class-groups',
            'delete-class-groups',

            //Attendance
            'view-attendance',
            'add-attendance',
            'edit-attendance',
            'delete-attendance',

            // Intakes
            'view-intakes',
            'add-intakes',
            'edit-intakes',
            'delete-intakes',

            // Accounting
            'create-payments',
            'view-payments',
            'edit-payments',
            'delete-payments',
            'give-discounts',

            //reports
            'view-reports',

            //manage users
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',

            // Enrollments
            'create-enrollments',
            'view-enrollments',
            'edit-enrollments',
            'delete-enrollments',

            //settings
            'manage-settings',

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
                'create-payments',
                'view-payments',
                'edit-payments',
            ],

            'student' => [
                'view-courses',
            ],

            'lecturer' => [
                'view-courses',
                'edit-courses',
                'view-attendance',
                'add-attendance',
                'edit-attendance',
                'delete-attendance',
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
