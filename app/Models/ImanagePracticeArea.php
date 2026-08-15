<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanagePracticeArea extends Model
{
    protected $fillable = ['tenant_id', 'library_id', 'key', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function subPracticeAreas(): HasMany
    {
        return $this->hasMany(ImanageSubPracticeArea::class, 'imanage_practice_area_id');
    }

    public function practiceAreaMappings(): HasMany
    {
        return $this->hasMany(PracticeAreaMapping::class, 'imanage_practice_area_id');
    }
}
