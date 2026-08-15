<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClioMatter extends Model
{
    protected $fillable = [
        'tenant_id', 'clio_id', 'clio_client_id', 'clio_practice_area_id',
        'matter_id', 'etag', 'display_number', 'custom_number', 'description',
        'status', 'location', 'client_reference', 'open_date', 'close_date',
        'pending_date', 'json_data', 'sequence_key', 'sequence_number',
    ];

    protected function casts(): array
    {
        return [
            'open_date'       => 'date',
            'close_date'      => 'date',
            'pending_date'    => 'date',
            'json_data'       => 'array',
            'sequence_number' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioClient(): BelongsTo
    {
        return $this->belongsTo(ClioClient::class, 'clio_client_id');
    }

    public function ClioPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ClioPracticeArea::class, 'clio_practice_area_id');
    }

    public function imanageMatters(): HasMany
    {
        return $this->hasMany(ImanageMatter::class, 'clio_matter_id');
    }
}
