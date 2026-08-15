<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanageClient extends Model
{
    protected $fillable = [
        'tenant_id', 'library_id', 'webhook_request_id', 'clio_client_id',
        'key', 'key_number', 'ssid', 'description', 'enabled', 'hipaa',
        'wstype', 'sequence_number', 'sequence_key',
    ];

    protected function casts(): array
    {
        return [
            'enabled'         => 'boolean',
            'hipaa'           => 'boolean',
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

    public function clioClient(): BelongsTo
    {
        return $this->belongsTo(ClioClient::class, 'clio_client_id');
    }

    public function imanageMatters(): HasMany
    {
        return $this->hasMany(ImanageMatter::class, 'imanage_client_id');
    }

    public function imanageWorkspaces(): HasMany
    {
        return $this->hasMany(ImanageWorkspace::class, 'imanage_client_id');
    }
}
