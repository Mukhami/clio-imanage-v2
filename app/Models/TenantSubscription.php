<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantSubscription extends Model
{
    use HasFactory;
    protected $fillable = [
        'tenant_id',
        'reference',
        'status',
        'start_date',
        'end_date',
        'clio_users_at_start',
        'plan_type',
        'notes',
        'voided_at',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'status'     => SubscriptionStatus::class,
            'start_date' => 'date',
            'end_date'   => 'date',
            'voided_at'  => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function tenantSubscriptionReminders(): HasMany
    {
        return $this->hasMany(TenantSubscriptionReminder::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query): void
    {
        $query->where('status', SubscriptionStatus::Active);
    }

    public function scopeExpired($query): void
    {
        $query->where('status', SubscriptionStatus::Expired);
    }

    public function scopeExpiringWithin($query, int $days): void
    {
        $query->where('status', SubscriptionStatus::Active)
              ->where('end_date', '<=', now()->addDays($days))
              ->where('end_date', '>=', now());
    }
}
