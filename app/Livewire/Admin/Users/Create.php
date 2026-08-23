<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Notifications\UserInvited;
use Flux\Flux;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'Admin';

    #[Computed]
    public function roles(): array
    {
        return ['Admin', 'Support'];
    }

    public function save(): void
    {
        $this->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role'  => 'required|in:Admin,Support',
        ]);

        $user = User::create([
            'name'              => $this->name,
            'email'             => $this->email,
            'password'          => Hash::make(Str::random(32)),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($this->role);

        $token    = Password::broker()->createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $user->notify(new UserInvited(null, $resetUrl));

        Flux::toast(text: 'User invited successfully. They will receive an email to set their password.', variant: 'success');

        $this->redirect(route('admin.users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.users.create');
    }
}
