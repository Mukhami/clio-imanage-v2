<?php

namespace App\Models;

use App\Enums\CustomFieldSourceType;
use App\Enums\ValueMappingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomFieldMappingRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'source_type',
        'source_field_name',
        'imanage_custom_field_config_id',
        'value_mapping_type',
        'static_value',
        'date_format',
        'priority',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'source_type'        => CustomFieldSourceType::class,
            'value_mapping_type' => ValueMappingType::class,
            'priority'           => 'integer',
            'enabled'            => 'boolean',
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
