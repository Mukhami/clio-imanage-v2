<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProcessingStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WebhookRequest extends Model
{
    protected $fillable = [
        'tenant_id',
        'webhook_id',
        'url',
        'headers',
        'body',
        'payload_hash',
        'correlation_id',
        'clio_matter_id',
        'processing_stage',
        'retrieved_client_id',
        'retrieved_matter_id',
        'client_activity_complete',
        'matter_activity_complete',
        'workspace_activity_complete',
        'folder_activity_complete',
        'security_activity_complete',
        'workspace_link_custom_field_populated',
        'error_message',
        'error_count',
        'failed_at_stage',
        'skip_reason',
        'api_call_log',
        'started_at',
        'completed_at',
        'reattempted',
        'reattempted_by',
        'reattempted_at',
    ];

    protected function casts(): array
    {
        return [
            'processing_stage'                     => ProcessingStage::class,
            'headers'                              => 'array',
            'body'                                 => 'array',
            'started_at'                           => 'datetime',
            'completed_at'                         => 'datetime',
            'reattempted_at'                       => 'datetime',
            'client_activity_complete'             => 'boolean',
            'matter_activity_complete'             => 'boolean',
            'workspace_activity_complete'          => 'boolean',
            'folder_activity_complete'             => 'boolean',
            'security_activity_complete'           => 'boolean',
            'workspace_link_custom_field_populated' => 'boolean',
            'reattempted'                          => 'boolean',
            'api_call_log'                         => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    public function workspaceSecurityAudit(): HasOne
    {
        return $this->hasOne(WorkspaceSecurityAudit::class);
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function advanceTo(ProcessingStage $stage): void
    {
        $this->processing_stage = $stage;
        $this->save();
    }

    public function markFailed(string $message): void
    {
        $this->error_count++;
        $this->failed_at_stage  = $this->processing_stage?->value ?? 'unknown';
        $this->error_message    = $message;
        $this->processing_stage = ProcessingStage::Failed;
        $this->completed_at     = now();
        $this->save();
    }

    public function markSkipped(string $reason): void
    {
        $this->skip_reason      = $reason;
        $this->processing_stage = ProcessingStage::Skipped;
        $this->completed_at     = now();
        $this->save();
    }

    /**
     * Append an API call entry to the log.
     */
    public function logApiCall(string $step, string $method, string $url, array $requestPayload, mixed $responseBody, int $statusCode): void
    {
        $log   = $this->api_call_log ?? [];
        $log[] = [
            'step'     => $step,
            'method'   => $method,
            'url'      => $url,
            'request'  => $requestPayload,
            'response' => is_array($responseBody) ? $responseBody : json_decode((string) $responseBody, true),
            'status'   => $statusCode,
            'at'       => now()->toDateTimeString(),
        ];

        $this->api_call_log = $log;
        $this->save();
    }
}
