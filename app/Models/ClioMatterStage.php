<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClioMatterStage extends Model
{
    protected $fillable = ['tenant_id', 'clio_id', 'name', 'display_order', 'clio_practice_area_id'];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ClioPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ClioPracticeArea::class, 'clio_practice_area_id');
    }
}
