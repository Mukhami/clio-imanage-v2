<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClioOAuthAccessCode extends Model
{
    protected $table = 'clio_oauth_access_codes';

    public $timestamps = false;

    public static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });
    }

    protected $fillable = ['tenant_id', 'code', 'redirect_uri', 'expires_at'];

    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'created_at'  => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
