<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PracticeAreaMapping extends Model
{
    protected $fillable = [
        'tenant_id', 'clio_practice_area_id', 'imanage_practice_area_id',
        'imanage_sub_practice_area_id', 'imanage_custom_field_config_id',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function clioPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ClioPracticeArea::class, 'clio_practice_area_id');
    }

    public function imanagePracticeArea(): BelongsTo
    {
        return $this->belongsTo(ImanagePracticeArea::class, 'imanage_practice_area_id');
    }

    public function imanageSubPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ImanageSubPracticeArea::class, 'imanage_sub_practice_area_id');
    }

    public function imanageCustomFieldConfig(): BelongsTo
    {
        return $this->belongsTo(ImanageCustomFieldConfig::class, 'imanage_custom_field_config_id');
    }
}
