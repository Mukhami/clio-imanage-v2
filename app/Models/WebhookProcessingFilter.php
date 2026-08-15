<?php

namespace App\Models;

use App\Enums\FilterAction;
use App\Enums\FilterOperator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookProcessingFilter extends Model
{
    protected $fillable = [
        'tenant_id',
        'field_path',
        'operator',
        'value',
        'action',
        'priority',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'operator' => FilterOperator::class,
            'action'   => FilterAction::class,
            'priority' => 'integer',
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

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeEnabled($query): void
    {
        $query->where('enabled', true);
    }

    public function scopeByPriority($query): void
    {
        $query->orderBy('priority', 'desc');
    }
}
