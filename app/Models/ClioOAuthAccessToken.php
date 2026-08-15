<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClioOAuthAccessToken extends Model
{
    protected $fillable = [
        'tenant_id',
        'access_token',
        'refresh_token',
        'access_expires_at',
        'refresh_expires_at',
        'revoked',
    ];

    protected function casts(): array
    {
        return [
            'access_token'       => 'encrypted',
            'refresh_token'      => 'encrypted',
            'access_expires_at'  => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked'            => 'boolean',
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

    public function scopeActive($query): void
    {
        $query->where('revoked', false)
              ->where('access_expires_at', '>', now());
    }
}
