<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImanageOAuthAccessToken extends Model
{
    protected $fillable = ['tenant_id', 'access_token', 'refresh_token', 'expires_at', 'revoked'];

    protected function casts(): array
    {
        return [
            'access_token'  => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at'    => 'datetime',
            'revoked'       => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query): void
    {
        $query->where('revoked', false)->where('expires_at', '>', now());
    }
}
