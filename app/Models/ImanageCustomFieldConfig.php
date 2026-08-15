<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImanageCustomFieldConfig extends Model
{
    protected $fillable = ['tenant_id', 'custom_field_identifier', 'description'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function imanageCustomFields(): HasMany
    {
        return $this->hasMany(ImanageCustomField::class, 'imanage_custom_field_config_id');
    }

    public function customFieldMappingRules(): HasMany
    {
        return $this->hasMany(CustomFieldMappingRule::class, 'imanage_custom_field_config_id');
    }
}
