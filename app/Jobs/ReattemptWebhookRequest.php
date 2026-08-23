<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ProcessingStage;
use App\Models\WebhookRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReattemptWebhookRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly ?int $reattemptedByUserId = null,
    ) {
    }

    public function backoff(): array
    {
        return [30];
    }

    public function handle(): void
    {
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);

        // Mark as reattempted
        $wr->reattempted    = true;
        $wr->reattempted_by = $this->reattemptedByUserId;
        $wr->reattempted_at = now();
        $wr->save();

        // Reset to Enqueued so downstream jobs see the correct starting state
        $wr->processing_stage = ProcessingStage::Enqueued;
        $wr->save();

        // Determine restart point based on activity flags
        if (! $wr->client_activity_complete || ! $wr->workspace_activity_complete) {
            UpdateMatter::dispatch($wr->id, $wr->tenant_id);
        } elseif (! $wr->folder_activity_complete) {
            CreateWorkspaceFolders::dispatch($wr->id, $wr->tenant_id);
        } elseif (! $wr->security_activity_complete) {
            PostWorkspaceSecurity::dispatch($wr->id, $wr->tenant_id);
        } elseif (! $wr->workspace_link_custom_field_populated) {
            PopulateWorkspaceLinkCustomField::dispatch($wr->id);
        } else {
            $wr->advanceTo(ProcessingStage::Completed);
            $wr->completed_at = now();
            $wr->save();
        }
    }
}
