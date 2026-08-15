<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanageGroup extends Model
{
    protected $fillable = ['tenant_id', 'library_id', 'imanage_group_id', 'name'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function groupMappings(): HasMany
    {
        return $this->hasMany(GroupMapping::class, 'imanage_group_id');
    }
}
