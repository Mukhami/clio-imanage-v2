<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Library;

class TenantSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'library_id',
        'imanage_template_id',
        'default_hipaa',
        'default_enabled',
        'has_replica_workspaces',
        'replica_template_id',
        'has_workspace_link_custom_field',
        'workspace_link_custom_field_name',
    ];

    protected function casts(): array
    {
        return [
            'default_hipaa'                   => 'boolean',
            'default_enabled'                 => 'boolean',
            'has_replica_workspaces'          => 'boolean',
            'has_workspace_link_custom_field' => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }
}
