<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ImanageWorkspace extends Model
{
    protected $fillable = [
        'tenant_id', 'library_id', 'imanage_template_id', 'imanage_matter_id', 'imanage_client_id',
        'iman_practice_area_id', 'iman_sub_practice_area_id', 'webhook_request_id',
        'imanage_workspace_id', 'name', 'description', 'database', 'default_security',
        'has_subfolders', 'owner', 'document_number', 'is_declared', 'is_hipaa', 'iwl', 'replica',
        'custom1',  'custom2',  'custom3',  'custom4',  'custom5',
        'custom6',  'custom7',  'custom8',  'custom9',  'custom10',
        'custom11', 'custom12', 'custom13', 'custom14', 'custom15',
        'custom16', 'custom17', 'custom18', 'custom19', 'custom20',
        'custom21', 'custom22', 'custom23', 'custom24', 'custom25',
        'custom26', 'custom27', 'custom28', 'custom29', 'custom30',
    ];

    protected function casts(): array
    {
        return [
            'has_subfolders' => 'boolean',
            'is_declared'    => 'boolean',
            'is_hipaa'       => 'boolean',
            'replica'        => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function library(): BelongsTo
    {
        return $this->belongsTo(Library::class);
    }

    public function imanageTemplate(): BelongsTo
    {
        return $this->belongsTo(ImanageTemplate::class, 'imanage_template_id');
    }

    public function imanageMatter(): BelongsTo
    {
        return $this->belongsTo(ImanageMatter::class, 'imanage_matter_id');
    }

    public function imanageClient(): BelongsTo
    {
        return $this->belongsTo(ImanageClient::class, 'imanage_client_id');
    }

    public function imanagePracticeArea(): BelongsTo
    {
        return $this->belongsTo(ImanagePracticeArea::class, 'iman_practice_area_id');
    }

    public function imanageSubPracticeArea(): BelongsTo
    {
        return $this->belongsTo(ImanageSubPracticeArea::class, 'iman_sub_practice_area_id');
    }

    public function securityAudit(): HasOne
    {
        return $this->hasOne(WorkspaceSecurityAudit::class, 'imanage_workspace_id');
    }
}
