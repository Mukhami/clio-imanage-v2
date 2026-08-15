<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClioClient extends Model
{
    protected $fillable = [
        'tenant_id', 'clio_id', 'client_id', 'etag', 'name', 'first_name',
        'last_name', 'type', 'initials', 'sequence_key', 'sequence_number',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioMatters(): HasMany
    {
        return $this->hasMany(ClioMatter::class, 'clio_client_id');
    }

    public function imanageClients(): HasMany
    {
        return $this->hasMany(ImanageClient::class, 'clio_client_id');
    }

    public function userMappings(): HasMany
    {
        return $this->hasMany(UserMapping::class, 'clio_user_id');
    }
}
