<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\View\View;
use Livewire\Component;

class Settings extends Component
{
    public function render(): View
    {
        $tenant = auth()->user()->tenant()->with('clioLocation')->first();
        $subscription = $tenant?->tenantSubscriptions()->active()->latest('end_date')->first();

        return view('livewire.portal.settings', compact('tenant', 'subscription'));
    }
}
