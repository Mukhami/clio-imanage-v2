<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Enquiries;

use App\Models\Enquiry;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Enquiry $enquiry;

    public function mount(int $id): void
    {
        $this->enquiry = Enquiry::findOrFail($id);
    }

    public function updateStatus(string $status): void
    {
        abort_unless(in_array($status, ['pending', 'contacted', 'onboarded', 'declined']), 422);

        $this->enquiry->update(['status' => $status]);
        $this->enquiry->refresh();
    }

    public function render(): View
    {
        return view('livewire.admin.enquiries.show');
    }
}
