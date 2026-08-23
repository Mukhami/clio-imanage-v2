<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Enquiry as EnquiryModel;
use App\Models\User;
use App\Notifications\NewEnquiry;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class Enquiry extends Component
{
    public string $firm_name    = '';
    public string $contact_name = '';
    public string $email        = '';
    public string $phone        = '';
    public string $firm_size    = '';
    public string $clio_region  = '';
    public string $notes        = '';

    public bool $submitted = false;

    public function submit(): void
    {
        $this->validate([
            'firm_name'    => ['required', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email'        => ['required', 'email', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'firm_size'    => ['required', 'in:solo,small,mid,large,enterprise'],
            'clio_region'  => ['required', 'in:us,ca,eu,au,uk'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ]);

        $enquiry = EnquiryModel::create([
            'firm_name'    => $this->firm_name,
            'contact_name' => $this->contact_name,
            'email'        => $this->email,
            'phone'        => $this->phone ?: null,
            'firm_size'    => $this->firm_size,
            'clio_region'  => $this->clio_region,
            'notes'        => $this->notes ?: null,
        ]);

        $admins = User::role(['Super Admin', 'Admin'])->get();
        Notification::send($admins, new NewEnquiry($enquiry));

        $this->submitted = true;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.enquiry')
            ->layout('layouts.auth.simple', [
                'title'       => 'Request Access',
                'description' => 'Request access to MatterLynk — the automated Clio to iManage integration platform. Eliminate manual workspace creation, enforce security mapping, and keep your matters in sync.',
                'robots'      => 'index, follow',
                'canonical'   => config('app.url').'/enquiry',
            ]);
    }
}
