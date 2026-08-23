# Feature Requirements — Clio iManage V2

> Reference: V1 codebase at `/Users/kevinmukhami/Desktop/Projects/dev/iman-clio`  
> Stack: Laravel 13, Livewire 4, Flux 2.x, Saloon v4, Spatie Permission

---

## 1. OAuth Flows ✅ COMPLETE

### 1.1 Clio OAuth
**Routes:**
- `GET /admin/tenants/{id}/clio/authorize` — initiates OAuth, redirects to Clio (admin auth required)
- `GET /oauth/clio/callback` — public callback, exchanges code for token

**Flow:**
1. Admin clicks "Authorise with Clio" on tenant show page
2. Redirected to `{clio_location.app_url}/oauth/authorize` with `client_id`, `redirect_uri`, `state=tenant.reference`
3. Clio redirects back to callback with `code` + `state`
4. Callback: exchange code via `ExchangeAuthCode` Saloon request, revoke previous tokens, store new `ClioOAuthAccessToken`
5. Redirect to tenant show with success/error flash

**Token storage:** `clio_oauth_access_tokens` (encrypted `access_token`, `refresh_token`, `access_expires_at`, `refresh_expires_at`)

### 1.2 iManage OAuth
**Routes:**
- `GET /admin/tenants/{id}/imanage/authorize` — initiates OAuth (admin auth required)
- `GET /oauth/imanage/callback` — public callback

**Flow:** Same pattern as Clio; uses `{tenant.imanage_cloud_url}/auth/oauth2/authorize` and `/auth/oauth2/token`

**Note:** iManage also supports password auth (`password_authentication` flag). Show page hides OAuth button when password auth is enabled.

**Token storage:** `imanage_oauth_access_tokens` (encrypted `access_token`, `refresh_token`, `expires_at`)

**Env vars required:**
```
CLIO_REDIRECT_URI=https://yourdomain.com/oauth/clio/callback
IMANAGE_REDIRECT_URI=https://yourdomain.com/oauth/imanage/callback
```

---

## 2. Token Refresh (Scheduled)

### 2.1 Clio Token Refresh
**Command:** `app:refresh-clio-tokens` (stub exists at `app/Console/Commands/RefreshClioTokens.php`)

**Logic:**
- Find all active (non-revoked) Clio tokens expiring within 30 minutes
- For each: POST to `{clio_location.app_url}/oauth/token` with `grant_type=refresh_token`, `client_id`, `client_secret`, `refresh_token`
- On success: mark old token revoked, create new token
- On failure: log error, optionally notify admin

**Schedule:** Every 15 minutes via `bootstrap/app.php` schedule or `Kernel.php`

### 2.2 iManage Token Refresh
**Command:** `app:refresh-imanage-tokens` (stub exists at `app/Console/Commands/RefreshImanageTokens.php`)

**Logic:** Same pattern using `{tenant.imanage_cloud_url}/auth/oauth2/token` with `grant_type=refresh_token`

**Note:** iManage may not always return a `refresh_token`. Only attempt refresh if one exists.

---

## 3. Tenant Configuration Panel

**Route:** `GET /admin/tenants/{id}/config` → `Admin\Tenants\Config` Livewire component  
**Menu:** Add "Config" link on tenant show page

### 3.1 Default Matter Settings
**Model:** `TenantSetting` (belongs to `Tenant`)
**Fields:**
- `library_id` — FK to `libraries` — "Default iManage Library" (select, populated from synced libraries)
- `imanage_template_id` — FK to `imanage_templates` — "Default iManage Template" (select)
- `replica_template_id` — FK to `imanage_templates` — "Replica iManage Template" (select, optional)
- `default_hipaa` — boolean — "HIPAA Compliant by default"
- `default_enabled` — boolean — "Enable new workspaces by default"
- `has_replica_workspaces` — boolean — "Has replica workspaces"
- `workspace_link_custom_field_name` — string — "Workspace link custom field name in Clio"
- `has_workspace_link_custom_field` — boolean

**UI:** Single card with a save button. Dropdowns for library and templates populated from synced data.

### 3.2 Practice Area Mappings
**Model:** `PracticeAreaMapping` (belongs to Tenant via FK)
**Fields:** `clio_practice_area_id`, `imanage_practice_area_id`, `imanage_sub_practice_area_id` (nullable), `imanage_custom_field_id` (nullable)

**UI:**
- Table: Clio PA | iManage PA | Sub-PA | Custom Field | Delete
- Add mapping via Flux modal form:
  - Clio Practice Area (select, populated from synced `clio_practice_areas`)
  - iManage Practice Area (select, populated from synced `imanage_practice_areas`)
  - iManage Sub-Practice Area (optional select — dynamically loaded when parent PA is selected via Livewire)
  - iManage Custom Field (optional select, from `imanage_custom_fields`)
- Delete button per row

### 3.3 Template Mappings
**Model:** `TemplateMapping`
**Fields:** `clio_practice_area_id`, `imanage_template_id`

**UI:** Same pattern — table + modal form for Clio PA → iManage Template

### 3.4 Group Mappings
**Model:** `GroupMapping`
**Fields:** `clio_group_id`, `imanage_group_id`

**UI:** Table + modal form for Clio Group → iManage Group

### 3.5 User Mappings
**Model:** `UserMapping`
**Fields:** `clio_user_id`, `imanage_user_id`

**UI:** Table + modal form for Clio User → iManage User

### 3.6 iManage Custom Field Configuration
**Model:** `ImanageCustomFieldConfig`
**Fields:** `tenant_id`, `imanage_custom_field_id` (or identifier string `custom3`–`custom28`), `description`

**UI:** Table + modal form for Custom Field Identifier → Description

---

## 4. Sequence Configuration

**Route:** `GET /admin/tenants/{id}/sequence-config` → `Admin\Tenants\SequenceConfig` Livewire component  
**Model:** `TenantSequenceConfig`
**Fields:**
- `client_prefix` — string (e.g. "CLT")
- `client_start_number` — integer
- `client_digits` — integer (zero-padding width)
- `client_custom_field_name` — string (Clio contact custom field to populate)
- `matter_prefix` — string (e.g. "MTR")
- `matter_start_number` — integer
- `matter_digits` — integer
- `matter_custom_field_name` — string (Clio matter custom field to populate)

**UI:**
- Two sections: Client Sequence / Matter Sequence
- Live preview showing generated ID (e.g. "CLT-00042")
- Save / Delete config buttons

---

## 5. Subscription Management

**Route:** `GET /admin/tenants/{id}/subscriptions` → `Admin\Tenants\Subscriptions` Livewire component  
**Model:** `TenantSubscription`
**Fields:** `tenant_id`, `reference` (e.g. PRO-XYZ999), `start_date`, `end_date`, `status` (active/void/expired), `clio_users_at_start`

**UI:**
- Table: Reference | Start Date | End Date | Status | Clio Users at Start | Actions
- "Add Subscription" opens Flux modal:
  - Reference (auto-generated pattern, editable)
  - Start Date (date picker)
  - End Date (date picker, must be after start)
- Void button per active subscription
- Auto-void previous active subscription when creating new one

**Business rules:**
- Only one active subscription at a time per tenant
- `clio_users_at_start` populated by fetching current Clio user count at creation time

---

## 6. Webhook Management

### 6.1 Webhooks List Per Tenant
**Route:** `GET /admin/tenants/{id}/webhooks` → `Admin\Tenants\Webhooks` Livewire component  
**Model:** `Webhook`
**Fields:** `tenant_id`, `webhook_type_id`, `clio_id`, `url`, `shared_secret`, `model`, `status`, `events`, `expires_at`

**UI:**
- Table: Type | Clio ID | URL | Status | Events | Expires At | Actions
- "Register Webhook" button → calls Clio Webhooks API via `ClioApiService::createWebhook()`
- Extend Expiry button per webhook
- Delete button per webhook

### 6.2 Webhook Request Reattempt
**Existing:** `Admin\WebhookRequests\Show` blade exists but lacks a reattempt action.

**Add to show view:**
- "Reattempt" button if request is in `failed` state
- "Reattempt Security" button if security step failed

**Implementation:** Dispatch `ReattemptMatterActivity` or equivalent job

---

## 7. Clio Users Management

**Route:** `GET /admin/tenants/{id}/clio-users` → `Admin\Tenants\ClioUsers` Livewire component  
**Model:** `ClioUser`

**UI:**
- Table: Name | Email | Role | Enabled | Last Sync
- "Sync Users" button → dispatches `SyncClioData` (or specific Clio user sync job)
- Pagination

---

## 8. Force Sync Controls

**Location:** Tenant show page — new "Actions" section or toolbar buttons

**Buttons:**
- "Sync Clio Data" → dispatches `SyncClioData` job for this tenant (syncs users, practice areas, groups, matters)
- "Sync iManage Data" → dispatches `SyncImanageData` job for this tenant (syncs libraries, templates, practice areas, users, groups, custom fields)

**UI feedback:** Show last synced timestamps. After dispatch, show "Sync queued" toast.

---

## 9. Library & Template Browser

**Routes:**
- `GET /admin/tenants/{id}/libraries` → `Admin\Tenants\Libraries` Livewire component
- `GET /admin/tenants/{id}/templates` → `Admin\Tenants\Templates` Livewire component

**UI:** Simple read-only tables showing synced data (name, ID, etc.) with a sync button at the top.

---

## 10. Admin Dashboard Enhancements

**Current:** Stats cards + recent webhook requests table.

**Add:**
- Total tenants with active subscriptions
- Tenants with expiring subscriptions (next 30 days)
- Tenants with no active OAuth token (needs attention)
- Recent failed webhook requests across all tenants

---

## 11. Tenant Show Page — Quick Actions

Add an "Actions" card to the tenant show page with:
- Edit (existing)
- Configuration (→ config route)
- Sequence Config (→ sequence config route)
- Subscriptions (→ subscriptions route)
- Clio Users (→ clio-users route)
- Webhooks (→ webhooks route)
- Sync Clio Data (dispatch job)
- Sync iManage Data (dispatch job)

---

## 12. Implement Sync Job Stubs

### 12.1 SyncClioData
**File:** `app/Jobs/SyncClioData.php`
**Currently:** Stubbed with TODOs

**Implement:**
- `syncUsers()` — `ClioApiService::getUsers()` → upsert `clio_users` (by `clio_id`)
- `syncGroups()` — `ClioApiService::getGroups()` → upsert `clio_groups`
- `syncPracticeAreas()` — `ClioApiService::getPracticeAreas()` → upsert `clio_practice_areas`

### 12.2 SyncImanageData
**File:** `app/Jobs/SyncImanageData.php`
**Currently:** Stubbed with TODOs

**Implement:**
- `syncLibraries()` — `ImanageApiService::getLibraries()` → upsert `libraries`
- `syncPracticeAreas()` — `ImanageApiService::getPracticeAreas()` → upsert `imanage_practice_areas` + `imanage_sub_practice_areas`
- `syncTemplates()` — `ImanageApiService::getTemplates()` → upsert `imanage_templates`
- `syncUsers()` — `ImanageApiService::getUsers()` → upsert `imanage_users`
- `syncGroups()` — `ImanageApiService::getGroups()` → upsert `imanage_groups`
- `syncCustomFields()` — `ImanageApiService::getCustomFields()` → upsert `imanage_custom_fields`

---

## 13. UpdateMatter Stubs

**File:** `app/Jobs/UpdateMatter.php`
**Methods to implement:**
- `findOrCreateClient()` — look up `ClioClient` by Clio ID, create `ImanageClient` if missing
- `findOrCreateMatter()` — look up or create `ImanageMatter` in the correct library
- `findOrCreateWorkspace()` — look up `ImanageWorkspace` or create one via `ClioApiService::createWorkspace()`

---

## 14. Security & Hardening (Phase later)

- `.env.example` — document all required env vars ✅ (in progress)
- CSP headers via middleware
- HSTS enforcement in production
- Rate limiting on OAuth callback routes
- Webhook HMAC signature verification (already partially done in `WebhookController`)

---

## Build Order (Recommended)

1. ✅ OAuth flows (Clio + iManage)
2. Token refresh commands (fill stubs)
3. Tenant configuration panel (Section 3)
4. Sync job stubs → implement (Section 12) — needed so config dropdowns have data
5. Subscription management (Section 5)
6. Sequence configuration (Section 4)
7. Clio users management (Section 7)
8. Webhook management (Section 6)
9. Force sync UI buttons (Section 8)
10. Library/template browser (Section 9)
11. Webhook reattempt (Section 6.2)
12. UpdateMatter stubs (Section 13)
13. Security hardening (Section 14)
