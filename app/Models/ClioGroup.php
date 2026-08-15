<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClioGroup extends Model
{
    protected $fillable = ['tenant_id', 'clio_id', 'name', 'type', 'etag'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function groupMapping(): HasOne
    {
        return $this->hasOne(GroupMapping::class, 'clio_group_id');
    }
}
