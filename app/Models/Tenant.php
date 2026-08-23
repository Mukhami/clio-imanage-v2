<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'reference',
        'status',
        'clio_location_id',
        'clio_app_id',
        'clio_app_secret',
        'imanage_cloud_url',
        'imanage_customer_id',
        'imanage_app_id',
        'imanage_app_secret',
        'imanage_username',
        'imanage_password',
        'password_authentication',
        'has_group_security_mapping',
        'enable_workspace_link_custom_field',
        'owner_id',
        'onboarded_at',
    ];

    protected function casts(): array
    {
        return [
            'status'                             => TenantStatus::class,
            'password_authentication'            => 'boolean',
            'has_group_security_mapping'         => 'boolean',
            'enable_workspace_link_custom_field' => 'boolean',
            'onboarded_at'                       => 'datetime',
            'clio_app_id'                        => 'encrypted',
            'clio_app_secret'                    => 'encrypted',
            'imanage_app_id'                     => 'encrypted',
            'imanage_app_secret'                 => 'encrypted',
            'imanage_username'                   => 'encrypted',
            'imanage_password'                   => 'encrypted',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function clioLocation(): BelongsTo
    {
        return $this->belongsTo(ClioLocation::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tenantSetting(): HasOne
    {
        return $this->hasOne(TenantSetting::class);
    }

    public function tenantSequenceConfig(): HasOne
    {
        return $this->hasOne(TenantSequenceConfig::class);
    }

    public function displayNumberParsingConfig(): HasOne
    {
        return $this->hasOne(DisplayNumberParsingConfig::class);
    }

    public function clientNameTransformationConfig(): HasOne
    {
        return $this->hasOne(ClientNameTransformationConfig::class);
    }

    public function matterDescriptionTransformationConfig(): HasOne
    {
        return $this->hasOne(MatterDescriptionTransformationConfig::class);
    }

    public function workspaceNamingConfig(): HasOne
    {
        return $this->hasOne(WorkspaceNamingConfig::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function tenantSubscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function tenantJobLocks(): HasMany
    {
        return $this->hasMany(TenantJobLock::class);
    }

    public function legacyAliasMappings(): HasMany
    {
        return $this->hasMany(LegacyAliasMapping::class);
    }

    public function webhookProcessingFilters(): HasMany
    {
        return $this->hasMany(WebhookProcessingFilter::class);
    }

    public function customFieldMappingRules(): HasMany
    {
        return $this->hasMany(CustomFieldMappingRule::class);
    }

    public function clioOAuthAccessTokens(): HasMany
    {
        return $this->hasMany(ClioOAuthAccessToken::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function libraries(): HasMany
    {
        return $this->hasMany(Library::class);
    }

    public function clioPracticeAreas(): HasMany
    {
        return $this->hasMany(ClioPracticeArea::class);
    }

    public function clioUsers(): HasMany
    {
        return $this->hasMany(ClioUser::class);
    }

    public function clioGroups(): HasMany
    {
        return $this->hasMany(ClioGroup::class);
    }

    public function clioMatters(): HasMany
    {
        return $this->hasMany(ClioMatter::class);
    }

    public function clioClients(): HasMany
    {
        return $this->hasMany(ClioClient::class);
    }

    public function imanageOAuthAccessTokens(): HasMany
    {
        return $this->hasMany(ImanageOAuthAccessToken::class);
    }

    public function practiceAreaMappings(): HasMany
    {
        return $this->hasMany(PracticeAreaMapping::class);
    }

    public function templateMappings(): HasMany
    {
        return $this->hasMany(TemplateMapping::class);
    }

    public function groupMappings(): HasMany
    {
        return $this->hasMany(GroupMapping::class);
    }

    public function userMappings(): HasMany
    {
        return $this->hasMany(UserMapping::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function webhookRequests(): HasMany
    {
        return $this->hasMany(WebhookRequest::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query): void
    {
        $query->where('status', TenantStatus::Active);
    }

    public function scopeByStatus($query, TenantStatus $status): void
    {
        $query->where('status', $status);
    }
}
