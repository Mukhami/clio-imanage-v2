<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Roles;

use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    #[Computed]
    public function roles(): Collection
    {
        return Role::with('permissions')->orderBy('name')->get();
    }

    #[Computed]
    public function permissions(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    public function render(): View
    {
        return view('livewire.admin.roles.index');
    }
}
