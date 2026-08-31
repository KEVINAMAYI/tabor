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

            // Accounting / General Ledger
            'view-chart-of-accounts',
            'create-chart-of-accounts',
            'edit-chart-of-accounts',
            'manage-accounting-periods',
            'view-journal-entries',
            'create-journal-entries',
            'approve-journal-entries',
            'view-trial-balance',

            // Accounting / Petty Cash & Imprest (Phase 2)
            'view-vote-heads',
            'manage-vote-heads',
            'view-petty-cash',
            'manage-petty-cash-custodians',
            'create-petty-cash-expense',
            'approve-petty-cash-expense',

            // Accounting / Budget Management (Phase 3)
            'view-budgets',
            'manage-budgets',

            // Accounting / Procurement & Supplier Payments (Phase 4)
            'view-suppliers',
            'manage-suppliers',
            'view-purchase-requisitions',
            'create-purchase-requisitions',
            'approve-purchase-requisitions-department',
            'approve-purchase-requisitions-finance',
            'view-purchase-orders',
            'create-purchase-orders',
            'record-goods-received',
            'view-supplier-invoices',
            'record-supplier-invoices',
            'create-supplier-payments',

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
                'view-reports',
                // Segregation of duties (SRS "Journal Approval"): finance-manager
                // can view/create GL records but not approve journal entries or
                // open/close/lock accounting periods.
                'view-chart-of-accounts',
                'view-journal-entries',
                'create-journal-entries',
                'view-trial-balance',
                // Petty Cash: Finance is the natural approver of expenses
                // submitted by non-finance custodians (Registrar, etc.), so
                // unlike journal-entry approval this isn't withheld — but
                // vote-head master data stays with admin, same as
                // chart-of-accounts create/edit.
                'view-vote-heads',
                'view-petty-cash',
                'manage-petty-cash-custodians',
                'create-petty-cash-expense',
                'approve-petty-cash-expense',
                // SRS §8: "Finance shall prepare annual budgets" — budget
                // prep/edit is explicitly a Finance responsibility, unlike
                // vote-head master data.
                'view-budgets',
                'manage-budgets',
                // Procurement: both approval stages granted to finance-manager
                // (no segregation-of-duties reason to withhold department
                // approval, since finance-manager already holds the finance
                // approval anyway — unlike journal-entry approval above).
                // manage-suppliers (master data) stays admin-only.
                'view-suppliers',
                'view-purchase-requisitions',
                'create-purchase-requisitions',
                'approve-purchase-requisitions-department',
                'approve-purchase-requisitions-finance',
                'view-purchase-orders',
                'create-purchase-orders',
                'record-goods-received',
                'view-supplier-invoices',
                'record-supplier-invoices',
                'create-supplier-payments',
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
