<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Tenants
            'tenants.create',
            'tenants.edit',
            'tenants.delete',
            'tenants.view',
            // Configurations
            'configs.edit',
            'configs.view',
            // Webhooks
            'webhooks.view',
            'webhooks.reattempt',
            'webhooks.create',
            // Subscriptions
            'subscriptions.manage',
            'subscriptions.view',
            // Users
            'users.manage',
            'users.create',
            'users.edit',
            'users.delete',
            // System
            'system.settings',
            'system.health',
            // Mappings
            'mappings.view',
            'mappings.edit',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Super Admin — all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // Admin — all except system.settings
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(
            Permission::where('name', '!=', 'system.settings')->get()
        );

        // Support — view + reattempt only
        $support = Role::firstOrCreate(['name' => 'Support', 'guard_name' => 'web']);
        $support->syncPermissions([
            'tenants.view',
            'webhooks.view',
            'webhooks.reattempt',
            'subscriptions.view',
            'system.health',
        ]);

        // Tenant Admin — own tenant, limited configs
        $tenantAdmin = Role::firstOrCreate(['name' => 'Tenant Admin', 'guard_name' => 'web']);
        $tenantAdmin->syncPermissions([
            'tenants.view',
            'configs.view',
            'webhooks.view',
            'mappings.view',
        ]);

        // Tenant Viewer — own tenant, read-only
        $tenantViewer = Role::firstOrCreate(['name' => 'Tenant Viewer', 'guard_name' => 'web']);
        $tenantViewer->syncPermissions([
            'tenants.view',
            'webhooks.view',
        ]);
    }
}
