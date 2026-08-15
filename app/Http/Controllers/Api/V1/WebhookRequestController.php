<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\ProcessingStage;
use App\Http\Controllers\Controller;
use App\Jobs\ReattemptWebhookRequest;
use App\Models\Tenant;
use App\Models\WebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookRequestController extends Controller
{
    private function isAdmin(Request $request): bool
    {
        return $request->user()->hasRole(['super_admin', 'admin']);
    }

    private function authorise(Request $request, Tenant $tenant): void
    {
        $isAdmin      = $this->isAdmin($request);
        $isTenantUser = $request->user()->tenant_id === $tenant->id;

        abort_if(! $isAdmin && ! $isTenantUser, 403);
    }

    public function index(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorise($request, $tenant);

        $query = $tenant->webhookRequests()->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('stage')) {
            $stage = ProcessingStage::from($request->input('stage'));
            $query->where('processing_stage', $stage);
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('client_id')) {
            $query->where('retrieved_client_id', $request->input('client_id'));
        }

        if ($request->filled('matter_id')) {
            $query->where('retrieved_matter_id', $request->input('matter_id'));
        }

        if ($request->filled('correlation_id')) {
            $query->where('correlation_id', $request->input('correlation_id'));
        }

        $requests = $query->paginate(25);

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'total'        => $requests->total(),
                'per_page'     => 25,
                'current_page' => $requests->currentPage(),
            ],
        ]);
    }

    public function show(Request $request, Tenant $tenant, WebhookRequest $webhookRequest): JsonResponse
    {
        $this->authorise($request, $tenant);

        abort_if($webhookRequest->tenant_id !== $tenant->id, 404);

        if (! $this->isAdmin($request)) {
            $data = $webhookRequest->makeHidden(['headers', 'body'])->toArray();
        } else {
            $data = $webhookRequest->toArray();
        }

        return response()->json(['data' => $data]);
    }

    public function reattempt(Request $request, Tenant $tenant, WebhookRequest $webhookRequest): JsonResponse
    {
        abort_if(! $this->isAdmin($request), 403);
        abort_if($webhookRequest->tenant_id !== $tenant->id, 404);

        $reattemptable = in_array($webhookRequest->processing_stage, [
            ProcessingStage::Failed,
            ProcessingStage::Skipped,
        ], true);

        abort_if(! $reattemptable, 422, 'Webhook request is not in a reattemptable state.');

        ReattemptWebhookRequest::dispatch($webhookRequest->id, $request->user()->id)
            ->onQueue('webhooks');

        return response()->json(['message' => 'Reattempt queued successfully']);
    }
}
