<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'clio_id',
        'webhook_type_id',
        'url',
        'shared_secret',
        'status',
        'expires_at',
        'etag',
    ];

    protected function casts(): array
    {
        return [
            'status'        => WebhookStatus::class,
            'expires_at'    => 'datetime',
            'shared_secret' => 'encrypted',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function webhookType(): BelongsTo
    {
        return $this->belongsTo(WebhookType::class);
    }

    public function webhookRequests(): HasMany
    {
        return $this->hasMany(WebhookRequest::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query): void
    {
        $query->where('status', WebhookStatus::Active);
    }
}
