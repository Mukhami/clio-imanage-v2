<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearWebhookData extends Command
{
    protected $signature = 'app:clear-webhook-data
                            {--tenant= : Only clear data for a specific tenant ID}
                            {--force : Required in production to prevent accidental execution}';

    protected $description = 'Clear webhook requests, processed iManage data, and failed jobs for a fresh start';

    public function handle(): int
    {
        $tenantId = $this->option('tenant') ? (int) $this->option('tenant') : null;

        // Production safety gate — require explicit --force flag
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('This command requires the --force flag in production.');
            $this->line('  Usage: php artisan app:clear-webhook-data --force');
            $this->line('  Usage: php artisan app:clear-webhook-data --tenant=1 --force');

            return self::FAILURE;
        }

        $scope = $tenantId ? "tenant {$tenantId}" : 'ALL tenants';

        // Show what will be deleted before confirming
        $this->warn("You are about to delete ALL webhook processing data for {$scope}:");
        $this->line('  - workspace_security_audits');
        $this->line('  - imanage_workspaces');
        $this->line('  - imanage_matters');
        $this->line('  - imanage_clients');
        $this->line('  - webhook_requests');
        if (! $tenantId) {
            $this->line('  - failed_jobs');
        }
        $this->newLine();

        if (! $this->confirm('Are you absolutely sure? This cannot be undone.')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        // Double confirmation in production
        if (app()->isProduction()) {
            if (! $this->confirm("PRODUCTION ENVIRONMENT — type yes to confirm deletion for {$scope}")) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }
        }

        $this->info('Clearing webhook processing data...');

        // Order matters — respect foreign key constraints
        $tables = [
            'workspace_security_audits' => 'tenant_id',
            'imanage_workspaces'        => 'tenant_id',
            'imanage_matters'           => 'tenant_id',
            'imanage_clients'           => 'tenant_id',
            'webhook_requests'          => 'tenant_id',
        ];

        foreach ($tables as $table => $column) {
            $query = DB::table($table);

            if ($tenantId) {
                $query->where($column, $tenantId);
            }

            $count = $query->count();
            $query->delete();

            $this->line("  Deleted {$count} rows from {$table}");
        }

        // Clear failed jobs (not tenant-scoped, but safe to clear)
        if (! $tenantId) {
            $failedCount = DB::table('failed_jobs')->count();
            DB::table('failed_jobs')->truncate();
            $this->line("  Cleared {$failedCount} failed jobs");
        }

        $this->newLine();
        $this->info('Done. Webhook processing data has been cleared.');
        $this->warn('Webhook registrations in Clio were kept. Use the admin panel to manage them.');

        return self::SUCCESS;
    }
}
