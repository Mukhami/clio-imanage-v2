<?php

namespace App\Models;

use App\Enums\MatterDescriptionStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatterDescriptionTransformationConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'strategy',
        'source_field',
        'template_pattern',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'strategy' => MatterDescriptionStrategy::class,
            'enabled'  => 'boolean',
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
