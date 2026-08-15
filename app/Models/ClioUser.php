<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClioUser extends Model
{
    protected $fillable = [
        'tenant_id', 'clio_id', 'name', 'email', 'first_name',
        'last_name', 'initials', 'enabled', 'time_zone', 'subscription_type',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function userMapping(): HasOne
    {
        return $this->hasOne(UserMapping::class, 'clio_user_id');
    }
}
