<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSequenceConfig;
use Illuminate\Support\Facades\DB;

class SequenceNumberService
{
    public function __construct(
        private readonly Tenant $tenant,
    ) {}

    public function nextClientNumber(): string
    {
        $config = DB::transaction(function () {
            /** @var TenantSequenceConfig $config */
            $config = TenantSequenceConfig::where('tenant_id', $this->tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $config->increment('client_current_number');
            $config->refresh();

            return $config;
        });

        return $this->formatNumber(
            $config->client_prefix ?? '',
            $config->client_current_number,
            $config->client_digits,
        );
    }

    public function nextMatterNumber(string $clientKey): string
    {
        $config = DB::transaction(function () {
            /** @var TenantSequenceConfig $config */
            $config = TenantSequenceConfig::where('tenant_id', $this->tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $config->increment('matter_current_number');
            $config->refresh();

            return $config;
        });

        return $this->formatNumber(
            $config->matter_prefix ?? '',
            $config->matter_current_number,
            $config->matter_digits,
        );
    }

    public function writeBackToClioClient(int $clioClientId, string $sequenceNumber): void
    {
        // Resolved at runtime via ClioApiService — kept here for interface contract
        // Implementation will call ClioApiService::updateContactCustomField()
    }

    public function writeBackToClioMatter(int $clioMatterId, string $sequenceNumber): void
    {
        // Resolved at runtime via ClioApiService — kept here for interface contract
        // Implementation will call ClioApiService::updateMatterCustomField()
    }

    private function formatNumber(string $prefix, int $number, int $digits): string
    {
        return $prefix.str_pad((string) $number, $digits, '0', STR_PAD_LEFT);
    }
}
