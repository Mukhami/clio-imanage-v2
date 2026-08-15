<?php

namespace App\Models;

use App\Enums\ReminderType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscriptionReminder extends Model
{
    protected $table = 'tenant_subscription_reminders';

    public $timestamps = false;

    public $updatedAt = false;

    protected $fillable = [
        'tenant_subscription_id',
        'reminder_type',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'reminder_type' => ReminderType::class,
            'sent_at'       => 'datetime',
            'created_at'    => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenantSubscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class);
    }
}
