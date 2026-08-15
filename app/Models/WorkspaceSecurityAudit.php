<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceSecurityAudit extends Model
{
    protected $fillable = [
        'tenant_id', 'webhook_request_id', 'imanage_workspace_id',
        'template_workspace_id', 'target_workspace_id',
        'template_security', 'target_security', 'diff',
        'status', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'template_security' => 'array',
            'target_security'   => 'array',
            'diff'              => 'array',
            'resolved_at'       => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function imanageWorkspace(): BelongsTo
    {
        return $this->belongsTo(ImanageWorkspace::class, 'imanage_workspace_id');
    }
}
