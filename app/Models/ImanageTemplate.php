<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanageTemplate extends Model
{
    protected $fillable = ['tenant_id', 'library_id', 'imanage_template_id', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function imanageWorkspaces(): HasMany
    {
        return $this->hasMany(ImanageWorkspace::class, 'imanage_template_id');
    }

    public function templateMappings(): HasMany
    {
        return $this->hasMany(TemplateMapping::class, 'imanage_template_id');
    }
}
