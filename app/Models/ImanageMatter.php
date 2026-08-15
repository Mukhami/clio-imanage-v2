<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanageMatter extends Model
{
    protected $fillable = [
        'tenant_id', 'library_id', 'imanage_client_id', 'webhook_request_id',
        'clio_client_id', 'clio_matter_id', 'clio_practice_area_id',
        'key', 'key_numeric', 'ssid', 'description', 'enabled', 'hipaa', 'closed',
        'wstype', 'iman_practice_area_id', 'iman_sub_practice_area_id',
        'parent_id', 'parent_ssid', 'sequence_number', 'sequence_key',
    ];

    protected function casts(): array
    {
        return [
            'enabled'         => 'boolean',
            'hipaa'           => 'boolean',
            'closed'          => 'boolean',
            'key_numeric'     => 'integer',
            'sequence_number' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function imanageClient(): BelongsTo
    {
        return $this->belongsTo(ImanageClient::class, 'imanage_client_id');
    }

    public function clioMatter(): BelongsTo
    {
        return $this->belongsTo(ClioMatter::class, 'clio_matter_id');
    }

    public function ClioPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ClioPracticeArea::class, 'clio_practice_area_id');
    }

    public function imanagePracticeArea(): BelongsTo
    {
        return $this->belongsTo(ImanagePracticeArea::class, 'iman_practice_area_id');
    }

    public function imanageSubPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ImanageSubPracticeArea::class, 'iman_sub_practice_area_id');
    }

    public function imanageWorkspaces(): HasMany
    {
        return $this->hasMany(ImanageWorkspace::class, 'imanage_matter_id');
    }
}
