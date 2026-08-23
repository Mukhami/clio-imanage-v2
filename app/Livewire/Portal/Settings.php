<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use Illuminate\View\View;
use Livewire\Component;

class Settings extends Component
{
    public function render(): View
    {
        $tenant = auth()->user()->tenant()
            ->with([
                'clioLocation',
                'tenantSubscriptions' => fn ($q) => $q->active()->latest('end_date')->limit(1),
            ])
            ->first();

        $subscription = $tenant?->tenantSubscriptions->first();

        return view('livewire.portal.settings', compact('tenant', 'subscription'));
    }
}
