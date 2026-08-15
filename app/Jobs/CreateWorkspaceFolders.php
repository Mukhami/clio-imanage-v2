<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\WebhookRequest;
use App\Services\ImanageApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateWorkspaceFolders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public readonly int $webhookRequestId,
        public readonly int $tenantId,
    ) {
        $this->onQueue('long_term');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(ImanageApiService $imanage): void
    {
        // 1. Load required records
        $wr = WebhookRequest::findOrFail($this->webhookRequestId);
        $tenant = Tenant::findOrFail($this->tenantId);

        // 2. TODO: Copy folder structure from template workspace to target workspace.
        //
        //    Suggested approach:
        //    a. Resolve the template workspace via tenant configuration
        //       (e.g. $tenant->tenantSetting->template_workspace_id).
        //    b. Fetch the template's folder list via iManage API:
        //       $templateFolders = $imanage->getFolders($tenant->defaultLibraryId, $templateWorkspaceId);
        //    c. For each folder, recreate it under the target workspace:
        //       foreach ($templateFolders as $folder) {
        //           $imanage->createFolder($tenant->defaultLibraryId, $targetWorkspaceId, [
        //               'name'        => $folder['name'],
        //               'description' => $folder['description'] ?? '',
        //           ]);
        //       }
        //    Note: ImanageApiService will need getFolders() / createFolder() methods added.

        // 3. Mark folder activity complete
        $wr->folder_activity_complete = true;
        $wr->save();
    }
}
