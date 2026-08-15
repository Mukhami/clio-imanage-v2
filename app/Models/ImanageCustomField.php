<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImanageCustomField extends Model
{
    protected $fillable = ['tenant_id', 'imanage_custom_field_config_id', 'key', 'description', 'wstype'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(ImanageCustomFieldConfig::class, 'imanage_custom_field_config_id');
    }
}
