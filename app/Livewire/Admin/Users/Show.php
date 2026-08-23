<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Show extends Component
{
    public User $user;

    public function mount(int $id): void
    {
        $this->user = User::with(['roles', 'tenant'])->findOrFail($id);
    }

    #[Computed]
    public function userRoles(): Collection
    {
        return $this->user->roles;
    }

    #[Computed]
    public function userPermissions(): Collection
    {
        return $this->user->getAllPermissions()->sortBy('name');
    }

    public function lockUser(): void
    {
        $this->user->update(['locked_until' => now()->addYears(10)]);
        $this->user->refresh();
        session()->flash('success', 'User locked.');
    }

    public function unlockUser(): void
    {
        $this->user->update(['locked_until' => null]);
        $this->user->refresh();
        session()->flash('success', 'User unlocked.');
    }

    public function render(): View
    {
        return view('livewire.admin.users.show');
    }
}
