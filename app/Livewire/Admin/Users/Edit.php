<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Notifications\UserRoleChanged;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public string $selectedRole = '';

    public function mount(int $id): void
    {
        $this->user = User::with(['roles', 'tenant'])->findOrFail($id);

        $this->name         = $this->user->name;
        $this->email        = $this->user->email;
        $this->selectedRole = $this->user->roles->first()?->name ?? '';
    }

    #[Computed]
    public function availableRoles(): array
    {
        return Role::where('name', '!=', 'Super Admin')
            ->orderBy('name')
            ->pluck('name')
            ->toArray();
    }

    public function save(): void
    {
        $this->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,' . $this->user->id,
            'selectedRole' => 'required|string|exists:roles,name',
        ]);

        $previousRole = $this->user->roles->first()?->name;

        $this->user->update([
            'name'  => $this->name,
            'email' => $this->email,
        ]);

        if ($previousRole !== $this->selectedRole) {
            $this->user->syncRoles([$this->selectedRole]);

            $this->user->notify(new UserRoleChanged($this->selectedRole, auth()->user()));

            // Notify Super Admin + Admin users (excluding self)
            $admins = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin']))
                ->where('id', '!=', auth()->id())
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new UserRoleChanged($this->selectedRole, auth()->user()));
            }
        }

        Flux::toast(text: 'User updated successfully.', variant: 'success');

        $this->redirect(route('admin.users.show', $this->user->id), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.edit');
    }
}
