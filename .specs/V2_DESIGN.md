# V2 Technical Design Document

**Document Version:** 2.0
**Last Updated:** 2026-08-14
**Status:** Production-Ready Design Specification
**Stack:** Laravel 11+ / Livewire 3 / Tailwind CSS 3 / Redis / MySQL 8 / Spatie Permission / Laravel Horizon
**Companion Document:** V2_REQUIREMENTS.md

---

## Table of Contents

1. [System Architecture Overview](#1-system-architecture-overview)
2. [Database Schema (Full ERD)](#2-database-schema-full-erd)
3. [Eloquent Model Class Diagram](#3-eloquent-model-class-diagram)
4. [Service Layer Class Diagram](#4-service-layer-class-diagram)
5. [Job Pipeline Class Diagram](#5-job-pipeline-class-diagram)
6. [Webhook Processing Data Flow](#6-webhook-processing-data-flow)
7. [Role & Permission Design](#7-role--permission-design)
8. [Onboarding Wizard Flow](#8-onboarding-wizard-flow)
9. [Queue Architecture](#9-queue-architecture)
10. [Key Design Decisions & Rationale](#10-key-design-decisions--rationale)

---

## 1. System Architecture Overview

### High-Level Architecture Diagram

```
 EXTERNAL SYSTEMS                         IMAN-CLIO V2                                    EXTERNAL SYSTEMS
 ================                         ===========                                    ================

                        +------------------------------------------------------------------+
                        |                     INCOMING LAYER                                |
                        |                                                                  |
  +----------+          |  +--------------------+    +------------------------+            |
  |          |  POST    |  |                    |    |                        |            |
  |  CLIO    |--------->|  | WebhookController  |--->| WebhookVerification   |            |
  |  (SaaS)  |  /webhook|  |                    |    | Service               |            |
  |          |<---------|  | - Handshake resp.  |    | - Signature verify    |            |
  +----------+   200 OK |  | - Tenant lookup    |    | - Handshake handling  |            |
                        |  | - Subscription chk |    +------------------------+            |
                        |  +--------|-----------+                                          |
                        +-----------|----- ------------------------------------------------+
                                    |
                                    | dispatch(ProcessWebhook)
                                    v
                        +------------------------------------------------------------------+
                        |                    PROCESSING LAYER                               |
                        |                                                                  |
                        |  +--------------------+    +----------------------------+        |
                        |  |                    |    |                            |        |
                        |  | ProcessWebhook Job |    | TenantConfigurationService |        |
                        |  | (Queue: webhooks)  |--->| - Display number parsing   |        |
                        |  |                    |    | - Client name transform    |        |
                        |  | - Parse payload    |    | - Matter desc transform    |        |
                        |  | - Apply filters    |    | - Webhook filters          |        |
                        |  | - Create WR record |    | - Custom field mapping     |        |
                        |  +--------|-----------+    | - Legacy alias lookup      |        |
                        |           |                +----------------------------+        |
                        |           | dispatch(UpdateMatter)                                |
                        |           v                                                      |
                        |  +--------------------+    +----------------------------+        |
                        |  |                    |    |                            |        |
                        |  | UpdateMatter Job   |    | SequenceNumberService      |        |
                        |  | (Queue: imanage)   |    | - Next client number       |        |
                        |  |                    |    | - Next matter number       |        |
                        |  | - Acquire lock     |    | - Clio writeback           |        |
                        |  | - Create client    |    +----------------------------+        |
                        |  | - Create matter    |                                          |
                        |  | - Create workspace |    +----------------------------+        |
                        |  | - Dispatch chained |    |                            |        |
                        |  +--------|-----------+    | WorkspaceNameResolver      |        |
                        |           |                | - Template token engine     |        |
                        |           |                +----------------------------+        |
                        |           |                                                      |
                        |  +--------v-----------+    +----------------------------+        |
                        |  | Chained Jobs:       |    |                            |        |
                        |  | - CreateWSFolders   |    | DisplayNumberParser        |        |
                        |  | - PostWSSecurity    |    | - Strategy pattern         |        |
                        |  | - AuditWSSecurity   |    | - Fallback chains          |        |
                        |  | - PopulateWSLink    |    +----------------------------+        |
                        |  | (Queue: long_term)  |                                          |
                        |  +--------------------+                                          |
                        +------------------------------------------------------------------+
                                    |                                    ^
                                    v                                    |
                        +------------------------------------------------------------------+
                        |                   INTEGRATION LAYER                               |
                        |                                                                  |
                        |  +------------------------+    +---------------------------+     |
                        |  |                        |    |                           |     |
                        |  | ClioApiService         |    | ImanageApiService         |     |     +-----------+
                        |  | - OAuth token mgmt     |    | - OAuth/password auth     |-----|---->|           |
                        |  | - Matter retrieval     |    | - Client CRUD             |     |     | iManage   |
                        |  | - Client retrieval     |    | - Matter CRUD             |     |     | (DMS)     |
                        |  | - Group/User sync      |    | - Workspace CRUD          |     |     |           |
                        |  | - Webhook CRUD         |    | - Folder management       |<----|-----|           |
                        |  | - Custom field update  |    | - Security management     |     |     +-----------+
                        |  +------------------------+    +---------------------------+     |
                        +------------------------------------------------------------------+
                                    |
                                    v
                        +------------------------------------------------------------------+
                        |                   CONFIGURATION LAYER                             |
                        |                                                                  |
                        |  +-------------------------+  +-----------------------------+    |
                        |  | Tenant Config Engine    |  | Mapping Tables              |    |
                        |  | (Database-driven)       |  |                             |    |
                        |  |                         |  | - PracticeAreaMapping       |    |
                        |  | - DisplayNumberParsing  |  | - TemplateMapping           |    |
                        |  | - ClientNameTransform   |  | - GroupMapping              |    |
                        |  | - MatterDescTransform   |  | - UserMapping               |    |
                        |  | - WorkspaceNaming       |  | - CustomFieldMappingRule    |    |
                        |  | - WebhookFilters        |  | - LegacyAliasMapping       |    |
                        |  | - SequenceConfig        |  +-----------------------------+    |
                        |  +-------------------------+                                     |
                        +------------------------------------------------------------------+
                                    |
                                    v
                        +------------------------------------------------------------------+
                        |                      DATA LAYER                                   |
                        |                                                                  |
                        |  +------------------+         +---------------------------+      |
                        |  |     MySQL 8       |         |        Redis              |      |
                        |  |                   |         |                           |      |
                        |  | - All domain      |         | - Queue backend           |      |
                        |  |   tables          |         | - Cache (tokens, config)  |      |
                        |  | - Audit logs      |         | - Tenant job locks        |      |
                        |  | - Activity logs   |         | - Rate limiting           |      |
                        |  | - Sessions        |         | - Horizon metrics         |      |
                        |  +------------------+         +---------------------------+      |
                        +------------------------------------------------------------------+
                                    |
                                    v
                        +------------------------------------------------------------------+
                        |                  INFRASTRUCTURE LAYER                             |
                        |                                                                  |
                        |  +-----------------+  +-----------------+  +-----------------+   |
                        |  | Laravel Horizon  |  | Task Scheduler  |  | Health Checks   |   |
                        |  | - Queue workers  |  | - Token refresh |  | - Redis conn    |   |
                        |  | - Job monitoring |  | - Webhook renew |  | - MySQL conn    |   |
                        |  | - Retry policy   |  | - Sync jobs     |  | - Queue depth   |   |
                        |  | - Metrics        |  | - Summaries     |  | - API health    |   |
                        |  +-----------------+  +-----------------+  +-----------------+   |
                        +------------------------------------------------------------------+
```

### Data Flow Summary

```
Clio Webhook POST
       |
       v
WebhookController (HTTP layer - sync)
  |-- Handshake? Return X-Hook-Secret header
  |-- Verify signature (WebhookVerificationService)
  |-- Lookup tenant by UUID reference
  |-- Check active subscription
  |-- Return 200 OK immediately
  |-- Dispatch ProcessWebhook to 'webhooks' queue
       |
       v
ProcessWebhook Job (Queue: webhooks)
  |-- Apply webhook processing filters (TenantConfigurationService)
  |-- Parse display number (DisplayNumberParser)
  |-- Transform client name (TenantConfigurationService)
  |-- Transform matter description (TenantConfigurationService)
  |-- Create WebhookRequest record with correlation_id
  |-- Dispatch UpdateMatter to 'imanage' queue
       |
       v
UpdateMatter Job (Queue: imanage)
  |-- Acquire tenant lock (Redis/DB)
  |-- Resolve sequence numbers if applicable
  |-- Call ImanageApiService: findOrCreateClient
  |-- Call ImanageApiService: findOrCreateMatter
  |-- Resolve workspace name (WorkspaceNameResolver)
  |-- Resolve custom field mappings (TenantConfigurationService)
  |-- Call ImanageApiService: findOrCreateWorkspace / updateWorkspace
  |-- Dispatch CreateWorkspaceFolders -> 'long_term'
  |-- Dispatch PostWorkspaceSecurity -> 'long_term'
  |-- Dispatch PopulateWorkspaceLinkCustomField -> 'long_term' (if enabled)
  |-- Release tenant lock
       |
       v
Chained Long-Term Jobs (Queue: long_term)
  |-- CreateWorkspaceFolders: copy template folders
  |-- PostWorkspaceSecurity: apply security from template or group mapping
  |-- AuditWorkspaceSecurity: compare template vs actual, log diff
  |-- PopulateWorkspaceLinkCustomField: write IWL back to Clio custom field
  |-- Mark WebhookRequest complete
```

---

## 2. Database Schema (Full ERD)

All tables are grouped by domain. Column types follow Laravel migration conventions.

---

### Auth & Users Domain

#### `users`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK, auto-increment | |
| name | varchar(255) | NOT NULL | |
| email | varchar(255) | NOT NULL, UNIQUE | |
| password | varchar(255) | NOT NULL | Hashed via bcrypt |
| tenant_id | bigint unsigned | NULLABLE, FK->tenants.id | NULL = back-office user |
| email_verified_at | timestamp | NULLABLE | |
| two_factor_secret | text | NULLABLE | Encrypted |
| two_factor_recovery_codes | text | NULLABLE | Encrypted |
| two_factor_confirmed_at | timestamp | NULLABLE | |
| remember_token | varchar(100) | NULLABLE | |
| last_login_at | timestamp | NULLABLE | |
| last_login_ip | varchar(45) | NULLABLE | IPv6-safe length |
| failed_login_attempts | int | DEFAULT 0 | Reset on successful login |
| locked_until | timestamp | NULLABLE | Account lockout after N failures |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Indexes:** `users_email_unique`, `users_tenant_id_foreign`

#### `personal_access_tokens` (Laravel Sanctum)

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tokenable_type | varchar(255) | NOT NULL | Polymorphic |
| tokenable_id | bigint unsigned | NOT NULL | Polymorphic |
| name | varchar(255) | NOT NULL | |
| token | varchar(64) | NOT NULL, UNIQUE | SHA-256 hash |
| abilities | text | NULLABLE | JSON array |
| last_used_at | timestamp | NULLABLE | |
| expires_at | timestamp | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `password_reset_tokens`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| email | varchar(255) | PK | |
| token | varchar(255) | NOT NULL | |
| created_at | timestamp | NULLABLE | |

#### `login_audit_logs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| user_id | bigint unsigned | NULLABLE, FK->users.id | NULL if login attempt with unknown email |
| email | varchar(255) | NOT NULL | Email used in attempt |
| ip_address | varchar(45) | NOT NULL | |
| user_agent | text | NULLABLE | |
| success | boolean | NOT NULL | |
| created_at | timestamp | NULLABLE | |

**Indexes:** `login_audit_logs_user_id_index`, `login_audit_logs_email_index`, `login_audit_logs_created_at_index`

#### Spatie Permission Tables

`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` -- standard Spatie schema. See Section 7 for the permission matrix.

---

### Tenant Domain

#### `clio_locations`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| name | varchar(255) | NOT NULL | e.g., "United States", "Europe" |
| region | enum('US','EU','CA','AU') | NOT NULL | |
| api_url | varchar(512) | NOT NULL | e.g., "https://app.clio.com/api/v4/" |
| app_url | varchar(512) | NOT NULL | e.g., "https://app.clio.com" |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `tenants`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| name | varchar(255) | NOT NULL | Firm display name |
| slug | varchar(255) | NOT NULL, UNIQUE | URL-safe identifier |
| reference | uuid | NOT NULL, UNIQUE | Used in webhook URLs |
| status | enum('pending','active','suspended','archived') | NOT NULL, DEFAULT 'pending' | |
| clio_location_id | bigint unsigned | FK->clio_locations.id | |
| clio_app_id | text | NULLABLE | Encrypted at rest |
| clio_app_secret | text | NULLABLE | Encrypted at rest |
| imanage_cloud_url | varchar(512) | NULLABLE | e.g., "https://cloudimanage.com" |
| imanage_customer_id | varchar(255) | NULLABLE | |
| imanage_app_id | text | NULLABLE | Encrypted at rest |
| imanage_app_secret | text | NULLABLE | Encrypted at rest |
| imanage_username | text | NULLABLE | Encrypted; for password auth tenants |
| imanage_password | text | NULLABLE | Encrypted; for password auth tenants |
| password_authentication | boolean | NOT NULL, DEFAULT false | true = password grant, false = OAuth |
| has_group_security_mapping | boolean | NOT NULL, DEFAULT false | |
| enable_workspace_link_custom_field | boolean | NOT NULL, DEFAULT false | |
| owner_id | bigint unsigned | NULLABLE, FK->users.id | Primary contact user |
| onboarded_at | timestamp | NULLABLE | Wizard completion timestamp |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Indexes:** `tenants_reference_unique`, `tenants_slug_unique`, `tenants_status_index`

#### `tenant_settings`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | One-to-one |
| library_id | bigint unsigned | NULLABLE, FK->libraries.id | Default iManage library |
| imanage_template_id | bigint unsigned | NULLABLE, FK->imanage_templates.id | Default workspace template |
| default_hipaa | boolean | NOT NULL, DEFAULT false | |
| default_enabled | boolean | NOT NULL, DEFAULT true | |
| has_replica_workspaces | boolean | NOT NULL, DEFAULT false | |
| replica_template_id | bigint unsigned | NULLABLE, FK->imanage_templates.id | |
| has_workspace_link_custom_field | boolean | NOT NULL, DEFAULT false | |
| workspace_link_custom_field_name | varchar(255) | NULLABLE | Clio custom field name for IWL |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `tenant_subscriptions`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| reference | varchar(255) | NOT NULL, UNIQUE | External reference / invoice ID |
| status | enum('active','void','expired') | NOT NULL, DEFAULT 'active' | |
| start_date | date | NOT NULL | |
| end_date | date | NOT NULL | |
| clio_users_at_start | int | NULLABLE | Snapshot of Clio user count at subscription start |
| voided_at | timestamp | NULLABLE | |
| voided_by | bigint unsigned | NULLABLE, FK->users.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `tenant_subscriptions_tenant_id_index`, `tenant_subscriptions_status_index`

#### `tenant_job_locks`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | |
| locked_at | timestamp | NULLABLE | NULL = unlocked |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `activity_logs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NULLABLE, FK->tenants.id | NULL for system-level actions |
| user_id | bigint unsigned | NULLABLE, FK->users.id | NULL for system/job actions |
| action | varchar(255) | NOT NULL | e.g., "tenant.updated", "config.changed" |
| model_type | varchar(255) | NULLABLE | Polymorphic model class |
| model_id | bigint unsigned | NULLABLE | |
| old_values | json | NULLABLE | Previous state snapshot |
| new_values | json | NULLABLE | New state snapshot |
| ip_address | varchar(45) | NULLABLE | |
| user_agent | text | NULLABLE | |
| created_at | timestamp | NULLABLE | |

**Indexes:** `activity_logs_tenant_id_index`, `activity_logs_action_index`, `activity_logs_created_at_index`

---

### Tenant Configuration Engine Domain

#### `display_number_parsing_configs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | One active config per tenant |
| strategy | enum | NOT NULL | See below |
| delimiter | varchar(10) | NULLABLE | Primary split character: `-`, `.`, `/`, `;` |
| secondary_delimiter | varchar(10) | NULLABLE | For nested splits (e.g., TenkLaw: `.` then `-`) |
| client_position | int | NULLABLE | 0-based index after split |
| matter_position | int | NULLABLE | 0-based index after split |
| regex_pattern | varchar(512) | NULLABLE | For 'regex' strategy |
| client_capture_group | varchar(50) | NULLABLE | Named group: `(?P<client>...)` |
| matter_capture_group | varchar(50) | NULLABLE | Named group: `(?P<matter>...)` |
| pre_processing_rules | json | NULLABLE | e.g., `[{"action":"trim"},{"action":"ltrim","char":"-"}]` |
| post_processing_rules | json | NULLABLE | e.g., `[{"action":"pad_left","length":4,"char":"0","target":"matter"}]` |
| validation_regex | varchar(512) | NULLABLE | Skip if display_number doesn't match |
| matter_status_filter | varchar(50) | NULLABLE | e.g., "OPEN" -- only process if status matches |
| custom_field_name | varchar(255) | NULLABLE | For 'custom_field_extraction' strategy |
| fallback_strategy | varchar(50) | NULLABLE | Strategy to use if primary fails |
| fallback_config | json | NULLABLE | Config for fallback strategy |
| enabled | boolean | NOT NULL, DEFAULT true | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Strategy enum values:**
- `split_delimiter` -- Split on delimiter, pick positions (Foundation Law, KW Law, etc.)
- `split_delimiter_nested` -- Split on primary, then secondary (TenkLaw, Osborne Wise, etc.)
- `regex` -- Full regex with named capture groups (FT Legal, Gionis Lilly)
- `bracket_extraction` -- Extract from brackets (LCI, LCI-UK)
- `clio_ids` -- Use Clio client_id and matter id directly (Embry Law, Inceptiv Law)
- `custom_field_extraction` -- Extract from a Clio custom field (Mills Co primary)
- `display_number_as_matter` -- Display number IS the matter_id (KRB, Hall & Diana)
- `display_number_as_client` -- Display number IS the client_id (Vladeck Raskin)
- `sequence_auto` -- Auto-generate via SequenceNumberService
- `legacy_alias_lookup` -- Check legacy alias mapping table first
- `custom` -- Fully custom regex with pre/post processing

#### `client_name_transformation_configs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | |
| strategy | enum('none','last_name_first','reverse_words','custom_template') | NOT NULL, DEFAULT 'none' | |
| template_pattern | varchar(512) | NULLABLE | For custom_template: `{last_name}, {first_names}` |
| apply_to_persons_only | boolean | NOT NULL, DEFAULT true | Don't transform Company contacts |
| enabled | boolean | NOT NULL, DEFAULT true | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `matter_description_transformation_configs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | |
| strategy | enum('none','use_display_number','use_client_description','composite_template','strip_prefix') | NOT NULL, DEFAULT 'none' | |
| source_field | varchar(255) | NULLABLE | For composite_template |
| template_pattern | varchar(512) | NULLABLE | e.g., `{display_number_part0}-{display_number_part1} - {description}` |
| enabled | boolean | NOT NULL, DEFAULT true | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `workspace_naming_configs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | |
| template_pattern | varchar(1024) | NOT NULL | e.g., `{client_id} - {client_description} - {matter_description}` |
| description | varchar(255) | NULLABLE | Human-readable label for admin UI |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Available tokens:** `{client_id}`, `{matter_id}`, `{client_description}`, `{matter_description}`, `{display_number}`, `{practice_area}`, `{sub_practice_area}`, `{open_date}`, `{responsible_attorney}`

#### `custom_field_mapping_rules`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | Multiple rules per tenant |
| source_type | enum | NOT NULL | See below |
| source_field_name | varchar(255) | NULLABLE | Clio field name for custom_field source |
| imanage_custom_field_config_id | bigint unsigned | NULLABLE, FK | Target iManage custom field config |
| value_mapping_type | enum('direct','lookup','static','date_format') | NOT NULL | |
| static_value | varchar(255) | NULLABLE | For 'static' mapping type |
| date_format | varchar(100) | NULLABLE | For 'date_format' type, e.g., `Y-m-d\TH:i:s\Z` |
| priority | int | NOT NULL, DEFAULT 0 | Higher = evaluated first |
| enabled | boolean | NOT NULL, DEFAULT true | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Source type enum:** `matter_status`, `responsible_attorney`, `originating_attorney`, `practice_area`, `template`, `clio_custom_field`, `open_date`, `static`

**Indexes:** `custom_field_mapping_rules_tenant_id_priority_index`

#### `legacy_alias_mappings`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| entity_type | enum('client','matter') | NOT NULL | |
| clio_id | varchar(255) | NOT NULL | Clio entity ID |
| imanage_alias | varchar(255) | NOT NULL | Mapped iManage key |
| imported_from | varchar(255) | NULLABLE | Source file name |
| imported_at | timestamp | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `legacy_alias_mappings_tenant_id_entity_type_clio_id_index` (composite unique)

#### `webhook_processing_filters`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| field_path | varchar(255) | NOT NULL | Dot-notation: `data.status`, `data.custom_field_values` |
| operator | enum('equals','not_equals','matches_regex','contains','clio_picklist_equals') | NOT NULL | |
| value | varchar(512) | NOT NULL | Expected value or regex pattern |
| action | enum('skip','proceed') | NOT NULL | What to do when filter matches |
| priority | int | NOT NULL, DEFAULT 0 | Higher = evaluated first |
| enabled | boolean | NOT NULL, DEFAULT true | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `webhook_processing_filters_tenant_id_priority_index`

---

### Clio Integration Domain

#### `clio_oauth_access_codes`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| code | text | NOT NULL | Authorization code from OAuth flow |
| redirect_uri | varchar(512) | NOT NULL | |
| expires_at | timestamp | NOT NULL | Short-lived |
| created_at | timestamp | NULLABLE | |

#### `clio_oauth_access_tokens`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| access_token | text | NOT NULL | Encrypted at rest |
| refresh_token | text | NOT NULL | Encrypted at rest |
| access_expires_at | timestamp | NOT NULL | |
| refresh_expires_at | timestamp | NULLABLE | |
| revoked | boolean | NOT NULL, DEFAULT false | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `clio_oauth_access_tokens_tenant_id_revoked_index`

#### `clio_matters`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | Clio's internal ID |
| clio_client_id | bigint unsigned | NULLABLE, FK->clio_clients.id | |
| clio_practice_area_id | bigint unsigned | NULLABLE, FK->clio_practice_areas.id | |
| matter_id | varchar(32) | NULLABLE | Parsed iManage matter key |
| etag | varchar(255) | NULLABLE | |
| display_number | varchar(255) | NULLABLE | |
| custom_number | varchar(255) | NULLABLE | |
| description | text | NULLABLE | |
| status | varchar(50) | NULLABLE | Open, Closed, Pending |
| location | varchar(255) | NULLABLE | |
| client_reference | varchar(255) | NULLABLE | |
| open_date | date | NULLABLE | |
| close_date | date | NULLABLE | |
| pending_date | date | NULLABLE | |
| json_data | json | NULLABLE | Full Clio payload snapshot |
| sequence_key | varchar(50) | NULLABLE | Auto-generated sequence identifier |
| sequence_number | int | NULLABLE | Numeric sequence value |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `clio_matters_tenant_id_clio_id_unique`, `clio_matters_tenant_id_status_index`

#### `clio_clients`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | Clio's internal ID |
| client_id | varchar(32) | NULLABLE | Parsed iManage client key |
| etag | varchar(255) | NULLABLE | |
| name | varchar(255) | NULLABLE | |
| first_name | varchar(255) | NULLABLE | |
| last_name | varchar(255) | NULLABLE | |
| type | varchar(50) | NULLABLE | Person or Company |
| initials | varchar(10) | NULLABLE | |
| sequence_key | varchar(50) | NULLABLE | |
| sequence_number | int | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `clio_clients_tenant_id_clio_id_unique`

#### `clio_practice_areas`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | |
| name | varchar(255) | NOT NULL | |
| code | varchar(50) | NULLABLE | |
| category | varchar(255) | NULLABLE | |
| etag | varchar(255) | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `clio_practice_areas_tenant_id_clio_id_unique`

#### `clio_users`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | |
| name | varchar(255) | NULLABLE | |
| email | varchar(255) | NULLABLE | |
| first_name | varchar(255) | NULLABLE | |
| last_name | varchar(255) | NULLABLE | |
| initials | varchar(10) | NULLABLE | |
| enabled | boolean | NOT NULL, DEFAULT true | |
| time_zone | varchar(100) | NULLABLE | |
| subscription_type | varchar(50) | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `clio_users_tenant_id_clio_id_unique`

#### `clio_groups`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | |
| name | varchar(255) | NOT NULL | |
| type | varchar(50) | NULLABLE | |
| etag | varchar(255) | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `clio_groups_tenant_id_clio_id_unique`

#### `clio_matter_stages` (NEW in V2)

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | |
| name | varchar(255) | NOT NULL | |
| display_order | int | NULLABLE | |
| clio_practice_area_id | bigint unsigned | NULLABLE, FK->clio_practice_areas.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

---

### iManage Integration Domain

#### `imanage_oauth_access_codes`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| code | text | NOT NULL | |
| created_at | timestamp | NULLABLE | |

#### `imanage_oauth_access_tokens`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| access_token | text | NOT NULL | Encrypted at rest |
| refresh_token | text | NULLABLE | Encrypted at rest |
| expires_at | timestamp | NOT NULL | |
| revoked | boolean | NOT NULL, DEFAULT false | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `libraries`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| imanage_library_id | varchar(255) | NOT NULL | e.g., "ACTIVE" |
| name | varchar(255) | NOT NULL | |
| description | text | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `libraries_tenant_id_imanage_library_id_unique`

#### `imanage_practice_areas`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| key | varchar(255) | NOT NULL | iManage practice area key |
| description | varchar(512) | NULLABLE | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `imanage_sub_practice_areas`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| key | varchar(255) | NOT NULL | |
| description | varchar(512) | NULLABLE | |
| imanage_practice_area_id | bigint unsigned | NOT NULL, FK->imanage_practice_areas.id | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `imanage_templates`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| imanage_template_id | varchar(255) | NOT NULL | iManage's template identifier |
| description | varchar(512) | NULLABLE | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `imanage_clients`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| webhook_request_id | bigint unsigned | NULLABLE, FK->webhook_requests.id | Last WR that touched this |
| clio_client_id | bigint | NULLABLE | Clio contact ID (not FK, raw Clio ID) |
| key | varchar(32) | NOT NULL | iManage client key |
| key_number | varchar(32) | NULLABLE | Numeric portion of key |
| ssid | varchar(255) | NULLABLE | iManage SSID |
| description | varchar(512) | NULLABLE | Client description in iManage |
| enabled | boolean | NOT NULL, DEFAULT true | |
| hipaa | boolean | NOT NULL, DEFAULT false | |
| wstype | varchar(50) | NULLABLE | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| sequence_number | int | NULLABLE | For sequence-based tenants |
| sequence_key | varchar(50) | NULLABLE | Formatted sequence identifier |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `imanage_clients_tenant_id_key_library_id_unique`

#### `imanage_matters`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| webhook_request_id | bigint unsigned | NULLABLE, FK->webhook_requests.id | |
| imanage_client_id | bigint unsigned | NOT NULL, FK->imanage_clients.id | |
| clio_client_id | bigint | NULLABLE | Raw Clio ID |
| clio_matter_id | bigint | NULLABLE | Raw Clio ID |
| clio_practice_area_id | bigint unsigned | NULLABLE, FK->clio_practice_areas.id | |
| key | varchar(32) | NOT NULL | iManage matter key |
| key_numeric | int | NULLABLE | Numeric portion for sequencing |
| ssid | varchar(255) | NULLABLE | |
| description | varchar(512) | NULLABLE | |
| enabled | boolean | NOT NULL, DEFAULT true | |
| hipaa | boolean | NOT NULL, DEFAULT false | |
| wstype | varchar(50) | NULLABLE | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| iman_practice_area_id | bigint unsigned | NULLABLE, FK->imanage_practice_areas.id | |
| iman_sub_practice_area_id | bigint unsigned | NULLABLE, FK->imanage_sub_practice_areas.id | |
| closed | boolean | NOT NULL, DEFAULT false | |
| parent_id | varchar(255) | NULLABLE | Parent client key |
| parent_ssid | varchar(255) | NULLABLE | |
| sequence_number | int | NULLABLE | |
| sequence_key | varchar(50) | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `imanage_matters_tenant_id_key_imanage_client_id_library_id_unique`

#### `imanage_workspaces`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| webhook_request_id | bigint unsigned | NULLABLE, FK->webhook_requests.id | |
| imanage_workspace_id | varchar(255) | NOT NULL | iManage's workspace ID |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| imanage_template_id | bigint unsigned | NULLABLE, FK->imanage_templates.id | |
| imanage_matter_id | bigint unsigned | NULLABLE, FK->imanage_matters.id | |
| imanage_client_id | bigint unsigned | NULLABLE, FK->imanage_clients.id | |
| iman_practice_area_id | bigint unsigned | NULLABLE, FK->imanage_practice_areas.id | |
| iman_sub_practice_area_id | bigint unsigned | NULLABLE, FK->imanage_sub_practice_areas.id | |
| name | varchar(1024) | NOT NULL | Workspace display name |
| description | text | NULLABLE | |
| database | varchar(255) | NOT NULL | Library identifier |
| default_security | varchar(50) | NULLABLE | public, private, view |
| has_subfolders | boolean | NOT NULL, DEFAULT false | |
| owner | varchar(255) | NULLABLE | iManage owner user ID |
| custom1 through custom30 | varchar(255) each | NULLABLE | iManage custom metadata fields |
| document_number | varchar(255) | NULLABLE | |
| is_declared | boolean | NOT NULL, DEFAULT false | |
| is_hipaa | boolean | NOT NULL, DEFAULT false | |
| iwl | text | NULLABLE | iManage Work Link |
| replica | boolean | NOT NULL, DEFAULT false | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Note:** `custom1` through `custom30` are individual varchar(255) columns. In the Eloquent model, these are accessed as `$workspace->custom1`, `$workspace->custom2`, etc. Each custom field also has optional companion columns `custom{N}_description` and `custom{N}_ssid` for the subset of fields that carry those attributes (typically custom1, custom2, custom29, custom30).

**Indexes:** `imanage_workspaces_tenant_id_database_custom2_custom1_index`, `imanage_workspaces_imanage_workspace_id_index`

#### `imanage_groups`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| imanage_group_id | varchar(255) | NOT NULL | |
| name | varchar(255) | NOT NULL | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `imanage_users`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| imanage_user_id | varchar(255) | NOT NULL | |
| full_name | varchar(255) | NULLABLE | |
| email | varchar(255) | NULLABLE | |
| library_id | bigint unsigned | NOT NULL, FK->libraries.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `imanage_custom_field_configs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| custom_field_identifier | varchar(255) | NOT NULL | e.g., "custom3", "custom15" |
| description | varchar(512) | NOT NULL | e.g., "Status", "Responsible Attorney" |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `imanage_custom_fields`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| imanage_custom_field_config_id | bigint unsigned | NOT NULL, FK | Parent config |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| key | varchar(255) | NOT NULL | Lookup value key |
| description | varchar(512) | NOT NULL | Human-readable label |
| wstype | varchar(50) | NOT NULL | e.g., "custom3", "custom15" |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

---

### Mapping Domain

#### `practice_area_mappings`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_practice_area_id | bigint unsigned | NOT NULL, FK->clio_practice_areas.id | |
| imanage_practice_area_id | bigint unsigned | NOT NULL, FK->imanage_practice_areas.id | |
| imanage_sub_practice_area_id | bigint unsigned | NULLABLE, FK->imanage_sub_practice_areas.id | |
| imanage_custom_field_config_id | bigint unsigned | NULLABLE, FK->imanage_custom_field_configs.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Indexes:** `practice_area_mappings_tenant_id_clio_practice_area_id_unique`

#### `template_mappings`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_practice_area_id | bigint unsigned | NOT NULL, FK->clio_practice_areas.id | |
| imanage_template_id | bigint unsigned | NOT NULL, FK->imanage_templates.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `group_mappings`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_group_id | bigint unsigned | NOT NULL, FK->clio_groups.id | |
| imanage_group_id | bigint unsigned | NOT NULL, FK->imanage_groups.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `user_mappings`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_user_id | bigint unsigned | NOT NULL, FK->clio_users.id | |
| imanage_user_id | bigint unsigned | NOT NULL, FK->imanage_users.id | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

---

### Webhook Processing Domain

#### `webhook_types`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| name | varchar(255) | NOT NULL | e.g., "Matter Created" |
| model | varchar(100) | NOT NULL | e.g., "Matter" |
| event | varchar(100) | NOT NULL | e.g., "created", "updated", "deleted" |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `webhooks`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| clio_id | bigint | NOT NULL | Clio's webhook ID |
| webhook_type_id | bigint unsigned | NOT NULL, FK->webhook_types.id | |
| url | varchar(512) | NOT NULL | Callback URL |
| shared_secret | text | NULLABLE | Encrypted; for signature verification |
| status | enum('active','expired','failed') | NOT NULL, DEFAULT 'active' | |
| expires_at | timestamp | NULLABLE | Clio webhooks expire |
| etag | varchar(255) | NULLABLE | For updates |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

#### `webhook_requests`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| webhook_id | bigint unsigned | NULLABLE, FK->webhooks.id | |
| url | varchar(512) | NOT NULL | |
| headers | json | NULLABLE | Request headers snapshot |
| body | json | NULLABLE | Request body snapshot |
| correlation_id | uuid | NOT NULL, UNIQUE | For distributed tracing |
| processing_stage | enum | NOT NULL, DEFAULT 'received' | See below |
| retrieved_client_id | varchar(32) | NULLABLE | Parsed client key |
| retrieved_matter_id | varchar(32) | NULLABLE | Parsed matter key |
| client_activity_complete | boolean | NOT NULL, DEFAULT false | |
| matter_activity_complete | boolean | NOT NULL, DEFAULT false | |
| workspace_activity_complete | boolean | NOT NULL, DEFAULT false | |
| folder_activity_complete | boolean | NOT NULL, DEFAULT false | |
| security_activity_complete | boolean | NOT NULL, DEFAULT false | |
| workspace_link_custom_field_populated | boolean | NOT NULL, DEFAULT false | |
| error_message | text | NULLABLE | Last error if failed |
| error_count | int | NOT NULL, DEFAULT 0 | Cumulative error count |
| started_at | timestamp | NULLABLE | Processing start |
| completed_at | timestamp | NULLABLE | All activities done |
| reattempted | boolean | NOT NULL, DEFAULT false | |
| reattempted_by | bigint unsigned | NULLABLE, FK->users.id | |
| reattempted_at | timestamp | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

**Processing stage enum:** `received`, `validated`, `parsed`, `filtered`, `enqueued`, `processing`, `post_processing`, `completed`, `failed`, `skipped`

**Indexes:** `webhook_requests_tenant_id_index`, `webhook_requests_correlation_id_unique`, `webhook_requests_processing_stage_index`, `webhook_requests_created_at_index`

#### `workspace_security_audits`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| webhook_request_id | bigint unsigned | NOT NULL, FK->webhook_requests.id | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id | |
| template_workspace_id | varchar(255) | NULLABLE | Template used as security source |
| target_workspace_id | varchar(255) | NOT NULL | Workspace that received security |
| template_security | json | NULLABLE | Expected security state |
| target_security | json | NULLABLE | Actual security state |
| diff | json | NULLABLE | Computed differences |
| status | enum('match','mismatch','pending') | NOT NULL, DEFAULT 'pending' | |
| resolved_at | timestamp | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

---

### Sequence Engine Domain

#### `tenant_sequence_configs`

| Column | Type | Constraints | Notes |
|--------|------|------------|-------|
| id | bigint unsigned | PK | |
| tenant_id | bigint unsigned | NOT NULL, FK->tenants.id, UNIQUE | |
| client_prefix | varchar(20) | NULLABLE | e.g., "C" |
| client_start_number | int | NOT NULL, DEFAULT 1 | |
| client_current_number | int | NOT NULL, DEFAULT 0 | Last assigned |
| client_digits | int | NOT NULL, DEFAULT 5 | Zero-pad length |
| client_custom_field_name | varchar(255) | NULLABLE | Clio custom field to write back to |
| matter_prefix | varchar(20) | NULLABLE | |
| matter_start_number | int | NOT NULL, DEFAULT 1 | |
| matter_current_number | int | NOT NULL, DEFAULT 0 | |
| matter_digits | int | NOT NULL, DEFAULT 5 | |
| matter_custom_field_name | varchar(255) | NULLABLE | |
| created_at | timestamp | NULLABLE | |
| updated_at | timestamp | NULLABLE | |

---

### System Domain

Standard Laravel tables: `notifications`, `jobs`, `job_batches`, `failed_jobs`, `cache`, `sessions`.

---

## 3. Eloquent Model Class Diagram

### Auth & Users Domain

```
┌──────────────────────────────┐
│ User                         │
│──────────────────────────────│
│ + id: int                    │
│ + name: string               │
│ + email: string              │
│ + password: string           │
│ + tenant_id: ?int            │
│ + last_login_at: ?datetime   │
│ + failed_login_attempts: int │
│ + locked_until: ?datetime    │
│──────────────────────────────│
│ belongsTo: Tenant            │
│ belongsToMany: Roles (Spatie)│
│ hasMany: LoginAuditLogs      │
│ hasMany: ActivityLogs        │
└──────────────────────────────┘

┌──────────────────────────────┐
│ LoginAuditLog                │
│──────────────────────────────│
│ + id: int                    │
│ + user_id: ?int              │
│ + email: string              │
│ + ip_address: string         │
│ + success: bool              │
│──────────────────────────────│
│ belongsTo: User              │
└──────────────────────────────┘
```

### Tenant Domain

```
┌──────────────────────────────────────┐
│ Tenant                               │
│──────────────────────────────────────│
│ + id: int                            │
│ + name: string                       │
│ + slug: string                       │
│ + reference: uuid                    │
│ + status: enum                       │
│ + clio_location_id: int              │
│ + clio_app_id: encrypted             │
│ + clio_app_secret: encrypted         │
│ + imanage_cloud_url: string          │
│ + imanage_customer_id: string        │
│ + password_authentication: bool      │
│ + has_group_security_mapping: bool   │
│──────────────────────────────────────│
│ belongsTo: ClioLocation              │
│ belongsTo: User (owner)              │
│ hasOne: TenantSetting                │
│ hasOne: TenantSequenceConfig         │
│ hasOne: DisplayNumberParsingConfig   │
│ hasOne: ClientNameTransformConfig    │
│ hasOne: MatterDescTransformConfig    │
│ hasOne: WorkspaceNamingConfig        │
│ hasMany: Users                       │
│ hasMany: TenantSubscriptions         │
│ hasMany: TenantJobLocks              │
│ hasMany: Webhooks                    │
│ hasMany: WebhookRequests             │
│ hasMany: ClioOAuthAccessTokens       │
│ hasMany: ImanageOAuthAccessTokens    │
│ hasMany: Libraries                   │
│ hasMany: ClioPracticeAreas           │
│ hasMany: ClioUsers                   │
│ hasMany: ClioGroups                  │
│ hasMany: ClioMatterStages            │
│ hasMany: ImanageGroups               │
│ hasMany: ImanageUsers                │
│ hasMany: ImanageClients              │
│ hasMany: ImanageMatters              │
│ hasMany: ImanageWorkspaces           │
│ hasMany: ImanageTemplates            │
│ hasMany: ImanagePracticeAreas        │
│ hasMany: ImanageCustomFieldConfigs   │
│ hasMany: CustomFieldMappingRules     │
│ hasMany: LegacyAliasMappings         │
│ hasMany: WebhookProcessingFilters    │
│ hasMany: PracticeAreaMappings        │
│ hasMany: TemplateMappings            │
│ hasMany: GroupMappings               │
│ hasMany: UserMappings                │
│ hasMany: ActivityLogs                │
└──────────────────────────────────────┘

┌─────────────────────────────────────┐
│ ClioLocation                        │
│─────────────────────────────────────│
│ + id: int                           │
│ + name: string                      │
│ + region: enum(US,EU,CA,AU)         │
│ + api_url: string                   │
│ + app_url: string                   │
│─────────────────────────────────────│
│ hasMany: Tenants                    │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ TenantSetting                       │
│─────────────────────────────────────│
│ + id: int                           │
│ + tenant_id: int                    │
│ + library_id: ?int                  │
│ + imanage_template_id: ?int         │
│ + default_hipaa: bool               │
│ + default_enabled: bool             │
│ + has_replica_workspaces: bool      │
│ + replica_template_id: ?int         │
│ + has_workspace_link_custom_field   │
│ + workspace_link_custom_field_name  │
│─────────────────────────────────────│
│ belongsTo: Tenant                   │
│ belongsTo: Library (default)        │
│ belongsTo: ImanageTemplate (default)│
│ belongsTo: ImanageTemplate (replica)│
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ TenantSubscription                  │
│─────────────────────────────────────│
│ + id: int                           │
│ + tenant_id: int                    │
│ + reference: string                 │
│ + status: enum                      │
│ + start_date: date                  │
│ + end_date: date                    │
│ + clio_users_at_start: ?int         │
│─────────────────────────────────────│
│ belongsTo: Tenant                   │
│ belongsTo: User (voided_by)         │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐
│ TenantSequenceConfig                │
│─────────────────────────────────────│
│ + id: int                           │
│ + tenant_id: int                    │
│ + client_prefix: ?string            │
│ + client_start_number: int          │
│ + client_current_number: int        │
│ + client_digits: int                │
│ + matter_prefix: ?string            │
│ + matter_start_number: int          │
│ + matter_current_number: int        │
│ + matter_digits: int                │
│─────────────────────────────────────│
│ belongsTo: Tenant                   │
└─────────────────────────────────────┘
```

### Configuration Engine Domain

```
┌──────────────────────────────────────┐
│ DisplayNumberParsingConfig           │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + strategy: enum                     │
│ + delimiter: ?string                 │
│ + secondary_delimiter: ?string       │
│ + client_position: ?int              │
│ + matter_position: ?int              │
│ + regex_pattern: ?string             │
│ + pre_processing_rules: ?json        │
│ + post_processing_rules: ?json       │
│ + validation_regex: ?string          │
│ + fallback_strategy: ?string         │
│ + fallback_config: ?json             │
│ + enabled: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClientNameTransformationConfig       │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + strategy: enum                     │
│ + template_pattern: ?string          │
│ + apply_to_persons_only: bool        │
│ + enabled: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ MatterDescriptionTransformConfig     │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + strategy: enum                     │
│ + source_field: ?string              │
│ + template_pattern: ?string          │
│ + enabled: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ WorkspaceNamingConfig                │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + template_pattern: string           │
│ + description: ?string               │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ CustomFieldMappingRule               │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + source_type: enum                  │
│ + source_field_name: ?string         │
│ + imanage_custom_field_config_id: ?int│
│ + value_mapping_type: enum           │
│ + static_value: ?string              │
│ + date_format: ?string               │
│ + priority: int                      │
│ + enabled: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ImanageCustomFieldConfig  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ LegacyAliasMapping                   │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + entity_type: enum(client,matter)   │
│ + clio_id: string                    │
│ + imanage_alias: string              │
│ + imported_from: ?string             │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ WebhookProcessingFilter             │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + field_path: string                 │
│ + operator: enum                     │
│ + value: string                      │
│ + action: enum(skip,proceed)         │
│ + priority: int                      │
│ + enabled: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘
```

### Clio Integration Domain

```
┌──────────────────────────────────────┐
│ ClioOAuthAccessToken                 │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + access_token: encrypted            │
│ + refresh_token: encrypted           │
│ + access_expires_at: datetime        │
│ + revoked: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClioMatter                           │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + clio_client_id: ?int               │
│ + clio_practice_area_id: ?int        │
│ + matter_id: ?string                 │
│ + display_number: ?string            │
│ + description: ?string               │
│ + status: ?string                    │
│ + sequence_key: ?string              │
│ + sequence_number: ?int              │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ClioClient                │
│ belongsTo: ClioPracticeArea          │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClioClient                           │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + client_id: ?string                 │
│ + name: ?string                      │
│ + first_name: ?string                │
│ + last_name: ?string                 │
│ + type: ?string                      │
│ + sequence_key: ?string              │
│ + sequence_number: ?int              │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ hasMany: ClioMatters                 │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClioPracticeArea                     │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + name: string                       │
│ + code: ?string                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ hasOne: PracticeAreaMapping          │
│ hasOne: TemplateMapping              │
│ hasMany: ClioMatterStages            │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClioUser                             │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + name: ?string                      │
│ + email: ?string                     │
│ + enabled: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ hasOne: UserMapping                  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClioGroup                            │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + name: string                       │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ hasOne: GroupMapping                 │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ClioMatterStage                      │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + name: string                       │
│ + display_order: ?int                │
│ + clio_practice_area_id: ?int        │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ClioPracticeArea          │
└──────────────────────────────────────┘
```

### iManage Integration Domain

```
┌──────────────────────────────────────┐
│ ImanageOAuthAccessToken              │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + access_token: encrypted            │
│ + refresh_token: encrypted           │
│ + expires_at: datetime               │
│ + revoked: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ Library                              │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + imanage_library_id: string         │
│ + name: string                       │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ hasMany: ImanagePracticeAreas        │
│ hasMany: ImanageSubPracticeAreas     │
│ hasMany: ImanageTemplates            │
│ hasMany: ImanageGroups               │
│ hasMany: ImanageUsers                │
│ hasMany: ImanageClients              │
│ hasMany: ImanageMatters              │
│ hasMany: ImanageWorkspaces           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanagePracticeArea                  │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + key: string                        │
│ + description: ?string               │
│ + library_id: int                    │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Library                   │
│ hasMany: ImanageSubPracticeAreas     │
│ hasMany: PracticeAreaMappings        │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageSubPracticeArea               │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + key: string                        │
│ + description: ?string               │
│ + imanage_practice_area_id: int      │
│ + library_id: int                    │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ImanagePracticeArea       │
│ belongsTo: Library                   │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageTemplate                      │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + imanage_template_id: string        │
│ + description: ?string               │
│ + library_id: int                    │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Library                   │
│ hasMany: ImanageWorkspaces           │
│ hasMany: TemplateMappings            │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageClient                        │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_client_id: ?bigint            │
│ + key: string                        │
│ + ssid: ?string                      │
│ + description: ?string               │
│ + enabled: bool                      │
│ + hipaa: bool                        │
│ + library_id: int                    │
│ + sequence_number: ?int              │
│ + sequence_key: ?string              │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Library                   │
│ belongsTo: WebhookRequest            │
│ hasMany: ImanageMatters              │
│ hasMany: ImanageWorkspaces           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageMatter                        │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + imanage_client_id: int             │
│ + clio_client_id: ?bigint            │
│ + clio_matter_id: ?bigint            │
│ + clio_practice_area_id: ?int        │
│ + key: string                        │
│ + key_numeric: ?int                  │
│ + ssid: ?string                      │
│ + description: ?string               │
│ + closed: bool                       │
│ + parent_id: ?string                 │
│ + library_id: int                    │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ImanageClient             │
│ belongsTo: Library                   │
│ belongsTo: ImanagePracticeArea       │
│ belongsTo: ImanageSubPracticeArea    │
│ belongsTo: ClioPracticeArea          │
│ belongsTo: WebhookRequest            │
│ hasMany: ImanageWorkspaces           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageWorkspace                     │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + imanage_workspace_id: string       │
│ + library_id: int                    │
│ + imanage_template_id: ?int          │
│ + imanage_matter_id: ?int            │
│ + imanage_client_id: ?int            │
│ + name: string                       │
│ + database: string                   │
│ + default_security: ?string          │
│ + has_subfolders: bool               │
│ + owner: ?string                     │
│ + custom1..custom30: ?string         │
│ + iwl: ?string                       │
│ + replica: bool                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Library                   │
│ belongsTo: ImanageTemplate           │
│ belongsTo: ImanageMatter             │
│ belongsTo: ImanageClient             │
│ belongsTo: ImanagePracticeArea       │
│ belongsTo: ImanageSubPracticeArea    │
│ belongsTo: WebhookRequest            │
│ hasOne: WorkspaceSecurityAudit       │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageCustomFieldConfig             │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + custom_field_identifier: string    │
│ + description: string                │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ hasMany: ImanageCustomFields         │
│ hasMany: CustomFieldMappingRules     │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageCustomField                   │
│──────────────────────────────────────│
│ + id: int                            │
│ + imanage_custom_field_config_id: int│
│ + tenant_id: int                     │
│ + key: string                        │
│ + description: string                │
│ + wstype: string                     │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ImanageCustomFieldConfig  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageGroup                         │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + imanage_group_id: string           │
│ + name: string                       │
│ + library_id: int                    │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Library                   │
│ hasMany: GroupMappings               │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ ImanageUser                          │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + imanage_user_id: string            │
│ + full_name: ?string                 │
│ + email: ?string                     │
│ + library_id: int                    │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Library                   │
│ hasMany: UserMappings                │
└──────────────────────────────────────┘
```

### Webhook Processing Domain

```
┌──────────────────────────────────────┐
│ WebhookType                          │
│──────────────────────────────────────│
│ + id: int                            │
│ + name: string                       │
│ + model: string                      │
│ + event: string                      │
│──────────────────────────────────────│
│ hasMany: Webhooks                    │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ Webhook                              │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_id: bigint                    │
│ + webhook_type_id: int               │
│ + url: string                        │
│ + shared_secret: encrypted           │
│ + status: enum                       │
│ + expires_at: ?datetime              │
│ + etag: ?string                      │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: WebhookType               │
│ hasMany: WebhookRequests             │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ WebhookRequest                       │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + webhook_id: ?int                   │
│ + url: string                        │
│ + headers: json                      │
│ + body: json                         │
│ + correlation_id: uuid               │
│ + processing_stage: enum             │
│ + retrieved_client_id: ?string       │
│ + retrieved_matter_id: ?string       │
│ + client_activity_complete: bool     │
│ + matter_activity_complete: bool     │
│ + workspace_activity_complete: bool  │
│ + folder_activity_complete: bool     │
│ + security_activity_complete: bool   │
│ + workspace_link_cfp: bool           │
│ + error_message: ?string             │
│ + error_count: int                   │
│ + started_at: ?datetime              │
│ + completed_at: ?datetime            │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: Webhook                   │
│ hasOne: WorkspaceSecurityAudit       │
│ hasMany: ImanageWorkspaces           │
│ hasMany: ImanageClients              │
│ hasMany: ImanageMatters              │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ WorkspaceSecurityAudit               │
│──────────────────────────────────────│
│ + id: int                            │
│ + webhook_request_id: int            │
│ + tenant_id: int                     │
│ + template_workspace_id: ?string     │
│ + target_workspace_id: string        │
│ + template_security: ?json           │
│ + target_security: ?json             │
│ + diff: ?json                        │
│ + status: enum                       │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: WebhookRequest            │
└──────────────────────────────────────┘
```

### Mapping Domain

```
┌──────────────────────────────────────┐
│ PracticeAreaMapping                  │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_practice_area_id: int         │
│ + imanage_practice_area_id: int      │
│ + imanage_sub_practice_area_id: ?int │
│ + imanage_custom_field_config_id: ?int│
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ClioPracticeArea          │
│ belongsTo: ImanagePracticeArea       │
│ belongsTo: ImanageSubPracticeArea    │
│ belongsTo: ImanageCustomFieldConfig  │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ TemplateMapping                      │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_practice_area_id: int         │
│ + imanage_template_id: int           │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ClioPracticeArea          │
│ belongsTo: ImanageTemplate           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ GroupMapping                         │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_group_id: int                 │
│ + imanage_group_id: int              │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ClioGroup                 │
│ belongsTo: ImanageGroup              │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│ UserMapping                          │
│──────────────────────────────────────│
│ + id: int                            │
│ + tenant_id: int                     │
│ + clio_user_id: int                  │
│ + imanage_user_id: int               │
│──────────────────────────────────────│
│ belongsTo: Tenant                    │
│ belongsTo: ClioUser                  │
│ belongsTo: ImanageUser               │
└──────────────────────────────────────┘
```

---

## 4. Service Layer Class Diagram

### Integration Services

```
┌──────────────────────────────────────────────────────────────────────┐
│ ClioApiService                                                       │
│──────────────────────────────────────────────────────────────────────│
│ - tenant: Tenant                                                     │
│ - baseUrl: string                                                    │
│ - apiVersion: string = '4.0.8'                                       │
│──────────────────────────────────────────────────────────────────────│
│ + __construct(Tenant $tenant)                                        │
│ + getAccessToken(): string                                           │
│ + refreshToken(): ClioOAuthAccessToken                               │
│ + getMatter(int $id, array $fields = []): array                      │
│ + getClient(int $id, array $fields = []): array                      │
│ + getGroup(int $id): array                                           │
│ + getUsers(): Collection                                             │
│ + getGroups(): Collection                                            │
│ + getPracticeAreas(): Collection                                     │
│ + getMatterStages(): Collection                              [NEW]   │
│ + getMatterRelationships(int $matterId): array               [NEW]   │
│ + getMatterRelatedContacts(int $matterId): array             [NEW]   │
│ + createWebhook(string $model, string $event,                       │
│     string $url, array $fields): array                               │
│ + updateWebhook(int $id, string $etag, array $params): array        │
│ + deleteWebhook(int $id): bool                                       │
│ + getCustomField(int $fieldId): array                                │
│ + updateMatterCustomField(int $matterId,                             │
│     int $fieldId, mixed $value): bool                                │
│ + updateContactCustomField(int $contactId,                           │
│     int $fieldId, mixed $value): bool                                │
│ + registerCustomAction(string $label,                        [NEW]   │
│     string $url, string $uiReference): array                        │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: ClioApiHelper.php (all global functions)                   │
│ - get_clio_token()          -> getAccessToken()                      │
│ - get_all_webhooks()        -> [internal to webhook methods]         │
│ - create_webhook()          -> createWebhook()                       │
│ - get_single_clio_matter()  -> getMatter()                           │
│ - get_single_clio_client()  -> getClient()                           │
│ - get_single_clio_group()   -> getGroup()                            │
│ - get_user_details()        -> getUsers()                            │
│ - get_custom_field()        -> getCustomField()                      │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ ImanageApiService                                                    │
│──────────────────────────────────────────────────────────────────────│
│ - tenant: Tenant                                                     │
│ - baseUrl: string                                                    │
│ - customerId: string                                                 │
│──────────────────────────────────────────────────────────────────────│
│ + __construct(Tenant $tenant)                                        │
│ + getAccessToken(): string                                           │
│ + getLibraries(): Collection                                         │
│ + getPracticeAreas(string $libraryId): Collection                    │
│ + getSubPracticeAreas(string $libraryId,                             │
│     string $paKey): Collection                                       │
│ + getTemplates(string $libraryId): Collection                        │
│ + getGroups(string $libraryId): Collection                           │
│ + getUsers(string $libraryId): Collection                            │
│ + findOrCreateClient(string $libraryId,                              │
│     string $key, array $params): array                               │
│ + findOrCreateMatter(string $libraryId, string $key,                 │
│     string $clientKey, array $params): array                         │
│ + findOrCreateWorkspace(string $libraryId,                           │
│     array $params): array                                            │
│ + updateClient(string $libraryId, string $key,                       │
│     array $params): array                                            │
│ + updateMatter(string $libraryId, string $key,                       │
│     string $clientKey, array $params): array                         │
│ + updateWorkspace(string $libraryId,                                 │
│     string $workspaceId, array $params): array                       │
│ + copyTemplateFolders(string $libraryId,                             │
│     string $templateId, string $workspaceId): void                   │
│ + getWorkspaceSecurity(string $libraryId,                            │
│     string $workspaceId): array                                      │
│ + setWorkspaceSecurity(string $libraryId,                            │
│     string $workspaceId, array $params): array                       │
│ + getCustomFields(string $libraryId): Collection                     │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: iManageApiHelper.php (all global functions)                │
│ - get_imanage_token()       -> getAccessToken()                      │
│ - get_single_client()       -> [internal to findOrCreateClient]      │
│ - post_client()             -> [internal to findOrCreateClient]      │
│ - patch_client()            -> updateClient()                        │
│ - get_single_matter()       -> [internal to findOrCreateMatter]      │
│ - post_matter()             -> [internal to findOrCreateMatter]      │
│ - patch_matter()            -> updateMatter()                        │
│ - post_workspace()          -> [internal to findOrCreateWorkspace]   │
│ - patch_workspace()         -> updateWorkspace()                     │
│ - header_items()            -> [private buildHeaders()]              │
│                                                                      │
│ NOTE: findOrCreate* methods implement idempotent                     │
│ check-before-create pattern internally. The caller                   │
│ does not need to check for existence first.                          │
└──────────────────────────────────────────────────────────────────────┘
```

### Configuration Services

```
┌──────────────────────────────────────────────────────────────────────┐
│ TenantConfigurationService                                           │
│──────────────────────────────────────────────────────────────────────│
│ - tenant: Tenant                                                     │
│ - displayNumberParser: DisplayNumberParser                           │
│ - workspaceNameResolver: WorkspaceNameResolver                       │
│──────────────────────────────────────────────────────────────────────│
│ + __construct(Tenant $tenant)                                        │
│ + resolveDisplayNumber(string $displayNumber,                        │
│     array $payload): ParsedIds                                       │
│ + resolveClientName(string $rawName,                                 │
│     string $contactType): string                                     │
│ + resolveMatterDescription(string $rawDescription,                   │
│     array $context): string                                          │
│ + resolveWorkspaceName(array $context): string                       │
│ + resolveCustomFieldMappings(array $payload,                         │
│     array $clioCustomFields): array                                  │
│ + shouldProcess(array $payload): FilterResult                        │
│ + resolveLegacyAlias(string $clioId,                                │
│     string $entityType): ?string                                     │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES:                                                            │
│ - MatterController switch/case (40+ cases)                           │
│ - UpdateMatter::handle() tenant-specific if/else blocks              │
│ - MatterController::checkLegacyMappingFile() (JSON files)            │
│ - MatterController::extractCustomFieldValue()                        │
│ - UpdateMatter::handleAdditionalCustomFields() (7+ tenant blocks)    │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ DisplayNumberParser                                                  │
│──────────────────────────────────────────────────────────────────────│
│ - config: DisplayNumberParsingConfig                                 │
│──────────────────────────────────────────────────────────────────────│
│ + __construct(DisplayNumberParsingConfig $config)                    │
│ + parse(string $displayNumber, array $payload): ParsedIds            │
│ + test(string $displayNumber, array $payload): ParsedIds             │
│ - applySplitDelimiter(string $dn): ParsedIds                        │
│ - applySplitDelimiterNested(string $dn): ParsedIds                   │
│ - applyRegex(string $dn): ParsedIds                                 │
│ - applyBracketExtraction(string $dn): ParsedIds                     │
│ - applyClioIds(array $payload): ParsedIds                            │
│ - applyCustomFieldExtraction(array $payload): ParsedIds              │
│ - applyLegacyAliasLookup(array $payload): ParsedIds                  │
│ - applyPreProcessing(string $dn): string                            │
│ - applyPostProcessing(ParsedIds $ids): ParsedIds                    │
│ - applyFallback(string $dn, array $payload): ParsedIds              │
│──────────────────────────────────────────────────────────────────────│
│ The test() method is identical to parse() but never writes to DB.    │
│ Used by the admin UI "Test" button to preview parsing results.       │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ WorkspaceNameResolver                                                │
│──────────────────────────────────────────────────────────────────────│
│ - config: WorkspaceNamingConfig                                      │
│──────────────────────────────────────────────────────────────────────│
│ + __construct(WorkspaceNamingConfig $config)                         │
│ + resolve(array $context): string                                    │
│ + availableTokens(): array                                           │
│ - replaceTokens(string $template, array $context): string            │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: 25+ named workspace schemas in post_workspace()            │
│ and patch_workspace() global helper functions.                       │
│                                                                      │
│ Available tokens:                                                    │
│   {client_id}             {matter_id}                                │
│   {client_description}    {matter_description}                       │
│   {display_number}        {practice_area}                            │
│   {sub_practice_area}     {open_date}                                │
│   {responsible_attorney}  {originating_attorney}                     │
│   {custom_field:NAME}     (dynamic custom field lookup)              │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ SequenceNumberService                                                │
│──────────────────────────────────────────────────────────────────────│
│ - tenant: Tenant                                                     │
│ - config: TenantSequenceConfig                                       │
│ - clioApi: ClioApiService                                            │
│──────────────────────────────────────────────────────────────────────│
│ + __construct(Tenant $tenant)                                        │
│ + nextClientNumber(): string                                         │
│ + nextMatterNumber(string $clientKey): string                        │
│ + writeBackToClioClient(int $clioClientId,                           │
│     string $sequenceNumber): void                                    │
│ + writeBackToClioMatter(int $clioMatterId,                           │
│     string $sequenceNumber): void                                    │
│ - formatNumber(string $prefix, int $number, int $digits): string     │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: MatterController sequence logic (lines 213-265)            │
│ and UpdateContactCustomFieldValue / UpdateMatterCustomFieldValue     │
│ jobs (writeback is now internal to this service, dispatched           │
│ as a sub-job when needed).                                           │
└──────────────────────────────────────────────────────────────────────┘
```

### Verification & Utility Services

```
┌──────────────────────────────────────────────────────────────────────┐
│ WebhookVerificationService                                           │
│──────────────────────────────────────────────────────────────────────│
│ + verifySignature(string $payload,                                   │
│     string $signature, string $sharedSecret): bool                   │
│ + verifyHandshake(Request $request): ?string                         │
│──────────────────────────────────────────────────────────────────────│
│ NEW in V2. Clio sends X-Hook-Secret on handshake and                 │
│ X-Hook-Signature for payload verification.                           │
│                                                                      │
│ verifyHandshake: returns the secret to echo back,                    │
│   or null if this is not a handshake request.                        │
│ verifySignature: HMAC-SHA256 of raw body against shared_secret.      │
└──────────────────────────────────────────────────────────────────────┘
```

### Value Objects / DTOs

```
┌──────────────────────────────────────────────────────────────────────┐
│ ParsedIds (Value Object)                                             │
│──────────────────────────────────────────────────────────────────────│
│ + clientId: string                                                   │
│ + matterId: string                                                   │
│ + strategy: string          // Which strategy produced these IDs     │
│ + wasLegacyLookup: bool     // True if resolved via legacy mapping   │
│──────────────────────────────────────────────────────────────────────│
│ + isValid(): bool           // Both clientId and matterId non-empty  │
│ + toArray(): array                                                   │
│──────────────────────────────────────────────────────────────────────│
│ Immutable. Created by DisplayNumberParser. Passed through the        │
│ pipeline without mutation. Serializable for logging.                 │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ FilterResult (Value Object)                                          │
│──────────────────────────────────────────────────────────────────────│
│ + shouldProcess: bool                                                │
│ + reason: string            // Human-readable explanation            │
│ + matchedFilter: ?WebhookProcessingFilter                            │
│──────────────────────────────────────────────────────────────────────│
│ + skipped(): bool           // Alias for !shouldProcess              │
│ + toArray(): array                                                   │
│──────────────────────────────────────────────────────────────────────│
│ Immutable. Created by TenantConfigurationService::shouldProcess().   │
│ When shouldProcess is false, the webhook is logged as skipped        │
│ with the reason, and no further processing occurs.                   │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 5. Job Pipeline Class Diagram

### Primary Webhook Processing Jobs

```
┌──────────────────────────────────────────────────────────────────────┐
│ ProcessWebhook                                                [NEW]  │
│──────────────────────────────────────────────────────────────────────│
│ Queue: webhooks                                                      │
│ Tries: 3                                                             │
│ Backoff: [5, 30, 120] seconds                                        │
│ Timeout: 30 seconds                                                  │
│──────────────────────────────────────────────────────────────────────│
│ Input: tenant_id, webhook_id, raw_payload, raw_headers               │
│──────────────────────────────────────────────────────────────────────│
│ Steps:                                                               │
│  1. Load Tenant with config relationships                            │
│  2. Create TenantConfigurationService                                │
│  3. Evaluate webhook processing filters -> FilterResult              │
│     - If FilterResult.skipped: create WR with stage='skipped', exit  │
│  4. Parse display number -> ParsedIds                                │
│  5. Transform client name                                            │
│  6. Transform matter description                                     │
│  7. Create WebhookRequest record (stage='enqueued')                  │
│  8. Save Clio client + matter records                                │
│  9. Handle sequence numbers if configured                            │
│ 10. Dispatch UpdateMatter -> 'imanage' queue                         │
│──────────────────────────────────────────────────────────────────────│
│ Dispatches: UpdateMatter                                             │
│ On failure: Log with correlation_id, mark WR stage='failed'          │
└──────────────────────────────────────────────────────────────────────┘
       |
       | dispatch
       v
┌──────────────────────────────────────────────────────────────────────┐
│ UpdateMatter                                             [REFACTORED]│
│──────────────────────────────────────────────────────────────────────│
│ Queue: imanage                                                       │
│ Tries: 3                                                             │
│ Backoff: [15, 60, 300] seconds                                       │
│ Timeout: 120 seconds                                                 │
│──────────────────────────────────────────────────────────────────────│
│ Input: WebhookRequest (with parsed IDs, transformed names)           │
│──────────────────────────────────────────────────────────────────────│
│ Steps:                                                               │
│  1. Acquire tenant job lock (DB + Redis)                             │
│  2. Mark WR stage='processing'                                       │
│  3. Instantiate ImanageApiService                                    │
│  4. findOrCreateClient -> update WR client_activity_complete         │
│  5. findOrCreateMatter -> update WR matter_activity_complete         │
│  6. Resolve workspace name (WorkspaceNameResolver)                   │
│  7. Resolve custom field mappings (TenantConfigurationService)       │
│  8. findOrCreateWorkspace or updateWorkspace                         │
│  9. Update WR workspace_activity_complete                            │
│ 10. If new workspace: dispatch CreateWorkspaceFolders                │
│ 11. If replica enabled: create replica workspace                     │
│ 12. Dispatch PostWorkspaceSecurity or handle group security          │
│ 13. If workspace link enabled: dispatch PopulateWSLinkCustomField    │
│ 14. Mark WR stage='post_processing'                                  │
│ 15. Release tenant lock in finally{} block                           │
│──────────────────────────────────────────────────────────────────────│
│ Dispatches: CreateWorkspaceFolders, PostWorkspaceSecurity,           │
│             AuditWorkspaceSecurity, PopulateWorkspaceLinkCustomField  │
│ On failure: Log, mark WR stage='failed', release lock                │
└──────────────────────────────────────────────────────────────────────┘
       |
       | dispatch (parallel)
       v
┌──────────────────────────────────────────────────────────────────────┐
│ CreateWorkspaceFolders                                               │
│──────────────────────────────────────────────────────────────────────│
│ Queue: long_term                                                     │
│ Tries: 3  |  Backoff: [30, 120, 600]  |  Timeout: 300 seconds       │
│──────────────────────────────────────────────────────────────────────│
│ Input: ImanageTemplate, ImanageWorkspace, ImanageClient, ?Matter     │
│ Action: Copies folder structure from template to workspace           │
│ Updates: WR folder_activity_complete = true                          │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ PostWorkspaceSecurity                                                │
│──────────────────────────────────────────────────────────────────────│
│ Queue: long_term                                                     │
│ Tries: 3  |  Backoff: [30, 120, 600]  |  Timeout: 120 seconds       │
│──────────────────────────────────────────────────────────────────────│
│ Input: tenant_id, template_workspace_id, target_workspace_id, wr_id  │
│ Action: Copies security config from template workspace to target     │
│ Updates: WR security_activity_complete = true                        │
│ Dispatches: AuditWorkspaceSecurity                                   │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ AuditWorkspaceSecurity                                               │
│──────────────────────────────────────────────────────────────────────│
│ Queue: long_term                                                     │
│ Tries: 2  |  Backoff: [60]  |  Timeout: 60 seconds                  │
│──────────────────────────────────────────────────────────────────────│
│ Input: tenant_id, wr_id, workspace_id, template_id, audit metadata   │
│ Action: Fetches actual security, compares to expected, records diff   │
│ Creates: WorkspaceSecurityAudit record                               │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ PopulateWorkspaceLinkCustomField                                     │
│──────────────────────────────────────────────────────────────────────│
│ Queue: long_term                                                     │
│ Tries: 3  |  Backoff: [15, 60]  |  Timeout: 30 seconds              │
│──────────────────────────────────────────────────────────────────────│
│ Input: Tenant, clio_matter_id, workspace_id, wr_id                   │
│ Action: Writes iManage Work Link (IWL) back to Clio custom field     │
│ Updates: WR workspace_link_custom_field_populated = true             │
│ Marks WR stage='completed' if all activities done                    │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ DeleteWorkspaceFolders                                               │
│──────────────────────────────────────────────────────────────────────│
│ Queue: long_term                                                     │
│ Tries: 2  |  Timeout: 120 seconds                                   │
│──────────────────────────────────────────────────────────────────────│
│ Input: WebhookRequest (for matter.deleted events)                    │
│ Action: Removes workspace folders in iManage                         │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ DeleteMatter                                                         │
│──────────────────────────────────────────────────────────────────────│
│ Queue: imanage                                                       │
│ Tries: 2  |  Timeout: 60 seconds                                    │
│──────────────────────────────────────────────────────────────────────│
│ Input: WebhookRequest                                                │
│ Action: Marks matter as closed/deleted in iManage                    │
└──────────────────────────────────────────────────────────────────────┘
```

### Sync Jobs

```
┌──────────────────────────────────────────────────────────────────────┐
│ SyncClioData                                                 [NEW]   │
│──────────────────────────────────────────────────────────────────────│
│ Queue: maintenance                                                   │
│ Tries: 2  |  Timeout: 300 seconds                                   │
│──────────────────────────────────────────────────────────────────────│
│ Input: Tenant                                                        │
│ Action: Combined sync of Clio users, groups, practice areas,         │
│         and matter stages via ClioApiService                         │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: FetchClioUsers, FetchClioGroups (separate jobs in V1)      │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ SyncImanageLibraries                                                 │
│──────────────────────────────────────────────────────────────────────│
│ Queue: maintenance                                                   │
│ Tries: 2  |  Timeout: 120 seconds                                   │
│──────────────────────────────────────────────────────────────────────│
│ Input: Tenant                                                        │
│ Action: Syncs iManage libraries                                      │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ SyncImanageData                                              [NEW]   │
│──────────────────────────────────────────────────────────────────────│
│ Queue: maintenance                                                   │
│ Tries: 2  |  Timeout: 300 seconds                                   │
│──────────────────────────────────────────────────────────────────────│
│ Input: Tenant, Library                                               │
│ Action: Combined sync of practice areas, sub-practice areas,         │
│         templates, groups, users, custom fields via ImanageApiService │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: SyncPracticeAreas, SyncTemplates, SyncImanageGroups,       │
│           SyncImanageUsers, SyncAdditionalImanageCustomFields         │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ RefreshClioTokens                                                    │
│──────────────────────────────────────────────────────────────────────│
│ Queue: maintenance                                                   │
│ Tries: 3  |  Timeout: 60 seconds                                    │
│ Schedule: Every 30 minutes                                           │
│──────────────────────────────────────────────────────────────────────│
│ Action: Refreshes expiring Clio OAuth tokens for all active tenants   │
│ On failure: Sends ClioAccessTokenRefreshFailed mail                  │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ RefreshImanageTokens                                                 │
│──────────────────────────────────────────────────────────────────────│
│ Queue: maintenance                                                   │
│ Tries: 3  |  Timeout: 60 seconds                                    │
│ Schedule: Every 30 minutes                                           │
│──────────────────────────────────────────────────────────────────────│
│ Action: Refreshes expiring iManage OAuth tokens (non-password tenants)│
│ On failure: Sends ImanageAccessTokenRefreshFailed mail               │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ RenewWebhookExpiries                                         [NEW]   │
│──────────────────────────────────────────────────────────────────────│
│ Queue: maintenance                                                   │
│ Tries: 3  |  Timeout: 120 seconds                                   │
│ Schedule: Daily at 02:00 UTC                                         │
│──────────────────────────────────────────────────────────────────────│
│ Action: Extends expiry for all webhooks expiring within 7 days       │
│ On failure: Sends WebhookExtensionFailed mail                        │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: ExtendWebhooksExpiriesAt (manually triggered in V1)        │
└──────────────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────────┐
│ SendWebhookSummary                                                   │
│──────────────────────────────────────────────────────────────────────│
│ Queue: notifications                                                 │
│ Tries: 2  |  Timeout: 120 seconds                                   │
│ Schedule: Daily at 08:00 UTC                                         │
│──────────────────────────────────────────────────────────────────────│
│ Action: Generates and sends daily webhook processing summary email   │
└──────────────────────────────────────────────────────────────────────┘
```

### Reattempt Jobs

```
┌──────────────────────────────────────────────────────────────────────┐
│ ReattemptWebhookRequest                                      [NEW]   │
│──────────────────────────────────────────────────────────────────────│
│ Queue: webhooks                                                      │
│ Tries: 1  |  Timeout: 120 seconds                                   │
│──────────────────────────────────────────────────────────────────────│
│ Input: WebhookRequest $webhookRequest, ?string $fromStage            │
│ Action:                                                              │
│  1. Determines which activities are incomplete                       │
│  2. Re-runs only the failed/incomplete portion of the pipeline       │
│  3. If fromStage is null, examines activity flags to determine       │
│     the correct restart point:                                       │
│     - client_activity_complete=false  -> restart from client step    │
│     - matter_activity_complete=false  -> restart from matter step    │
│     - workspace_activity_complete=false -> restart from workspace    │
│     - folder_activity_complete=false   -> dispatch CreateWSFolders   │
│     - security_activity_complete=false -> dispatch PostWSSecurity    │
│  4. Marks WR as reattempted with user_id and timestamp               │
│──────────────────────────────────────────────────────────────────────│
│ REPLACES: ReattemptClientActivity, ReattemptMatterActivity,          │
│           ReattemptWorkspaceActivity, ReattemptFolderActivity,        │
│           ReattemptSecurityActivity (5 separate jobs in V1)           │
└──────────────────────────────────────────────────────────────────────┘
```

### Job Dependency Graph

```
                    WebhookController
                          |
                   ProcessWebhook
                     (webhooks)
                          |
                     UpdateMatter
                      (imanage)
                    /     |     \
                   /      |      \
    CreateWSFolders  PostWSSecurity  PopulateWSLink
     (long_term)      (long_term)     (long_term)
                          |
                   AuditWSSecurity
                     (long_term)
```

---

## 6. Webhook Processing Data Flow

### Complete Sequence Diagram

```
CLIO                    WEBHOOK CONTROLLER          REDIS/QUEUE             PROCESS WEBHOOK        UPDATE MATTER           IMANAGE
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |== HANDSHAKE PHASE =========|                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |-- POST /webhook/matter     |                         |                      |                      |                      |
  |   X-Hook-Secret: abc123 -->|                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |                         [1. WebhookVerification      |                      |                      |                      |
  |                            Service.verifyHandshake]  |                      |                      |                      |
  |                            |-- return secret         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |<-- 200 OK                  |                         |                      |                      |                      |
  |   X-Hook-Secret: abc123 --|                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |== DATA PHASE ==============|                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |-- POST /webhook/matter/    |                         |                      |                      |                      |
  |   {tenant_reference}       |                         |                      |                      |                      |
  |   Body: {data:{...}}       |                         |                      |                      |                      |
  |   X-Hook-Signature: hmac ->|                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |                         [2. Verify HMAC-SHA256        |                      |                      |                      |
  |                            signature against          |                      |                      |                      |
  |                            shared_secret]             |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |                         [3. Lookup tenant by          |                      |                      |                      |
  |                            UUID reference]            |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |                         [4. Check tenant has          |                      |                      |                      |
  |                            active subscription]       |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |<-- 200 OK (immediate) ----|                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |                            |-- dispatch              |                      |                      |                      |
  |                            |   ProcessWebhook ------>|-- enqueue            |                      |                      |
  |                            |                         |   (webhooks queue)    |                      |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |== DEQUEUE ===========>|                      |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [5. Load tenant +         |                      |
  |                            |                         |                      all config rels]       |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [6. Evaluate webhook      |                      |
  |                            |                         |                      processing filters]    |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |-- FilterResult:      |                      |
  |                            |                         |                      |   shouldProcess=true  |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [7. Parse display_number  |                      |
  |                            |                         |                      via DisplayNumberParser]|                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |-- ParsedIds:         |                      |
  |                            |                         |                      |   clientId="10095"   |                      |
  |                            |                         |                      |   matterId="39"      |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [8. Transform client name |                      |
  |                            |                         |                      via TenantConfigSvc]   |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [9. Transform matter      |                      |
  |                            |                         |                      description]           |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [10. Create WebhookRequest|                      |
  |                            |                         |                       record with           |                      |
  |                            |                         |                       correlation_id (UUID)]|                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [11. Save ClioClient +    |                      |
  |                            |                         |                       ClioMatter records]   |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                   [12. Handle sequence      |                      |
  |                            |                         |                       numbers if config'd]  |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |-- dispatch           |                      |
  |                            |                         |                      |   UpdateMatter ------>|                      |
  |                            |                         |                      |   (imanage queue)     |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [13. Acquire tenant      |
  |                            |                         |                      |                      job lock (DB row)]    |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- If locked:         |
  |                            |                         |                      |                      |   release(15s)       |
  |                            |                         |                      |                      |   retry later        |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [14. Instantiate         |
  |                            |                         |                      |                      ImanageApiService]    |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- findOrCreateClient |
  |                            |                         |                      |                      |   (GET check) ------>|
  |                            |                         |                      |                      |<-- 404 not found ----|
  |                            |                         |                      |                      |   (POST create) ---->|
  |                            |                         |                      |                      |<-- 201 created ------|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- WR.client_activity |
  |                            |                         |                      |                      |   = true             |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [15. Save ImanageClient  |
  |                            |                         |                      |                      record locally]       |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- findOrCreateMatter |
  |                            |                         |                      |                      |   (GET check) ------>|
  |                            |                         |                      |                      |<-- 404 not found ----|
  |                            |                         |                      |                      |   (POST create) ---->|
  |                            |                         |                      |                      |<-- 201 created ------|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- WR.matter_activity |
  |                            |                         |                      |                      |   = true             |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [16. Save ImanageMatter  |
  |                            |                         |                      |                      record locally]       |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [17. Resolve workspace   |
  |                            |                         |                      |                      name via              |
  |                            |                         |                      |                      WorkspaceNameResolver]|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [18. Resolve custom      |
  |                            |                         |                      |                      field mappings via    |
  |                            |                         |                      |                      TenantConfigService]  |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- findOrCreateWS     |
  |                            |                         |                      |                      |   or updateWorkspace |
  |                            |                         |                      |                      |   (POST/PATCH) ----->|
  |                            |                         |                      |                      |<-- 200/201 ---------|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- WR.workspace_act   |
  |                            |                         |                      |                      |   = true             |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [19. Save ImanageWS     |
  |                            |                         |                      |                      record locally]       |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [20. If NEW workspace:  |
  |                            |                         |                      |                      dispatch              |
  |                            |                         |                      |                      CreateWSFolders       |
  |                            |                         |                      |                      -> long_term queue]   |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- copyTemplateFolders|
  |                            |                         |                      |                      |   (POST) ----------->|
  |                            |                         |                      |                      |<-- 200 --------------|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- WR.folder_activity |
  |                            |                         |                      |                      |   = true             |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [21. Dispatch            |
  |                            |                         |                      |                      PostWSSecurity        |
  |                            |                         |                      |                      -> long_term queue]   |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- setWSSecurity      |
  |                            |                         |                      |                      |   (POST) ----------->|
  |                            |                         |                      |                      |<-- 200 --------------|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- WR.security_act    |
  |                            |                         |                      |                      |   = true             |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [22. Dispatch            |
  |                            |                         |                      |                      AuditWSSecurity       |
  |                            |                         |                      |                      -> long_term]         |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- getWSSecurity      |
  |                            |                         |                      |                      |   (GET) ------------>|
  |                            |                         |                      |                      |<-- security data ----|
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                      |-- Compare & create  |
  |                            |                         |                      |                      |   SecurityAudit rec  |
  |                            |                         |                      |                      |                      |
  |<-- updateMatterCustomField-|------- (if ws link enabled) ------------------|-- ClioApiService ---->|                      |
  |    (IWL writeback)         |                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |<-- updateContactCustomField (if sequence enabled) --|-- ClioApiService ---->|                      |                      |
  |    (sequence writeback)    |                         |                      |                      |                      |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [23. Mark WR             |
  |                            |                         |                      |                      stage='completed'     |
  |                            |                         |                      |                      completed_at=now()]   |
  |                            |                         |                      |                      |                      |
  |                            |                         |                      |                   [24. Release tenant      |
  |                            |                         |                      |                      job lock]             |
  |                            |                         |                      |                      |                      |
```

### Processing Stage Transitions

```
received -> validated -> parsed -> filtered -> enqueued -> processing -> post_processing -> completed
                                      |                        |
                                      v                        v
                                   skipped                   failed
```

---

## 7. Role & Permission Design

### Permission Matrix

| Permission | super_admin | admin | support | tenant_admin | tenant_viewer |
|:-----------|:----------:|:-----:|:-------:|:------------:|:------------:|
| **Tenant Management** | | | | | |
| tenants.view | X | X | X | -- | -- |
| tenants.create | X | X | -- | -- | -- |
| tenants.edit | X | X | -- | -- | -- |
| tenants.delete | X | -- | -- | -- | -- |
| tenants.archive | X | X | -- | -- | -- |
| **Configuration** | | | | | |
| configs.view | X | X | X | -- | -- |
| configs.edit | X | X | -- | -- | -- |
| configs.test | X | X | X | -- | -- |
| **Webhooks** | | | | | |
| webhooks.view | X | X | X | -- | -- |
| webhooks.create | X | X | -- | -- | -- |
| webhooks.delete | X | X | -- | -- | -- |
| webhooks.reattempt | X | X | X | -- | -- |
| webhook_requests.view | X | X | X | -- | -- |
| webhook_requests.export | X | X | -- | -- | -- |
| **Subscriptions** | | | | | |
| subscriptions.view | X | X | X | -- | -- |
| subscriptions.create | X | X | -- | -- | -- |
| subscriptions.void | X | X | -- | -- | -- |
| **User Management** | | | | | |
| users.view | X | X | X | -- | -- |
| users.create | X | X | -- | -- | -- |
| users.edit | X | X | -- | -- | -- |
| users.delete | X | -- | -- | -- | -- |
| users.invite | X | X | -- | -- | -- |
| **Sync Operations** | | | | | |
| sync.trigger | X | X | -- | -- | -- |
| sync.view | X | X | X | -- | -- |
| **Mappings** | | | | | |
| mappings.view | X | X | X | -- | -- |
| mappings.edit | X | X | -- | -- | -- |
| **Sequences** | | | | | |
| sequences.view | X | X | X | -- | -- |
| sequences.edit | X | X | -- | -- | -- |
| sequences.reset | X | -- | -- | -- | -- |
| **System** | | | | | |
| system.settings | X | -- | -- | -- | -- |
| system.health | X | X | -- | -- | -- |
| system.logs | X | X | X | -- | -- |
| **Tenant Portal** | | | | | |
| portal.view | -- | -- | -- | X | X |
| portal.config.view | -- | -- | -- | X | X |
| portal.config.edit | -- | -- | -- | X | -- |

**Notes:**
- `super_admin`: Inherits all permissions. Can manage other admins, delete tenants, access system settings, reset sequences.
- `admin`: Full operational access. Cannot delete tenants, manage system settings, or delete users.
- `support`: Read-only with reattempt capability. Can view everything, trigger reattempts, but cannot modify configs or create records.
- `tenant_admin`: Scoped to their own tenant via `tenant_id` on the User model. Portal permissions only. Can toggle limited settings (e.g., workspace link custom field).
- `tenant_viewer`: Scoped to their own tenant. Read-only portal access (webhook activity dashboard, status).

### Tenant Isolation

Tenant-scoped users (`tenant_id IS NOT NULL`) are automatically filtered via:

1. **Global Scope on Models:** A `TenantScope` automatically adds `WHERE tenant_id = ?` to all queries when the authenticated user has a `tenant_id`.
2. **Middleware:** `EnsureTenantAccess` middleware verifies that any route parameter `{tenant}` matches the user's `tenant_id`.
3. **Policy Layer:** Model policies check `$user->tenant_id === $model->tenant_id` for all tenant-scoped models.

---

## 8. Onboarding Wizard Flow

### Step-by-Step Flow

```
Step 1: Basic Info                  Step 2: Clio Connection
+---------------------------+       +---------------------------+
| Tenant Name: [________]  |       | Clio Region: [dropdown]   |
| Slug: [auto-generated]   |  -->  | App ID: [________]        |
| Contact Email: [________]|       | App Secret: [________]    |
| Contact Name: [________] |       | [Authorize with Clio] btn |
+---------------------------+       +---------------------------+
                                           |
    DB: Create Tenant (pending)            | OAuth flow
    DB: Create User (tenant_admin)         | DB: Store ClioOAuthAccessToken
    DB: Assign tenant_admin role           | DB: Update tenant clio_app_id/secret
                                           |
                                           v
Step 3: iManage Connection          Step 4: Library Selection
+---------------------------+       +---------------------------+
| Cloud URL: [________]    |       | Available Libraries:      |
| Customer ID: [________]  |  -->  | ( ) ACTIVE                |
| Auth Method:              |       | ( ) ARCHIVE               |
|   ( ) OAuth               |       |                           |
|   ( ) Password            |       | [Select Default Library]  |
| [Connect to iManage] btn |       +---------------------------+
+---------------------------+              |
       |                                   | API: ImanageApiService.getLibraries()
       | API: getAccessToken()             | DB: Sync libraries
       | DB: Store credentials             | DB: Create TenantSetting (library_id)
       v                                   v
Step 5: Template & Defaults         Step 6: Display Number Config
+---------------------------+       +---------------------------+
| Default Template:         |       | Strategy: [dropdown]      |
|   [dropdown from sync]    |       | Delimiter: [________]     |
| Default HIPAA: [ ] no     |  -->  | Client Position: [__]     |
| Default Enabled: [x] yes  |       | Matter Position: [__]     |
| Replica WS: [ ] no        |       | Test Input: [________]    |
+---------------------------+       | [Test Parse] btn          |
       |                            | Result: C=10095, M=39     |
       | API: SyncImanageData job   +---------------------------+
       | DB: Sync templates, PAs          |
       | DB: Update TenantSetting         | DB: Create DisplayNumberParsingConfig
       v                                   v
Step 7: Naming & Transforms        Step 8: Webhook Setup
+---------------------------+       +---------------------------+
| Client Name Transform:    |       | Setting up webhooks...    |
|   ( ) None                |       |                           |
|   ( ) Last Name First     |  -->  | [x] Matter Created        |
|   ( ) Reverse Words       |       | [x] Matter Updated        |
| Matter Desc Transform:    |       | [ ] Matter Deleted         |
|   ( ) None                |       |                           |
|   ( ) Use Display Number  |       | [Create Webhooks] btn     |
| Workspace Name Template:  |       +---------------------------+
|   [{client_id} - {desc}] |              |
+---------------------------+              | API: ClioApiService.createWebhook()
       |                                   |      x3 (one per event type)
       | DB: ClientNameTransformConfig     | DB: Create Webhook records
       | DB: MatterDescTransformConfig     | DB: Store shared_secrets (encrypted)
       | DB: WorkspaceNamingConfig         v
       v
Step 9: Subscription               Step 10: Review & Activate
+---------------------------+       +---------------------------+
| Start Date: [________]   |       | Tenant: Acme Law          |
| End Date: [________]     |       | Region: US                |
| Reference: [________]    |  -->  | Library: ACTIVE            |
| Clio Users: [auto-count] |       | Template: Default          |
+---------------------------+       | Strategy: split_delimiter  |
       |                            | Webhooks: 3 active         |
       | DB: Create                 | Subscription: Active       |
       |     TenantSubscription     |                           |
       v                            | [Activate Tenant] btn     |
                                    +---------------------------+
                                           |
                                           | DB: Tenant.status = 'active'
                                           | DB: Tenant.onboarded_at = now()
                                           | Dispatch: SyncClioData
                                           | Dispatch: SyncImanageData
```

### API Calls Made During Onboarding

| Step | API Calls | Records Created |
|------|-----------|----------------|
| 1 | None | Tenant, User |
| 2 | Clio OAuth authorize + token exchange | ClioOAuthAccessToken |
| 3 | iManage token request (password or OAuth) | ImanageOAuthAccessToken (if OAuth) |
| 4 | ImanageApiService.getLibraries() | Library records |
| 5 | SyncImanageData job (templates, PAs, custom fields) | ImanageTemplate, ImanagePracticeArea, etc. |
| 6 | None (local parsing test only) | DisplayNumberParsingConfig |
| 7 | None | ClientNameTransformConfig, MatterDescTransformConfig, WorkspaceNamingConfig |
| 8 | ClioApiService.createWebhook() x N | Webhook records |
| 9 | ClioApiService.getUsers() (for count) | TenantSubscription |
| 10 | None | Updates Tenant status/onboarded_at |

---

## 9. Queue Architecture

### Queue Configuration

| Queue | Purpose | Priority | Workers | Timeout | Max Tries | Backoff |
|-------|---------|----------|---------|---------|-----------|---------|
| `webhooks` | Incoming webhook processing (ProcessWebhook, ReattemptWebhookRequest) | HIGH | 4 | 30s | 3 | 5, 30, 120s |
| `imanage` | iManage API calls (UpdateMatter, DeleteMatter) | HIGH | 3 | 120s | 3 | 15, 60, 300s |
| `long_term` | Folder creation, security setup, audits, IWL writeback | MEDIUM | 2 | 300s | 3 | 30, 120, 600s |
| `maintenance` | Token refresh, webhook renewal, sync jobs | LOW | 1 | 300s | 3 | 60, 300s |
| `notifications` | Email and notification sending | LOW | 1 | 120s | 2 | 60s |
| `default` | General purpose, fallback | LOW | 1 | 60s | 3 | 30s |

### V1 to V2 Queue Name Mapping

| V1 Queue | V2 Queue | Rationale |
|----------|----------|-----------|
| `mid_ii` | `webhooks` | Clearer naming; this is webhook initial processing |
| `mid` | `imanage` | Clearer naming; these are iManage API operations |
| `long_term` | `long_term` | Kept as-is; well-understood |
| (none) | `maintenance` | New; separates background tasks from processing |
| (none) | `notifications` | New; isolates email sending |

### Horizon Configuration

```php
// config/horizon.php

'environments' => [
    'production' => [
        'webhook-workers' => [
            'connection' => 'redis',
            'queue' => ['webhooks'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 2,
            'maxProcesses' => 6,
            'balanceMaxShift' => 2,
            'balanceCooldown' => 3,
            'memory' => 256,
            'timeout' => 30,
            'tries' => 3,
            'nice' => 0,
        ],

        'imanage-workers' => [
            'connection' => 'redis',
            'queue' => ['imanage'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 2,
            'maxProcesses' => 4,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 3,
            'memory' => 256,
            'timeout' => 120,
            'tries' => 3,
            'nice' => 0,
        ],

        'long-term-workers' => [
            'connection' => 'redis',
            'queue' => ['long_term'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'minProcesses' => 1,
            'maxProcesses' => 3,
            'balanceMaxShift' => 1,
            'balanceCooldown' => 5,
            'memory' => 256,
            'timeout' => 300,
            'tries' => 3,
            'nice' => 5,
        ],

        'maintenance-workers' => [
            'connection' => 'redis',
            'queue' => ['maintenance', 'notifications', 'default'],
            'balance' => 'simple',
            'minProcesses' => 1,
            'maxProcesses' => 2,
            'memory' => 128,
            'timeout' => 300,
            'tries' => 3,
            'nice' => 10,
        ],
    ],
],
```

### Scheduled Jobs (Task Scheduler)

| Job | Schedule | Queue |
|-----|----------|-------|
| RefreshClioTokens | Every 30 minutes | maintenance |
| RefreshImanageTokens | Every 30 minutes | maintenance |
| RenewWebhookExpiries | Daily at 02:00 UTC | maintenance |
| SendWebhookSummary | Daily at 08:00 UTC | notifications |
| SyncClioData (all active tenants) | Daily at 03:00 UTC | maintenance |
| SyncImanageData (all active tenants) | Daily at 04:00 UTC | maintenance |
| Prune stale tenant job locks (>30 min) | Every 15 minutes | maintenance |

---

## 10. Key Design Decisions & Rationale

### 1. Service Classes Over Global Helpers

**Decision:** Replace `ClioApiHelper.php` and `iManageApiHelper.php` (global functions) with `ClioApiService` and `ImanageApiService` (injectable classes).

**Rationale:**
- V1 global helpers (`get_clio_token()`, `post_client()`, `patch_workspace()`, etc.) cannot be mocked in tests, making the job pipeline untestable without hitting live APIs.
- Services receive `Tenant` in the constructor, eliminating the need to pass tenant-related parameters (base URL, customer ID, access token) to every function call. The V1 `UpdateMatter` job passes 6-8 parameters to each helper call.
- Dependency injection makes it explicit which external systems a job depends on.
- Services can implement internal caching (e.g., caching the access token for the duration of a job execution) without polluting the call site.

### 2. Database-Driven Configuration Over Code

**Decision:** Replace all tenant-specific `switch/case` and `if/else` blocks with database configuration tables (`display_number_parsing_configs`, `client_name_transformation_configs`, etc.).

**Rationale:**
- The V1 `MatterController::create_webhook_request()` method contains a 450+ line switch statement with 40+ cases, one per tenant. Every new tenant requires a code deployment.
- The V1 `UpdateMatter::handle()` method contains 7+ tenant-specific blocks for custom field mapping and name transformation. These are duplicated across create and update paths.
- Database-driven config allows onboarding via the admin UI. The `DisplayNumberParsingConfig` table with its `strategy` enum captures every parsing pattern observed across all V1 tenants.
- The "Test" button in the admin UI (`DisplayNumberParser::test()`) lets admins verify parsing before activating a tenant, eliminating the deploy-and-pray cycle.

### 3. Redis Over Database Queue Driver

**Decision:** Use Redis as the queue backend (via Laravel Horizon) instead of the database queue driver.

**Rationale:**
- Webhook processing is latency-sensitive. Clio expects a fast 200 response; job pickup must be near-instant.
- Redis supports atomic operations for tenant job locking without the `SELECT ... FOR UPDATE` pattern currently used in V1 (which holds database connections during lock waits).
- Horizon provides real-time monitoring, auto-scaling, and metrics that the database driver lacks.
- Redis pub/sub enables future real-time webhook status updates to the Livewire dashboard.

### 4. Unified ProcessWebhook Job as Entry Point

**Decision:** Introduce `ProcessWebhook` as a new job that handles validation, parsing, filtering, and data preparation before dispatching `UpdateMatter`.

**Rationale:**
- In V1, the controller (`MatterController`) does too much: display number parsing (450+ lines), Clio client/matter record creation, sequence number generation, webhook request creation, AND job dispatching. This violates the "controllers receive and validate; jobs process" principle.
- Separating `ProcessWebhook` from `UpdateMatter` allows the webhook queue (`webhooks`) to handle the CPU-bound parsing/filtering work while the iManage queue (`imanage`) handles the I/O-bound API calls. These have different scaling characteristics.
- `ProcessWebhook` runs with a 30-second timeout (parsing is fast); `UpdateMatter` runs with a 120-second timeout (API calls are slow). Mixing these in one job means either the timeout is too short for API calls or too long to detect parsing failures.
- The reattempt flow benefits: `ReattemptWebhookRequest` can restart from `UpdateMatter` without re-parsing.

### 5. ParsedIds and FilterResult as Value Objects

**Decision:** Use dedicated value objects instead of associative arrays for parsed display number results and filter evaluation results.

**Rationale:**
- V1 passes `$client_id` and `$matter_id` as separate local variables through 700+ lines of controller code. A typo or missed assignment causes silent data corruption (null client_id creates orphaned workspaces).
- `ParsedIds` is immutable. Once created by `DisplayNumberParser`, neither `clientId` nor `matterId` can be accidentally overwritten. The `isValid()` method provides a single checkpoint.
- `ParsedIds::strategy` records which parsing strategy produced the IDs, enabling debugging ("why did this matter get client_id=CLIO-12345?" -- "because strategy was `clio_ids`").
- `FilterResult` replaces the V1 pattern of returning `null` from `create_webhook_request()` to signal "don't process." The `reason` field provides audit trail for skipped webhooks ("Skipped: iManage Creation picklist value is not 'yes'").

### 6. Tenant Isolation via Global Scopes

**Decision:** Apply a `TenantScope` global scope to all tenant-scoped models rather than adding `->where('tenant_id', $tenantId)` to every query.

**Rationale:**
- V1 queries are inconsistent. Some filter by `tenant_id`, others don't (e.g., `ClioPracticeArea::query()->where('clio_id', '=', ...)` in `MatterController` line 43 has no tenant filter).
- A global scope guarantees tenant isolation at the model level. Forgetting to filter is impossible.
- For back-office admin queries (cross-tenant), the scope is explicitly bypassed with `withoutGlobalScope(TenantScope::class)`.
- Job processing sets the "current tenant" at the start of `ProcessWebhook::handle()`, and all subsequent model queries are automatically scoped.

### 7. UUID Reference in Webhook URLs

**Decision:** Use `tenants.reference` (UUID v4) in webhook callback URLs (`/webhook/matter/{reference}`) instead of `tenants.id` (auto-increment integer).

**Rationale:**
- V1 already uses this pattern (see `MatterController::clio_matter_created_webhook($request, $tenant_reference)`). It is carried forward because:
  - Auto-increment IDs are sequential and guessable. An attacker could enumerate all tenants by incrementing the ID.
  - UUIDs are 128-bit random, making URL enumeration infeasible.
  - The URL format `POST /webhook/matter/550e8400-e29b-41d4-a716-446655440000` leaks no information about tenant count, creation order, or database structure.

### 8. Check-Before-Create (Idempotency) Pattern

**Decision:** `ImanageApiService::findOrCreateClient()`, `findOrCreateMatter()`, and `findOrCreateWorkspace()` internally check for existence (GET) before creating (POST), and return the existing entity if found.

**Rationale:**
- V1 implements this pattern inline in `UpdateMatter::handle()` (lines 214-232 for clients, 265-283 for matters) but inconsistently. The workspace check (lines 324-343) has a special case for Siri Glimstad.
- Encapsulating check-before-create in the service layer means:
  - Every caller gets idempotent behavior automatically.
  - The "exists but needs update" path (PATCH) is handled internally.
  - Retry-safe: if `UpdateMatter` fails after creating the client but before creating the matter, the retry will find the existing client and proceed to matter creation.
- The service returns a unified response (`array` with iManage entity data) regardless of whether it created or found the entity.

### 9. Token Encryption Strategy

**Decision:** All OAuth tokens, API keys, and secrets are encrypted at rest using Laravel's `encrypt()`/`decrypt()` functions (AES-256-CBC with `APP_KEY`).

**Rationale:**
- V1 stores some credentials encrypted (e.g., `imanage_password` uses `decrypt()` in the helper) but others appear to be plaintext or inconsistently encrypted.
- V2 uses Laravel's `encrypted` cast on all sensitive Eloquent attributes:
  ```php
  protected $casts = [
      'clio_app_id' => 'encrypted',
      'clio_app_secret' => 'encrypted',
      'imanage_app_id' => 'encrypted',
      // ...
  ];
  ```
- This means encryption/decryption is transparent. Code reads `$tenant->clio_app_secret` and gets the plaintext value; the database column stores ciphertext.
- The `APP_KEY` is the single secret that must be protected. Key rotation is handled via Laravel's `php artisan key:rotate` command (available in Laravel 11+).
- Tokens are never logged. The `ClioApiService` and `ImanageApiService` use `Log::debug()` for request/response bodies but explicitly redact token values.

---

*End of V2 Technical Design Document*
