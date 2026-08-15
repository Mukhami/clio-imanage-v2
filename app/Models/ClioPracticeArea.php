<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClioPracticeArea extends Model
{
    protected $fillable = ['tenant_id', 'clio_id', 'name', 'code', 'category', 'etag'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioMatters(): HasMany
    {
        return $this->hasMany(ClioMatter::class, 'clio_practice_area_id');
    }

    public function clioMatterStages(): HasMany
    {
        return $this->hasMany(ClioMatterStage::class, 'clio_practice_area_id');
    }

    public function practiceAreaMapping(): HasOne
    {
        return $this->hasOne(PracticeAreaMapping::class, 'clio_practice_area_id');
    }

    public function templateMapping(): HasOne
    {
        return $this->hasOne(TemplateMapping::class, 'clio_practice_area_id');
    }
}
