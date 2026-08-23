<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WebhookRequests;

use App\Jobs\ReattemptWebhookRequest;
use App\Models\WebhookRequest;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public WebhookRequest $webhookRequest;

    public function mount(int $id): void
    {
        $this->webhookRequest = WebhookRequest::with('tenant')->findOrFail($id);
    }

    public function reattempt(): void
    {
        ReattemptWebhookRequest::dispatch($this->webhookRequest->id, Auth::id());

        Flux::toast(text: 'Reattempt queued successfully.', variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.webhook-requests.show');
    }
}
