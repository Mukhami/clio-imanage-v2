<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Library extends Model
{
    protected $fillable = ['tenant_id', 'imanage_library_id', 'name', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function imanagePracticeAreas(): HasMany
    {
        return $this->hasMany(ImanagePracticeArea::class);
    }

    public function imanageSubPracticeAreas(): HasMany
    {
        return $this->hasMany(ImanageSubPracticeArea::class);
    }

    public function imanageTemplates(): HasMany
    {
        return $this->hasMany(ImanageTemplate::class);
    }

    public function imanageGroups(): HasMany
    {
        return $this->hasMany(ImanageGroup::class);
    }

    public function imanageUsers(): HasMany
    {
        return $this->hasMany(ImanageUser::class);
    }

    public function imanageClients(): HasMany
    {
        return $this->hasMany(ImanageClient::class);
    }

    public function imanageMatters(): HasMany
    {
        return $this->hasMany(ImanageMatter::class);
    }

    public function imanageWorkspaces(): HasMany
    {
        return $this->hasMany(ImanageWorkspace::class);
    }
}
