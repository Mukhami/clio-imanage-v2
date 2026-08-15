<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMapping extends Model
{
    protected $fillable = ['tenant_id', 'clio_user_id', 'imanage_user_id'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioUser(): BelongsTo
    {
        return $this->belongsTo(ClioUser::class, 'clio_user_id');
    }

    public function imanageUser(): BelongsTo
    {
        return $this->belongsTo(ImanageUser::class, 'imanage_user_id');
    }
}
