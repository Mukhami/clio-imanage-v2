<?php

declare(strict_types=1);

namespace App\Livewire\Admin\WebhookRequests;

use App\Jobs\ReattemptWebhookRequest;
use App\Models\WebhookRequest;
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

        session()->flash('success', 'Reattempt queued successfully.');
    }

    public function render(): View
    {
        return view('livewire.admin.webhook-requests.show');
    }
}
