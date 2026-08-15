<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanageUser extends Model
{
    protected $fillable = ['tenant_id', 'library_id', 'imanage_user_id', 'full_name', 'email'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function userMappings(): HasMany
    {
        return $this->hasMany(UserMapping::class, 'imanage_user_id');
    }
}
