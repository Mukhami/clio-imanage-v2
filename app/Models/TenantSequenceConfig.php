<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSequenceConfig extends Model
{
    protected $fillable = [
        'tenant_id',
        'client_prefix',
        'client_start_number',
        'client_current_number',
        'client_digits',
        'client_custom_field_name',
        'matter_prefix',
        'matter_start_number',
        'matter_current_number',
        'matter_digits',
        'matter_custom_field_name',
    ];

    protected function casts(): array
    {
        return [
            'client_start_number'   => 'integer',
            'client_current_number' => 'integer',
            'client_digits'         => 'integer',
            'matter_start_number'   => 'integer',
            'matter_current_number' => 'integer',
            'matter_digits'         => 'integer',
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
