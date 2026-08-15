<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebhookType extends Model
{
    protected $fillable = [
        'name',
        'model',
        'event',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
