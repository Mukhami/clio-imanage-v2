<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupMapping extends Model
{
    protected $fillable = ['tenant_id', 'clio_group_id', 'imanage_group_id'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioGroup(): BelongsTo
    {
        return $this->belongsTo(ClioGroup::class, 'clio_group_id');
    }

    public function imanageGroup(): BelongsTo
    {
        return $this->belongsTo(ImanageGroup::class, 'imanage_group_id');
    }
}
