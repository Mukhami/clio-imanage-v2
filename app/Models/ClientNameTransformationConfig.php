<?php

namespace App\Models;

use App\Enums\ClientNameStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNameTransformationConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'strategy',
        'template_pattern',
        'apply_to_persons_only',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'strategy'              => ClientNameStrategy::class,
            'apply_to_persons_only' => 'boolean',
            'enabled'               => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
