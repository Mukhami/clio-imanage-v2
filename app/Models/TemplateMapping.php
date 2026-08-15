<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateMapping extends Model
{
    protected $fillable = ['tenant_id', 'clio_practice_area_id', 'imanage_template_id'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ClioPracticeArea::class, 'clio_practice_area_id');
    }

    public function imanageTemplate(): BelongsTo
    {
        return $this->belongsTo(ImanageTemplate::class, 'imanage_template_id');
    }
}
