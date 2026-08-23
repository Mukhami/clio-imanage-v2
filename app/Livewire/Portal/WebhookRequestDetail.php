<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Models\WebhookRequest;
use Illuminate\View\View;
use Livewire\Component;

class WebhookRequestDetail extends Component
{
    public WebhookRequest $webhookRequest;

    public function mount(WebhookRequest $webhookRequest): void
    {
        abort_unless((int) $webhookRequest->tenant_id === (int) auth()->user()->tenant_id, 403);
        $this->webhookRequest = $webhookRequest;
    }

    public function render(): View
    {
        return view('livewire.portal.webhook-request-detail');
    }
}
