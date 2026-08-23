<?php

declare(strict_types=1);

namespace App\Livewire\Portal;

use App\Models\User;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Notifications\UserInvited;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|unique:users,email')]
    public string $email = '';

    #[Validate('required|in:Tenant Admin,Tenant Viewer')]
    public string $role = 'Tenant Viewer';

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('Tenant Admin'), 403);
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->with('roles')
            ->orderBy('name')
            ->paginate(20);
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'email', 'role']);
        $this->role = 'Tenant Viewer';
        $this->showCreateModal = true;
    }

    public function suspendUser(int $userId): void
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($userId);

        abort_if($user->id === auth()->id(), 403, 'You cannot suspend your own account.');

        $user->update(['suspended_at' => now()]);

        unset($this->users);

        Flux::toast(text: "{$user->name} has been suspended.", variant: 'warning');
    }

    public function reactivateUser(int $userId): void
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($userId);

        $user->update(['suspended_at' => null]);

        unset($this->users);

        Flux::toast(text: "{$user->name} has been reactivated.", variant: 'success');
    }

    public function resendInvite(int $userId): void
    {
        $user = User::where('tenant_id', auth()->user()->tenant_id)->findOrFail($userId);

        $token    = Password::createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));

        $user->notify(new UserInvited($user->tenant, $resetUrl));

        Flux::toast(text: "Invite resent to {$user->email}.", variant: 'success');
    }

    public function createUser(): void
    {
        $this->validate();

        $user = User::create([
            'name'               => $this->name,
            'email'              => $this->email,
            'password'           => Hash::make(str()->random(32)),
            'tenant_id'          => auth()->user()->tenant_id,
            'email_verified_at'  => now(),
        ]);

        $user->assignRole($this->role);

        $token = Password::createToken($user);
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email], false));

        $user->notify(new UserInvited($user->tenant, $resetUrl));

        $this->showCreateModal = false;
        $this->reset(['name', 'email', 'role']);

        unset($this->users);

        Flux::toast(text: 'User invited. A password-setup link has been emailed to them.', variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.portal.users', [
            'users' => $this->users,
        ]);
    }
}
