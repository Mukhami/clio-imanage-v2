<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyAliasMapping extends Model
{
    protected $fillable = [
        'tenant_id',
        'entity_type',
        'clio_id',
        'imanage_alias',
        'imported_from',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
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

    public function scopeForTenant($query, int $tenantId): void
    {
        $query->where('tenant_id', $tenantId);
    }

    public function scopeByEntityType($query, string $entityType): void
    {
        $query->where('entity_type', $entityType);
    }
}
