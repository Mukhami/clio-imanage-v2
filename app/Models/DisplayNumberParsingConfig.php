<?php

namespace App\Models;

use App\Enums\ParsingStrategy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisplayNumberParsingConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'strategy',
        'delimiter',
        'secondary_delimiter',
        'client_position',
        'matter_position',
        'regex_pattern',
        'client_capture_group',
        'matter_capture_group',
        'pre_processing_rules',
        'post_processing_rules',
        'validation_regex',
        'matter_status_filter',
        'custom_field_name',
        'fallback_strategy',
        'fallback_config',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'strategy'              => ParsingStrategy::class,
            'client_position'       => 'integer',
            'matter_position'       => 'integer',
            'pre_processing_rules'  => 'array',
            'post_processing_rules' => 'array',
            'fallback_config'       => 'array',
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
