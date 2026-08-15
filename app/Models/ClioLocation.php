<?php

namespace App\Models;

use App\Enums\ClioRegion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClioLocation extends Model
{
    protected $fillable = [
        'name',
        'region',
        'api_url',
        'app_url',
    ];

    protected function casts(): array
    {
        return [
            'region' => ClioRegion::class,
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
