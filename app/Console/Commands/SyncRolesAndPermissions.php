<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncRolesAndPermissions extends Command
{
    protected $signature = 'permissions:sync';
    protected $description = 'Sync all roles and permissions — safe to run at any time';

    private array $permissions = [
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

    public function handle(): int
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->syncPermissions();
        $this->syncRoles();

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Roles and permissions synced successfully.');

        return Command::SUCCESS;
    }

    private function syncPermissions(): void
    {
        foreach ($this->permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        // Remove any permissions that are no longer in the list
        $removed = Permission::where('guard_name', 'web')
            ->whereNotIn('name', $this->permissions)
            ->get();

        if ($removed->isNotEmpty()) {
            foreach ($removed as $permission) {
                $this->warn("Removing obsolete permission: {$permission->name}");
                $permission->delete();
            }
        }

        $this->line('  Permissions: ' . count($this->permissions) . ' active, ' . $removed->count() . ' removed.');
    }

    private function syncRoles(): void
    {
        // Super Admin — all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());

        // Admin — all except system.settings
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(
            Permission::where('guard_name', 'web')->where('name', '!=', 'system.settings')->get()
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

        $this->line('  Roles: Super Admin, Admin, Support, Tenant Admin, Tenant Viewer synced.');
    }
}
