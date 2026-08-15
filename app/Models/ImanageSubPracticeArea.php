<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImanageSubPracticeArea extends Model
{
    protected $fillable = ['tenant_id', 'library_id', 'imanage_practice_area_id', 'key', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function practiceArea(): BelongsTo
    {
        return $this->belongsTo(ImanagePracticeArea::class, 'imanage_practice_area_id');
    }
}
