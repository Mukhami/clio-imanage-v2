<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $roleFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'tenant'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', fn ($q) => $q->where('name', $this->roleFilter));
            })
            ->orderBy('name')
            ->paginate(20);
    }

    public function lockUser(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['locked_until' => now()->addYears(10)]);
        Flux::toast(text: 'User locked.', variant: 'warning');
        unset($this->users);
    }

    public function unlockUser(int $id): void
    {
        $user = User::findOrFail($id);
        $user->update(['locked_until' => null]);
        Flux::toast(text: 'User unlocked.', variant: 'success');
        unset($this->users);
    }

    public function render(): View
    {
        return view('livewire.admin.users.index');
    }
}
