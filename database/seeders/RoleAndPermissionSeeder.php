<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        Permission::create(['name' => 'manage-tenants']);
        Permission::create(['name' => 'view-borrowers']);
        Permission::create(['name' => 'create-borrowers']);
        Permission::create(['name' => 'edit-borrowers']);
        Permission::create(['name' => 'delete-borrowers']);
        Permission::create(['name' => 'view-loans']);
        Permission::create(['name' => 'create-loans']);
        Permission::create(['name' => 'edit-loans']);
        Permission::create(['name' => 'approve-loans']);
        Permission::create(['name' => 'foreclose-loans']);
        Permission::create(['name' => 'view-payments']);
        Permission::create(['name' => 'process-payments']);
        Permission::create(['name' => 'view-reports']);
        Permission::create(['name' => 'export-reports']);

        $superAdmin = Role::create(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'view-borrowers', 'create-borrowers', 'edit-borrowers', 'delete-borrowers',
            'view-loans', 'create-loans', 'edit-loans', 'approve-loans', 'foreclose-loans',
            'view-payments', 'process-payments',
            'view-reports', 'export-reports',
        ]);

        $loanOfficer = Role::create(['name' => 'loan-officer']);
        $loanOfficer->givePermissionTo([
            'view-borrowers', 'create-borrowers', 'edit-borrowers',
            'view-loans', 'create-loans', 'edit-loans',
            'view-payments',
        ]);

        $borrower = Role::create(['name' => 'borrower']);
        $borrower->givePermissionTo([
            'view-loans',
            'view-payments', 'process-payments',
        ]);
    }
}
