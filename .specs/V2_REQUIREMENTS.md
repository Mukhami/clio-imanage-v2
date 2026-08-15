# V2 Requirements & Module Breakdown

**Document Version:** 2.0
**Last Updated:** 2026-08-14
**Status:** Master Brief - Ready for Development
**Stack:** Laravel 11+ / Livewire 3 / Tailwind CSS 3

---

## 1. Project Overview

### What This Application Does

This application is a middleware bridge between **Clio** (legal practice management SaaS) and **iManage** (document management system). It receives webhooks from Clio when matters and clients are created or updated, processes and maps the incoming data according to tenant-specific rules, and creates or updates corresponding workspaces, clients, and matters in iManage.

### Multi-Tenant Architecture

Each tenant represents a law firm. Each firm has its own Clio account and iManage environment. During onboarding, the application obtains OAuth credentials for both systems and acts as the persistent bridge between them.

### Why V2 Exists

V1 works but does not scale operationally. The single greatest problem is that **onboarding a new tenant frequently requires code changes**. Tenant-specific logic is hardcoded throughout the codebase in switch/case statements, if/else blocks, and per-tenant helper functions. V1 has:

- **40+ tenant-specific cases** in a single switch statement for display number parsing (MatterController, 450+ lines)
- **8+ tenant-specific if/else blocks** for client name transformation rules (UpdateMatter job)
- **7+ tenant-specific if/else blocks** for custom field mapping to iManage workspace custom fields (UpdateMatter job)
- **25+ named schemas** hardcoded in a switch/case for workspace naming (duplicated in both `post_workspace()` and `patch_workspace()` functions)
- **3 legacy JSON mapping files** for specific tenants (Mills Co, Siri Glimstad, Horn Williamson)
- **No tests**, **no webhook payload verification**, **unauthenticated debug routes**, and **plain-text credential storage**

V2 replaces all hardcoded tenant logic with a **database-driven, UI-configurable Tenant Configuration Engine**, bringing the onboarding process from "requires a developer" to "an admin fills out forms."

---

## 2. Goals & Principles

### Primary Goals

1. **Zero-code tenant onboarding.** Every aspect of a tenant's configuration -- display number parsing, name transformation, custom field mapping, workspace naming -- must be configurable via the admin UI without touching code.
2. **Production-grade reliability.** Idempotent webhook processing, retry with backoff, dead letter handling, correlated logging, and comprehensive audit trails.
3. **Security hardening.** Encrypted credentials, webhook signature verification, removal of debug routes, proper RBAC, and API authentication.
4. **Observability.** Real-time dashboard for webhook processing status, filterable/searchable request history, health checks, and alerting.
5. **Tenant self-service.** Law firm staff can view their own webhook activity, see processing status, and manage limited configuration aspects.
6. **Test coverage.** Unit tests for all business logic, integration tests for the webhook pipeline, feature tests for the admin panel.

### Architectural Principles

- **Service classes over helper files.** Replace all global helper functions with injectable service classes.
- **Jobs over controllers for business logic.** Controllers receive and validate; jobs process.
- **Database-driven configuration over code.** No tenant name should ever appear in a switch/case, if/else, or hardcoded constant.
- **Encrypt at rest.** All OAuth tokens, API keys, and secrets encrypted using Laravel's encryption.
- **Fail loudly, retry gracefully.** Every failure is logged with a correlation ID. Retries use exponential backoff with configurable max attempts.
- **Queue-first.** All external API calls happen in queued jobs, never in the HTTP request cycle.

---

## 3. User Roles & Access

### Role Hierarchy

| Role | Scope | Description |
|------|-------|-------------|
| **Super Admin** | Global | Full system access. Can manage all tenants, users, system settings, and infrastructure. |
| **Admin** | Global | Can manage tenants, configurations, subscriptions, and view all monitoring data. Cannot manage system settings or other admins. |
| **Support** | Global | Read-only access to all tenants. Can view webhook requests, reattempt failed jobs, but cannot modify configurations. |
| **Tenant Admin** | Single Tenant | Law firm's primary contact. Can view their own webhook activity, manage limited settings (e.g., toggle workspace link custom field), and view mapping configurations (read-only). |
| **Tenant Viewer** | Single Tenant | Law firm staff. Read-only access to their tenant's webhook activity and status dashboard. |

### Access Control Implementation

- Use **Spatie Laravel Permission** (already in V1 migration `2022_09_19_120051_create_permission_tables.php`).
- Permissions are granular and assigned to roles: `tenants.view`, `tenants.create`, `tenants.edit`, `tenants.delete`, `webhooks.reattempt`, `configs.edit`, `subscriptions.manage`, etc.
- Tenant-scoped users are automatically filtered to only see their own tenant's data via a global scope or middleware.

---

## 4. Module Breakdown

---

### Module 1: Authentication & User Management

**Purpose:** Secure user authentication with support for both back-office admins and tenant-scoped users.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `User` | id, name, email, password, tenant_id (nullable), email_verified_at, two_factor_* | tenant_id null = back-office user |
| `Team` | id, name, user_id | Retained from Jetstream if needed, otherwise remove |

**Features:**

- Email/password authentication via Laravel Fortify
- Two-factor authentication (TOTP) for all admin-level users
- Password complexity requirements (min 12 chars, mixed case, numbers, symbols)
- Session management with configurable timeout (default 30 minutes idle)
- "Remember me" functionality with secure token rotation
- Password reset via email
- User invitation flow (admin invites user, user sets password)
- Account lockout after 5 failed login attempts (15-minute lockout)
- Login audit log (IP, user agent, timestamp, success/failure)

**V1 Comparison:**

- V1 uses Jetstream with Blade. V2 replaces with Livewire components.
- V1 has tenant_id on users but no tenant-scoped access control. V2 enforces scoping.
- V1 has no 2FA enforcement or login auditing. V2 adds both.

**Acceptance Criteria:**

- [ ] Users can register only via admin invitation (no self-registration)
- [ ] 2FA can be enforced per role
- [ ] Tenant-scoped users cannot access any data outside their tenant
- [ ] Login attempts are logged with IP and user agent
- [ ] Account lockout triggers after 5 failed attempts
- [ ] Password reset emails are sent and processed correctly

---

### Module 2: Role & Permission Management

**Purpose:** Fine-grained access control using roles and permissions.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `Role` | id, name, guard_name | Spatie model |
| `Permission` | id, name, guard_name | Spatie model |
| `model_has_roles` | role_id, model_type, model_id | Pivot |
| `model_has_permissions` | permission_id, model_type, model_id | Pivot |
| `role_has_permissions` | permission_id, role_id | Pivot |

**Features:**

- Predefined roles: Super Admin, Admin, Support, Tenant Admin, Tenant Viewer
- Granular permissions covering every module action
- Admin UI to assign roles to users
- Admin UI to view permission matrix (role vs permission grid)
- Middleware enforcement on all routes
- Blade/Livewire directives for UI-level permission checks (`@can`, `@role`)

**Permission Matrix (Key Permissions):**

| Permission | Super Admin | Admin | Support | Tenant Admin | Tenant Viewer |
|------------|:-----------:|:-----:|:-------:|:------------:|:-------------:|
| tenants.create | Y | Y | - | - | - |
| tenants.edit | Y | Y | - | - | - |
| tenants.view | Y | Y | Y | Own | Own |
| configs.edit | Y | Y | - | Limited | - |
| webhooks.view | Y | Y | Y | Own | Own |
| webhooks.reattempt | Y | Y | Y | - | - |
| subscriptions.manage | Y | Y | - | - | - |
| users.manage | Y | Y | - | - | - |
| system.settings | Y | - | - | - | - |

**V1 Comparison:**

- V1 has Spatie tables migrated but roles/permissions are not enforced anywhere. Routes use only `auth` middleware with a commented-out `role:admin` check.
- V2 enforces RBAC on every route and UI element.

**Acceptance Criteria:**

- [ ] All five roles are seeded with correct permissions
- [ ] Every route is protected by appropriate permission middleware
- [ ] UI elements are conditionally rendered based on permissions
- [ ] Tenant-scoped users can only access their own tenant's data
- [ ] Role changes take effect immediately without requiring re-login

---

### Module 3: Tenant Management

**Purpose:** CRUD operations for tenants (law firms), including onboarding workflow, credential management, and lifecycle management.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `Tenant` | id, name, reference (UUID), clio_location_id, clio_app_id, clio_app_secret (encrypted), imanage_cloud_url, imanage_customer_id, imanage_app_id (encrypted), imanage_app_secret (encrypted), imanage_username (encrypted), imanage_password (encrypted), password_authentication (bool), has_group_security_mapping (bool), enable_workspace_link_custom_field (bool), owner_id, status (enum: pending, active, suspended, archived), onboarded_at | reference is the public-facing identifier used in webhook URLs |
| `TenantSetting` | id, tenant_id, library_id, imanage_template_id, default_hipaa, default_enabled, has_replica_workspaces, replica_template_id, has_workspace_link_custom_field, workspace_link_custom_field_name | One-to-one with Tenant |
| `ClioLocation` | id, name, api_url, app_url, region | EUR / USA / CAN / AUS API regions |

**Features:**

- Tenant CRUD with Livewire form components
- Guided onboarding wizard:
  1. Basic info (name, Clio region)
  2. Clio OAuth connection (redirect flow)
  3. iManage connection (OAuth or password auth)
  4. Library sync and default library selection
  5. Template sync and default template selection
  6. Display number parsing configuration
  7. Workspace naming configuration
  8. Practice area mapping
  9. Subscription creation
  10. Webhook registration
- Tenant status management (pending -> active -> suspended -> archived)
- Credential rotation (re-authorize Clio/iManage without losing data)
- Bulk data sync triggers (libraries, templates, practice areas, groups, users)
- Tenant dashboard showing key metrics (webhook count, success rate, last activity)
- Tenant soft-delete with data retention policy
- Tenant export (configuration snapshot as JSON for backup/migration)

**V1 Comparison:**

- V1 has basic tenant CRUD. Onboarding is manual and multi-step across different pages.
- V1 stores imanage_app_id and imanage_app_secret in plain text. V2 encrypts all credentials.
- V1 has no tenant status lifecycle. V2 adds pending/active/suspended/archived states.
- V1 has no onboarding wizard. V2 provides a guided multi-step flow.

**Acceptance Criteria:**

- [ ] Tenant can be created and fully configured via the onboarding wizard without leaving the admin panel
- [ ] All sensitive credentials are encrypted at rest
- [ ] Clio and iManage OAuth flows complete successfully and store tokens
- [ ] Tenant status transitions are enforced (e.g., cannot process webhooks for suspended tenants)
- [ ] Tenant soft-delete preserves all historical data
- [ ] Bulk sync operations work correctly for libraries, templates, practice areas, groups, and users

---

### Module 4: Tenant Configuration Engine

**Purpose:** This is the core architectural fix for V2. It replaces ALL hardcoded tenant-specific logic with a database-driven, UI-configurable system. After V2, onboarding a new tenant NEVER requires code changes.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `DisplayNumberParsingConfig` | id, tenant_id, strategy (enum), delimiter, client_position, matter_position, regex_pattern, client_capture_group, matter_capture_group, pre_processing_rules (JSON), post_processing_rules (JSON), validation_regex, matter_status_filter, custom_field_name (for Proclaim-style), fallback_strategy (enum), priority | The core replacement for the 40+ case switch statement |
| `ClientNameTransformationConfig` | id, tenant_id, strategy (enum: none, reverse_words, last_name_first, custom_template), template_pattern, enabled | Replaces per-tenant name transformation if/else blocks |
| `MatterDescriptionTransformationConfig` | id, tenant_id, strategy (enum: none, use_display_number, strip_prefix, custom_template), source_field, template_pattern, enabled | Replaces per-tenant matter description overrides |
| `WorkspaceNamingConfig` | id, tenant_id, template_pattern, description | Replaces the 25+ case switch in post_workspace/patch_workspace |
| `CustomFieldMappingRule` | id, tenant_id, source_type (enum: matter_status, responsible_attorney, originating_attorney, practice_area, template, clio_custom_field, open_date, custom), source_field_name, imanage_custom_field_config_id, value_mapping_type (enum: direct, lookup, static, date_format), static_value, date_format, enabled, priority | Replaces the per-tenant handleAdditionalCustomFields blocks |
| `LegacyAliasMapping` | id, tenant_id, entity_type (client/matter), clio_id, imanage_alias, imported_from, imported_at | Database replacement for JSON mapping files |
| `WebhookProcessingFilter` | id, tenant_id, field_path, operator (enum: equals, not_equals, matches_regex, contains, clio_picklist_equals), value, action (enum: skip, proceed), priority, enabled | Replaces hardcoded Griffitts-style "only process if picklist value is yes" logic |

#### 4.1 Display Number Parsing Engine

**Problem it solves:** In V1, MatterController.php has a 450+ line switch statement with 40+ cases that parse `display_number` strings into `client_id` and `matter_id` for iManage. Every new tenant requires adding a case to this switch statement.

**Strategy Enum Values:**

| Strategy | Description | Example Input | Config | Output |
|----------|-------------|---------------|--------|--------|
| `split_delimiter` | Split string by a delimiter, pick positions | `12345-001` | delimiter: `-`, client_pos: 0, matter_pos: 1 | client: `12345`, matter: `001` |
| `split_delimiter_nested` | Split by primary then secondary delimiter | `6381.00001-Name` | primary_delim: `-`, secondary_delim: `.`, client_pos: 0, matter_pos: 1 | client: `6381`, matter: `00001` |
| `regex` | Full regex with named capture groups | `(12345) text` | pattern: `/\((?P<client>\d+)\.(?P<matter>\d+)\)/` | client: `12345`, matter from group |
| `bracket_extraction` | Extract from last parenthesized group, then split | `Name-(4.00002)` | inner_delim: `.` | client: `4`, matter: `00002` |
| `clio_ids` | Use Clio's own client_id and matter id from payload | any | prefix: `CLIO-` (optional) | client: `CLIO-123`, matter: `CLIO-456` |
| `custom_field_extraction` | Extract from a Clio custom field value | any | custom_field_name: `Proclaim Matter Number`, inner_delim: `.` | From custom field value |
| `display_number_as_matter` | Entire display number becomes the matter_id | `MR-2024-001` | client_source: `payload_client_id` | client: from payload, matter: full display_number |
| `display_number_as_client` | Entire display number becomes the client_id | `12345` | - | client: `12345`, matter: null |
| `sequence_auto` | Use the Sequence Number Engine (Module 10) | any | - | Delegated to sequence config |
| `legacy_alias_lookup` | Check the legacy alias mapping table first, then fall back | any | fallback_strategy | From legacy mapping or fallback |

**Pre-Processing Rules (JSON array of operations applied before parsing):**

```json
[
  {"op": "ltrim", "chars": "-"},
  {"op": "split_take", "delimiter": " - ", "position": 0},
  {"op": "uppercase"},
  {"op": "strip_prefix", "prefix": "AWD-"},
  {"op": "regex_replace", "pattern": "/\\s+/", "replacement": ""}
]
```

**Post-Processing Rules (JSON array of operations applied to client_id or matter_id after parsing):**

```json
[
  {"target": "matter_id", "op": "pad_left", "length": 4, "char": "0"},
  {"target": "client_id", "op": "prefix", "value": "Clio-"},
  {"target": "matter_id", "op": "trim"},
  {"target": "matter_id", "op": "max_length", "length": 32}
]
```

**Validation Regex:** Optional regex that the display_number must match before processing. If it fails, the webhook is logged but not processed (replaces the Gionis Lilly pattern validation).

**Matter Status Filter:** Optional filter to only process matters with a specific status (e.g., "OPEN" only for Gionis Lilly).

**Fallback Strategy:** What to do if the primary strategy fails to extract IDs. Options: `skip`, `use_clio_ids`, `use_default_split`.

**UI Requirements:**

- Dropdown to select parsing strategy
- Dynamic form fields that change based on selected strategy
- "Test" button: admin pastes a sample display_number, clicks test, and sees the extracted client_id and matter_id in real time
- Regex helper with common patterns pre-populated
- Preview of existing webhook requests showing how they would parse under the current config

#### 4.2 Client Name Transformation Engine

**Problem it solves:** In V1, UpdateMatter.php has if/else blocks for specific tenants that modify client names before sending to iManage (e.g., "John Smith" -> "Smith, John" for Embry Law; reversed word order for McKinley Irvin).

**Strategy Enum Values:**

| Strategy | Description | Example Input | Output |
|----------|-------------|---------------|--------|
| `none` | Pass through unchanged | `John Smith` | `John Smith` |
| `last_name_first` | Move last word to front with comma | `John Michael Smith` | `Smith, John Michael` |
| `reverse_words` | Reverse word order (space-separated) | `John Michael Smith` | `Smith Michael John` |
| `custom_template` | Handlebars-style template | `John Smith` | Based on template pattern |

**Template Pattern Variables:** `{first_name}`, `{last_name}`, `{full_name}`, `{name_parts[0]}`, `{name_parts[1]}`, etc.

**UI Requirements:**

- Dropdown to select strategy
- Live preview showing transformation result for a sample name
- Option to apply transformation only to person contacts (not company contacts) based on Clio client type

#### 4.3 Matter Description Transformation Engine

**Problem it solves:** In V1, specific tenants override the matter description sent to iManage (e.g., Embry Law uses display_number as description; Gionis Lilly strips matter_id prefix from description; Acevedo Belt constructs a composite description).

**Strategy Enum Values:**

| Strategy | Description |
|----------|-------------|
| `none` | Use Clio matter description as-is |
| `use_display_number` | Use display_number instead of description |
| `use_client_description` | Use client name as matter description (Vladeck Raskin case) |
| `composite_template` | Build from template: `{display_number_part_0}-{display_number_part_1} - {description}` |
| `strip_prefix` | Remove the matter_id (extracted from display_number) from the start of description |

**UI Requirements:**

- Dropdown to select strategy
- Template builder for composite strategy
- Live preview

#### 4.4 Workspace Naming Configuration

**Problem it solves:** In V1, `post_workspace()` and `patch_workspace()` each contain a 25+ case switch statement that determines the workspace name format. Every new naming format requires code changes in TWO places.

**Solution:** A single configurable template pattern stored in the database, using placeholder tokens.

**Available Tokens:**

| Token | Resolves To |
|-------|-------------|
| `{client_id}` | Extracted client_id (custom1) |
| `{matter_id}` | Extracted matter_id (custom2) |
| `{client_name}` | Client description/name |
| `{matter_description}` | Matter description |
| `{display_number}` | Raw Clio display_number |
| `{practice_area}` | Mapped practice area key |
| `{display_number_part_N}` | Nth segment of display_number split by primary delimiter |

**Example Patterns (replacing V1 hardcoded schemas):**

| V1 Schema Name | V2 Template Pattern |
|----------------|-------------------|
| default | `{matter_description} ({client_id}.{matter_id})` |
| custom-krb | `{matter_id} : {matter_description}` |
| custom-mobility | `{client_id} - {matter_description}` |
| custom-mfb | `{client_id}.{matter_id} {matter_description}` |
| custom-dlaw | `{client_id}.{matter_id} - {matter_description}` |
| custom-cbms | `{client_id}-{matter_id} {client_name} - {matter_description}` |
| custom-mills-co | `{matter_description} ({matter_id})` |
| custom-mckinley | `{matter_description} - {display_number}` |
| custom-horn | `{client_id}.{matter_id} {client_name} - {matter_description}` |
| custom-adams | `{client_name}-{matter_description} ({client_id}.{matter_id})` |

**UI Requirements:**

- Text input for template pattern with token autocomplete
- Token reference panel showing all available tokens
- Live preview with sample data
- "Copy from existing tenant" dropdown to clone a naming pattern

#### 4.5 Custom Field Mapping Engine

**Problem it solves:** In V1, the `handleAdditionalCustomFields()` method in UpdateMatter.php has 200+ lines of tenant-specific if/else blocks that map Clio data to iManage custom fields (custom3 through custom28). Each tenant needs different Clio fields mapped to different iManage custom field slots.

**Solution:** A rules-based engine where each rule says: "For this tenant, take [source] data, look it up in [iManage custom field config], and populate [target iManage custom field slot]."

**Rule Configuration:**

Each `CustomFieldMappingRule` specifies:

1. **Source Type:** Where to get the value from the Clio webhook payload
   - `matter_status` - Maps Open/Closed to Active/Inactive (or custom labels)
   - `responsible_attorney` - Maps attorney name to iManage custom field value
   - `originating_attorney` - Maps originating attorney name
   - `practice_area` - Maps Clio practice area to an iManage custom field value
   - `template` - Maps the selected template to a custom field value
   - `clio_custom_field` - Reads a specific Clio custom field (by field_name) and resolves picklist values via API
   - `open_date` - Maps the matter open date with configurable date format
   - `static` - Always set a static value

2. **Target:** Which `ImanageCustomFieldConfig` group to search for the resolved value

3. **Value Mapping Type:**
   - `lookup` - Match the source value against `ImanageCustomField` descriptions within the config group
   - `direct` - Use the source value as the iManage field key directly
   - `static` - Always use a hardcoded value
   - `date_format` - Format a date value

**UI Requirements:**

- Table of rules per tenant with add/edit/delete
- Source type dropdown with dynamic fields
- Target custom field config dropdown (populated from `ImanageCustomFieldConfig` for the tenant)
- Test button that runs the rules against a sample webhook payload and shows which iManage custom fields would be populated
- Drag-and-drop reordering for priority

#### 4.6 Legacy Mapping File Import Tool

**Problem it solves:** V1 has JSON files in `storage/app/clio-alias-mappings/` for Mills Co, Siri Glimstad, and Horn Williamson that map Clio client/matter IDs to iManage aliases. These files are checked during display number parsing as a lookup table.

**Solution:** Import these JSON files into the `LegacyAliasMapping` database table. Provide a UI for managing legacy mappings. The display number parsing engine's `legacy_alias_lookup` strategy queries this table instead of reading files.

**Features:**

- JSON file import tool (upload or paste JSON, preview mappings, confirm import)
- CSV import/export for bulk management
- Manual add/edit/delete individual mappings
- Search and filter by Clio ID or iManage alias
- Migration command to auto-import existing JSON files during V1->V2 migration

#### 4.7 Webhook Processing Filters

**Problem it solves:** In V1, Griffitts LLP has a hardcoded check that only processes a matter if a specific Clio custom field ("iManage Creation") has a picklist value of "yes." Gionis Lilly only processes matters with status "OPEN" and a specific display_number format.

**Solution:** Configurable pre-processing filters that determine whether a webhook should be processed or skipped.

**Features:**

- Per-tenant filter rules with priority ordering
- Filter conditions: field path in payload, operator, expected value
- Special operator `clio_picklist_equals` that resolves Clio picklist option IDs to their labels via API before comparing
- Actions: `skip` (log and skip) or `proceed` (continue processing)
- UI to manage filter rules per tenant

**V1 Comparison Summary for Module 4:**

| Feature | V1 | V2 |
|---------|----|----|
| Display number parsing | 40+ hardcoded switch cases in MatterController | Database-driven strategy selection with test UI |
| Client name transformation | 3 hardcoded if/else blocks in UpdateMatter | Configurable strategy per tenant |
| Matter description override | 5 hardcoded if/else blocks in UpdateMatter | Configurable strategy per tenant |
| Workspace naming | 25+ hardcoded switch cases in post_workspace AND patch_workspace (duplicated) | Single template pattern per tenant |
| Custom field mapping | 200+ lines of tenant-specific if/else in handleAdditionalCustomFields | Rules-based engine with UI |
| Legacy JSON mappings | 3 JSON files in storage, read at runtime | Database table with import tool |
| Processing filters | 2 hardcoded tenant checks | Configurable filter rules |

**Acceptance Criteria:**

- [ ] A new tenant can be fully configured for display number parsing, name transformation, workspace naming, and custom field mapping entirely through the admin UI
- [ ] The "Test" button for display number parsing correctly parses sample inputs for all strategies
- [ ] All 40+ existing V1 tenant configurations can be reproduced using V2's configuration engine (verified by comparison testing)
- [ ] Legacy JSON mapping files are imported into the database
- [ ] Workspace naming template produces identical output to V1 for all existing schemas
- [ ] Custom field mapping rules produce identical output to V1 for all existing tenants
- [ ] Processing filters correctly skip/proceed for configured conditions
- [ ] No tenant name appears in any switch/case or if/else block in the codebase

---

### Module 5: Clio Integration Layer

**Purpose:** Encapsulate all Clio API interactions in a clean, testable service layer. Replace the global helper functions in `ClioApiHelper.php` with a proper service class.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `ClioOAuthAccessCode` | id, tenant_id, code, redirect_uri | Short-lived authorization code |
| `ClioOAuthAccessToken` | id, tenant_id, access_token (encrypted), refresh_token (encrypted), expires_at, revoked | Long-lived token pair |
| `ClioLocation` | id, name, api_url, app_url, region | API region configuration |
| `ClioClient` | id, tenant_id, clio_id, client_id, etag, name, initials, type, sequence_key, sequence_number | Local cache of Clio contacts |
| `ClioMatter` | id, tenant_id, clio_id, clio_client_id, clio_practice_area_id, matter_id, etag, display_number, custom_number, description, status, location, client_reference, open_date, close_date, pending_date, sequence_key, sequence_number, json_data | Local cache of Clio matters |
| `ClioPracticeArea` | id, tenant_id, clio_id, name, category | Synced practice areas |
| `ClioUser` | id, tenant_id, clio_id, name, email, enabled | Synced Clio users |
| `ClioGroup` | id, tenant_id, clio_id, name | Synced Clio groups |

**Service Class: `ClioApiService`**

```
ClioApiService
  - __construct(Tenant $tenant)
  - getAccessToken(): string
  - refreshToken(): ClioOAuthAccessToken
  - getWebhooks(): Collection
  - createWebhook(string $model, string $event, string $url, array $fields): array
  - deleteWebhook(int $webhookId): bool
  - extendWebhookExpiry(int $webhookId, string $etag): bool
  - getMatter(int $matterId): array
  - getClient(int $clientId): array
  - getUsers(): Collection
  - getGroups(): Collection
  - getGroup(int $groupId): array
  - getPracticeAreas(): Collection
  - getCustomField(int $fieldId): array
  - updateMatterCustomField(int $matterId, string $fieldName, $value): bool
  - updateContactCustomField(int $contactId, string $fieldName, $value): bool
```

**Features:**

- Full OAuth 2.0 flow (authorization code -> access token -> refresh token rotation)
- Automatic token refresh when expired (checked before every API call)
- Multi-region support (US, EU, CA, AU) via `ClioLocation`
- Rate limiting with backoff (respect Clio's rate limit headers)
- Request/response logging with correlation IDs
- Retry logic with exponential backoff for transient failures
- Circuit breaker pattern: after N consecutive failures, stop calling for M minutes

**V1 Comparison:**

- V1 uses raw curl calls in global helper functions (`ClioApiHelper.php`, ~500 lines). No error handling, no rate limiting, no retry logic.
- V1 tokens are stored in plain text. V2 encrypts all tokens.
- V1 refresh logic runs on a scheduler every minute for ALL tenants regardless of subscription status. V2 only refreshes for active tenants.

**Acceptance Criteria:**

- [ ] All Clio API calls go through `ClioApiService` -- no raw curl or Http calls outside the service
- [ ] OAuth flow works for all Clio regions (US, EU, CA, AU)
- [ ] Tokens are automatically refreshed when expired
- [ ] Rate limiting headers are respected
- [ ] Failed API calls are retried with exponential backoff
- [ ] All tokens and secrets are encrypted at rest

---

### Module 6: iManage Integration Layer

**Purpose:** Encapsulate all iManage API interactions in a clean, testable service layer. Replace the global helper functions in `iManageApiHelper.php` (~1200+ lines of global functions with raw curl).

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `ImanageOAuthAccessCode` | id, tenant_id, code | Short-lived |
| `ImanageOAuthAccessToken` | id, tenant_id, access_token (encrypted), refresh_token (encrypted), expires_at, revoked | Long-lived |
| `Library` | id, tenant_id, imanage_library_id, name, description | Synced libraries |
| `ImanagePracticeArea` | id, tenant_id, key, description, library_id | Synced practice areas |
| `ImanageSubPracticeArea` | id, tenant_id, key, description, imanage_practice_area_id | Sub-practice areas |
| `ImanageTemplate` | id, tenant_id, imanage_template_id, description, library_id | Workspace templates |
| `ImanageClient` | id, tenant_id, key, ssid, description, enabled, hipaa, wstype, library_id, webhook_request_id, clio_client_id, sequence_number, sequence_key | Created iManage clients |
| `ImanageMatter` | id, tenant_id, key, ssid, description, enabled, hipaa, wstype, imanage_client_id, library_id, webhook_request_id, clio_client_id, clio_matter_id, iman_practice_area_id, iman_sub_practice_area_id, clio_practice_area_id, closed, parent_id, parent_ssid, key_numeric, sequence_number, sequence_key | Created iManage matters |
| `ImanageWorkspace` | id, tenant_id, imanage_workspace_id, library_id, imanage_template_id, imanage_matter_id, imanage_client_id, imanage_practice_area_id, imanage_sub_practice_area_id, webhook_request_id, name, description, database, default_security, has_subfolders, owner, custom1-custom30, document_number, is_declared, is_hipaa, iwl, replica | Created workspaces |
| `ImanageGroup` | id, tenant_id, imanage_group_id, name, library_id | Synced groups |
| `ImanageUser` | id, tenant_id, imanage_user_id, full_name, email, library_id | Synced users |
| `ImanageCustomFieldConfig` | id, tenant_id, custom_field_identifier, description | Custom field config groups |
| `ImanageCustomField` | id, imanage_custom_field_config_id, tenant_id, key, description, wstype | Individual custom field values within a config group |

**Service Class: `ImanageApiService`**

```
ImanageApiService
  - __construct(Tenant $tenant)
  - getAccessToken(): string
  - refreshToken(): ImanageOAuthAccessToken
  - getPasswordToken(): string  // for password-auth tenants
  - getLibraries(): Collection
  - getPracticeAreas(string $libraryId): Collection
  - getSubPracticeAreas(string $libraryId, string $practiceAreaKey): Collection
  - getTemplates(string $libraryId): Collection
  - getGroups(string $libraryId): Collection
  - getUsers(string $libraryId): Collection
  - getClient(string $libraryId, string $clientKey): ?array
  - createClient(string $libraryId, string $key, bool $enabled, string $description, bool $hipaa): array
  - updateClient(string $libraryId, string $key, bool $enabled, string $description, bool $hipaa): array
  - getMatter(string $libraryId, string $matterKey, string $clientKey): ?array
  - createMatter(string $libraryId, string $key, string $clientKey, bool $enabled, string $description, bool $hipaa): array
  - updateMatter(string $libraryId, string $key, string $clientKey, bool $enabled, string $description, bool $hipaa): array
  - getWorkspace(string $libraryId, string $workspaceId): array
  - createWorkspace(string $libraryId, array $params): array
  - updateWorkspace(string $libraryId, string $workspaceId, array $params): array
  - getWorkspaceFolders(string $libraryId, string $workspaceId): Collection
  - copyTemplateFolders(string $libraryId, string $templateId, string $workspaceId): bool
  - getWorkspaceSecurity(string $libraryId, string $workspaceId): array
  - setWorkspaceSecurity(string $libraryId, string $workspaceId, array $include, array $remove, string $defaultSecurity): array
  - getCustomFieldDefinitions(string $libraryId): Collection
```

**Features:**

- Dual authentication support: OAuth 2.0 and password-based (per tenant configuration)
- Automatic token refresh for OAuth tenants
- Rate limiting with backoff
- Request/response logging with correlation IDs
- Retry logic with exponential backoff
- Idempotency: check-before-create for clients, matters, and workspaces to prevent duplicates
- Workspace parameter builder: constructs the params array based on tenant configuration (replaces the conditional if/elseif chain in V1)

**V1 Comparison:**

- V1 uses ~1200 lines of global functions with raw curl. No classes, no dependency injection, no testability.
- V1 has duplicated workspace naming logic in both post_workspace and patch_workspace. V2 has a single `WorkspaceNameResolver` that both create and update use.
- V1 has no idempotency -- retrying a failed job can create duplicate workspaces. V2 checks existence before creating.
- V1 has a hardcoded iManage OAuth URL in ImanageAuthorizationController line 25. V2 uses tenant's `imanage_cloud_url`.

**Acceptance Criteria:**

- [ ] All iManage API calls go through `ImanageApiService` -- no raw curl or Http calls outside the service
- [ ] Both OAuth and password authentication modes work correctly
- [ ] Client/matter/workspace creation is idempotent (no duplicates on retry)
- [ ] Workspace naming uses the template pattern from `WorkspaceNamingConfig` -- no switch/case
- [ ] All tokens and credentials are encrypted at rest
- [ ] Rate limiting is respected

---

### Module 7: Webhook Processing Pipeline

**Purpose:** Receive, validate, queue, and process Clio webhooks through a reliable, observable pipeline.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `Webhook` | id, tenant_id, clio_id, type, model, events, url, shared_secret, status, expires_at, etag | Webhook registrations |
| `WebhookRequest` | id, tenant_id, webhook_id, url, headers, body, retrieved_client_id, retrieved_matter_id, client_activity_complete, matter_activity_complete, workspace_activity_complete, folder_activity_complete, security_activity_complete, workspace_link_custom_field_populated, correlation_id (UUID), processing_stage (enum), error_message, error_count, started_at, completed_at, reattempted, reattempted_by, reattempted_at | Individual webhook events |
| `WebhookType` | id, name, model, event | Reference data |
| `TenantJobLock` | id, tenant_id, locked_at | Per-tenant processing lock |

**Pipeline Stages:**

```
1. RECEIVE      -> Controller receives POST, verifies X-Hook-Secret handshake
2. VALIDATE     -> Verify webhook signature (new in V2), find tenant, check subscription
3. PARSE        -> Apply DisplayNumberParsingConfig to extract client_id/matter_id
4. FILTER       -> Apply WebhookProcessingFilters (skip/proceed)
5. TRANSFORM    -> Apply ClientNameTransformationConfig, MatterDescriptionTransformationConfig
6. ENQUEUE      -> Create WebhookRequest record, dispatch UpdateMatter job
7. PROCESS      -> Job acquires tenant lock, calls iManage APIs
8. POST-PROCESS -> Create folders, apply security, write workspace link back to Clio
9. AUDIT        -> Run security audit, record results
10. COMPLETE    -> Mark WebhookRequest as complete, release tenant lock
```

**Features:**

- **Webhook signature verification** (new): Verify the `X-Hook-Signature` header using the webhook's shared_secret. V1 only handles the X-Hook-Secret handshake but never verifies actual payload signatures.
- **Correlation IDs**: Every webhook request gets a UUID that is passed through all jobs and logged. Enables tracing a single webhook through the entire pipeline.
- **Processing stages**: The `processing_stage` field tracks exactly where in the pipeline a request is. Replaces the 6 boolean flags with a single status tracking field (booleans retained for backward compatibility but stage enum is the source of truth).
- **Per-tenant locking**: Only one webhook is processed per tenant at a time (carried over from V1's `tenant_job_locks` table, but improved with proper distributed locking via Redis).
- **Retry with backoff**: Failed jobs are retried with exponential backoff (15s, 30s, 60s). Max 3 attempts, then moved to dead letter.
- **Dead letter queue**: Webhook requests that exhaust retries are marked as `failed` with the error message preserved. Admin can view and manually retry.
- **Idempotency**: If the same display_number + tenant combination arrives while a previous request is still processing (unstarted), deduplicate.
- **Client webhook processing** (new): V1 stubs for client created/updated/deleted webhooks that only log. V2 implements actual processing where applicable.
- **Matter opened/closed processing** (new): V1 stubs. V2 can update iManage workspace status or custom fields when a matter is opened/closed.

**Job Chain:**

```
UpdateMatter (queue: mid_ii)
  |-> Creates/updates iManage client
  |-> Creates/updates iManage matter
  |-> Creates/updates iManage workspace
  |-> If new workspace:
  |     |-> CreateWorkspaceFolders (queue: long_term)
  |     |-> If replica workspace enabled:
  |           |-> Create replica workspace
  |           |-> CreateWorkspaceFolders for replica (queue: long_term)
  |-> PostWorkspaceSecurity (queue: long_term)
  |     OR
  |-> HandleGroupSecurityMapping (inline, then dispatches AuditWorkspaceSecurity)
  |-> If workspace link custom field enabled:
        |-> PopulateClioMatterWorkspaceLinkCustomField
```

**V1 Comparison:**

- V1 has no webhook payload signature verification. V2 adds it.
- V1 has no correlation IDs. Debugging requires manually correlating log entries by timestamp. V2 adds UUIDs.
- V1 uses 6 boolean flags to track progress. V2 adds a stage enum for clearer status tracking.
- V1 uses database queue driver. V2 uses Redis for better performance and reliability.
- V1 has client/matter opened/closed webhook handlers that are empty stubs. V2 implements them.
- V1 has no dead letter handling. Failed jobs disappear into the failed_jobs table. V2 surfaces them in the UI.

**Acceptance Criteria:**

- [ ] Webhook signature is verified for all incoming payloads
- [ ] Correlation ID is generated and logged through the entire pipeline
- [ ] Processing stage is updated at each step
- [ ] Per-tenant locking prevents concurrent processing
- [ ] Failed jobs are retried with exponential backoff
- [ ] Dead letter requests are visible in the admin panel and can be manually retried
- [ ] Duplicate webhook requests for the same matter are deduplicated
- [ ] Client webhooks are processed (create/update client in iManage)
- [ ] Matter opened/closed webhooks update iManage workspace status

---

### Module 8: Mapping Engine

**Purpose:** Manage the various cross-system mappings between Clio and iManage entities.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `PracticeAreaMapping` | id, tenant_id, clio_practice_area_id, imanage_practice_area_id, imanage_sub_practice_area_id, imanage_custom_field_id | Clio PA -> iManage PA + sub-PA |
| `TemplateMapping` | id, tenant_id, clio_practice_area_id, imanage_template_id | Clio PA -> iManage template |
| `GroupMapping` | id, tenant_id, clio_group_id, imanage_group_id | Clio group -> iManage group |
| `UserMapping` | id, tenant_id, clio_user_id, imanage_user_id | Clio user -> iManage user |

**Features:**

- Practice area mapping CRUD with Livewire components
- Template mapping CRUD with Livewire components
- Group mapping CRUD with search/filter
- User mapping CRUD with search/filter
- Auto-suggest mappings based on name similarity (new): When syncing, suggest matches where Clio and iManage names are similar
- Bulk mapping import/export (CSV)
- Mapping validation: warn if a Clio entity has no mapping
- Mapping audit: log when mappings are created, modified, or deleted

**V1 Comparison:**

- V1 has all four mapping types with basic CRUD via Blade forms. V2 upgrades to Livewire with better UX.
- V1 has no auto-suggest. V2 suggests matches based on name similarity.
- V1 has no bulk import/export. V2 adds CSV support.

**Acceptance Criteria:**

- [ ] All four mapping types (practice area, template, group, user) can be managed via the UI
- [ ] Auto-suggest provides reasonable matches based on name similarity
- [ ] Bulk CSV import works for all mapping types
- [ ] Mapping changes are audit-logged
- [ ] Unmapped entities are flagged with warnings

---

### Module 9: Subscription & Billing Management

**Purpose:** Track tenant subscription status and enforce processing restrictions based on subscription state.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `TenantSubscription` | id, tenant_id, reference, start_date, end_date, status (active/void/expired), clio_users_at_start, plan_type, notes | Subscription periods |
| `TenantSubscriptionReminder` | id, tenant_subscription_id, reminder_type (30_day, 14_day, 7_day, 1_day, expired), sent_at | Reminder tracking |

**Features:**

- Subscription CRUD per tenant
- Status lifecycle: active -> expired (automatic at end_date) or active -> void (manual)
- Automatic subscription expiry: Scheduled command checks daily and transitions expired subscriptions
- Processing gate: Webhook processing only proceeds for tenants with active subscriptions
- Reminder system: Email notifications at 30, 14, 7, and 1 day(s) before expiry
- Subscription history view per tenant
- Clio user count snapshot at subscription start (for billing reference)
- Dashboard widget showing expiring subscriptions

**V1 Comparison:**

- V1 has subscription management but no automatic expiry. Scheduled commands process ALL tenants regardless of subscription status.
- V1 has reminder notifications but they may not prevent processing of expired subscriptions.
- V2 enforces the subscription gate consistently and adds automatic expiry.

**Acceptance Criteria:**

- [ ] Webhook processing is blocked for tenants without active subscriptions
- [ ] Subscriptions automatically expire at end_date
- [ ] Reminder emails are sent at configured intervals
- [ ] Subscription history is viewable per tenant
- [ ] Voiding a subscription takes effect immediately

---

### Module 10: Sequence Number Engine

**Purpose:** Auto-generate sequential client and matter IDs for tenants that don't have their own numbering system in Clio.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `TenantSequenceConfig` | id, tenant_id, client_prefix, client_start_number, client_digits, client_custom_field_name, matter_prefix, matter_start_number, matter_digits, matter_custom_field_name | Sequence configuration |

**Features:**

- Per-tenant sequence configuration (prefix, start number, zero-pad digits)
- Separate sequences for client IDs and matter IDs
- Atomic sequence number generation (database-level locking to prevent gaps or duplicates)
- Write-back to Clio: After generating a sequence number, write it to a specified Clio custom field
- Sequence preview: Show the next number that would be generated
- Manual sequence reset (admin only, with confirmation)
- Sequence audit log

**V1 Comparison:**

- V1 has this feature implemented. The core logic in MatterController is functional but tightly coupled.
- V1 has a race condition: sequence number generation uses `max() + 1` which can produce duplicates under concurrent load. V2 uses atomic database operations or Redis locking.
- V2 decouples the sequence engine from the controller into a dedicated service.

**Acceptance Criteria:**

- [ ] Sequence numbers are generated atomically without gaps or duplicates under concurrent load
- [ ] Generated numbers are written back to Clio custom fields
- [ ] Sequence configuration is manageable via the admin UI
- [ ] Manual reset requires confirmation and is audit-logged

---

### Module 11: Monitoring & Observability

**Purpose:** Real-time dashboard and tools for monitoring webhook processing, identifying failures, and troubleshooting issues.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `WebhookRequest` | (see Module 7) | Primary data source |
| `WorkspaceSecurityAudit` | (see Module 6) | Security audit data |
| `ActivityLog` | id, tenant_id, user_id, action, model_type, model_id, properties (JSON), ip_address, created_at | Admin action audit trail (new) |

**Features:**

- **Global Dashboard:**
  - Total webhook requests (today, this week, this month)
  - Success/failure rates by tenant
  - Processing time percentiles (p50, p95, p99)
  - Queue depth and worker status
  - Expiring subscriptions widget
  - Recent failures widget
  - Active tenant count

- **Webhook Request List (enhanced):**
  - Filterable by: tenant, date range, status (pending, processing, completed, failed), stage, has_errors
  - Searchable by: client_id, matter_id, correlation_id
  - Sortable by all columns
  - Bulk operations: reattempt selected, export selected
  - Real-time updates via Livewire polling

- **Webhook Request Detail:**
  - Full request/response data (headers, body, formatted JSON)
  - Processing timeline showing each stage with timestamps
  - Related iManage entities created/updated (client, matter, workspace)
  - Security audit results
  - Error details with stack traces (for failed requests)
  - Manual reattempt button with confirmation

- **Tenant Health Dashboard:**
  - Per-tenant webhook processing stats
  - Last successful processing timestamp
  - Token expiry status (Clio + iManage)
  - Subscription status
  - Configuration completeness indicator

- **Health Check Endpoint (new):**
  - `GET /health` returns system status (database, Redis, queue workers, external API connectivity)
  - Used by load balancers and monitoring systems

- **Webhook Summary Email:**
  - Configurable schedule (currently every 3 hours in V1)
  - Summary of processed, failed, and pending webhooks
  - Highlight any tenants with issues

**V1 Comparison:**

- V1 has a basic webhook request list with DataTables but no filtering, searching, or bulk operations.
- V1 has no global dashboard or tenant health view.
- V1 has no health check endpoint.
- V1 has no processing timeline or stage tracking.
- V1 has no admin action audit trail.

**Acceptance Criteria:**

- [ ] Dashboard loads in under 2 seconds with 100K+ webhook requests
- [ ] Webhook request list supports filtering by tenant, date range, and status
- [ ] Search by client_id, matter_id, and correlation_id works correctly
- [ ] Manual reattempt from the detail page works correctly
- [ ] Health check endpoint returns accurate system status
- [ ] Webhook summary email sends on schedule

---

### Module 12: Notification System

**Purpose:** Configurable notifications for system events, errors, and reminders.

**Key Entities/Models:**

| Model | Key Fields | Notes |
|-------|-----------|-------|
| `NotificationPreference` | id, user_id, channel (email, slack, database), event_type, enabled | Per-user notification preferences |
| `Notification` | id, type, notifiable_type, notifiable_id, data, read_at | Laravel notification table |

**Notification Events:**

| Event | Default Recipients | Channels |
|-------|-------------------|----------|
| Webhook processing failed (after all retries) | Admins | Email, Slack |
| Subscription expiring (30/14/7/1 day) | Admins, Tenant Admin | Email |
| Subscription expired | Admins, Tenant Admin | Email |
| Clio token refresh failed | Admins | Email, Slack |
| iManage token refresh failed | Admins | Email, Slack |
| Webhook expiry extension failed | Admins | Email |
| New tenant registered | Admins | Email |
| Security audit gap detected | Admins | Email, Database |
| Webhook summary (periodic) | Admins | Email |
| Queue worker down | Super Admin | Email, Slack |

**Features:**

- Laravel Notifications with mail and database channels
- Optional Slack channel integration
- Per-user notification preferences
- In-app notification bell with unread count
- Notification history

**V1 Comparison:**

- V1 has a few Mailable classes (WebhookExtensionFailed, ClioAccessTokenRefreshFailed, ImanageAccessTokenRefreshFailed, NewRegistration, NewWebhookRequest) but most are disabled.
- V2 uses Laravel Notifications for all channels with configurable preferences.

**Acceptance Criteria:**

- [ ] All notification events trigger correctly
- [ ] Users can configure their notification preferences
- [ ] In-app notifications display with unread count
- [ ] Slack integration sends to configured channel
- [ ] Email notifications are properly formatted

---

### Module 13: Tenant Portal (New in V2)

**Purpose:** Self-service portal for law firm staff to view their webhook activity, processing status, and limited configuration.

**Features:**

- **Dashboard:**
  - Today's webhook activity (received, processing, completed, failed)
  - Recent webhook requests with status
  - Subscription status and expiry date
  - System status indicator (healthy/degraded)

- **Webhook Activity Log:**
  - List of all webhook requests for their tenant
  - Filter by date range and status
  - Detail view showing processing stages and outcomes
  - No access to raw headers/body (security)

- **Configuration View (Read-Only):**
  - Display number parsing configuration (view only)
  - Workspace naming template (view only)
  - Practice area mappings (view only)
  - Group and user mappings (view only)

- **Limited Self-Service Configuration:**
  - Toggle workspace link custom field (on/off)
  - Update workspace link custom field name
  - Request support (form that creates a notification for admins)

**V1 Comparison:**

- V1 has no tenant-facing portal. All management is done by admins.
- V2 introduces a self-service portal that reduces support burden.

**Acceptance Criteria:**

- [ ] Tenant users can only see their own tenant's data
- [ ] Dashboard shows accurate real-time statistics
- [ ] Webhook activity log is filterable and paginated
- [ ] Configuration pages are read-only for tenant users
- [ ] Self-service toggles work correctly and are audit-logged

---

### Module 14: Admin Panel & Back Office Tools

**Purpose:** Comprehensive admin interface for managing all aspects of the system.

**Features:**

- **Tenant Management:** Full CRUD, onboarding wizard, credential management
- **Configuration Management:** All Module 4 configuration UIs
- **Mapping Management:** All Module 8 mapping UIs
- **Subscription Management:** All Module 9 subscription UIs
- **Sequence Config Management:** Module 10 configuration UI
- **Monitoring:** Module 11 dashboards and tools
- **User Management:** CRUD for admin and tenant users
- **Sync Operations:**
  - Sync libraries for a tenant
  - Sync templates for a tenant
  - Sync practice areas for a tenant
  - Sync Clio users for a tenant
  - Sync Clio groups for a tenant
  - Sync iManage groups for a tenant
  - Sync iManage users for a tenant
  - Sync iManage custom field definitions for a tenant
- **Bulk Operations (new):**
  - Bulk reattempt failed webhook requests (by tenant, by date range)
  - Bulk sync data across multiple tenants
  - Bulk extend webhook expiries
- **System Settings (new):**
  - Default queue configuration
  - Notification channel settings
  - Health check thresholds
  - Log retention policy
- **Audit Log Viewer (new):**
  - All admin actions logged and searchable
  - Filter by user, action type, tenant, date range

**V1 Comparison:**

- V1 has a basic admin panel with Blade views and DataTables. Limited filtering, no bulk operations, no audit trail.
- V2 upgrades to Livewire components with real-time updates, comprehensive filtering, bulk operations, and full audit trail.

**Acceptance Criteria:**

- [ ] All admin functions are accessible through the Livewire-based panel
- [ ] Bulk operations work correctly with progress indicators
- [ ] Sync operations trigger correctly and report results
- [ ] Audit log captures all admin actions
- [ ] UI is responsive and works on tablet-sized screens

---

### Module 15: API Layer (New in V2)

**Purpose:** RESTful API for external integrations, automation, and future mobile app support.

**Features:**

- **Authentication:** Laravel Sanctum token-based authentication
- **Endpoints:**

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/tenants` | List tenants | Admin |
| GET | `/api/v1/tenants/{id}` | Get tenant details | Admin, Tenant Admin (own) |
| GET | `/api/v1/tenants/{id}/webhook-requests` | List webhook requests | Admin, Tenant (own) |
| GET | `/api/v1/tenants/{id}/webhook-requests/{id}` | Get webhook request detail | Admin, Tenant (own) |
| POST | `/api/v1/tenants/{id}/webhook-requests/{id}/reattempt` | Reattempt a webhook request | Admin |
| GET | `/api/v1/tenants/{id}/subscriptions` | List subscriptions | Admin |
| GET | `/api/v1/tenants/{id}/mappings/practice-areas` | List practice area mappings | Admin |
| GET | `/api/v1/health` | System health check | Public or API key |
| GET | `/api/v1/stats` | Global statistics | Admin |

- **Features:**
  - Rate limiting (60 requests/minute default, configurable per token)
  - JSON:API response format with pagination
  - Comprehensive error responses with error codes
  - API versioning via URL prefix
  - OpenAPI/Swagger documentation auto-generated

**V1 Comparison:**

- V1 has no API. V2 introduces a full REST API.

**Acceptance Criteria:**

- [ ] All endpoints return correct data with proper authorization
- [ ] Rate limiting is enforced
- [ ] API documentation is auto-generated and accessible
- [ ] Unauthorized requests return 401/403 with proper error format
- [ ] Pagination works correctly

---

### Module 16: Security & Compliance

**Purpose:** Address all V1 security gaps and establish security best practices.

**Features:**

- **Credential Encryption:**
  - All OAuth tokens encrypted at rest using Laravel's `encrypt()`/`decrypt()`
  - All API keys, app secrets, passwords encrypted at rest
  - Encryption key rotation support
  - Migration command to encrypt existing V1 plain-text credentials

- **Webhook Security:**
  - X-Hook-Secret handshake for webhook registration (carried over from V1)
  - X-Hook-Signature verification for all incoming webhook payloads (new)
  - Webhook URL uses tenant reference UUID, not sequential ID

- **Route Security:**
  - Remove all debug routes (`/session_data`, `/test_lci_ids`, `/create_client_create_webhook/{tenant_id}`)
  - All admin routes behind authentication middleware
  - CSRF protection on all POST/PUT/DELETE routes
  - Rate limiting on authentication endpoints (5 attempts/minute)

- **Data Security:**
  - Webhook request bodies containing client data are treated as sensitive
  - Log sanitization: no PII in application logs (mask client names, email addresses)
  - Database connection encryption (SSL)

- **Infrastructure Security:**
  - Environment variable validation on boot (fail fast if required vars are missing)
  - `.env.example` complete with all required variables documented
  - No credentials in version control
  - Content Security Policy headers
  - HSTS enforcement

**V1 Gaps Being Fixed:**

| Gap | V1 Status | V2 Fix |
|-----|-----------|--------|
| Debug routes accessible without auth | `/session_data`, `/test_lci_ids`, `/create_client_create_webhook` are public | Removed entirely |
| No webhook signature verification | Only handshake, no payload verification | Full signature verification |
| Plain-text credentials | imanage_app_id, imanage_app_secret in DB as plain text | Encrypted at rest |
| Plain-text OAuth tokens | access_token, refresh_token stored plain | Encrypted at rest |
| Hardcoded iManage OAuth URL | ImanageAuthorizationController line 25 | Uses tenant's imanage_cloud_url |
| No CSRF on webhook routes | Webhook routes are POST but excluded from CSRF | Expected behavior for webhooks; add signature verification instead |

**Acceptance Criteria:**

- [ ] No credentials stored in plain text in the database
- [ ] All debug routes are removed
- [ ] Webhook payload signatures are verified
- [ ] Authentication endpoints are rate-limited
- [ ] Environment variable validation fails fast on missing required variables
- [ ] `.env.example` documents all required variables

---

### Module 17: Infrastructure & DevOps

**Purpose:** Production-ready infrastructure configuration, deployment, and operational tooling.

**Features:**

- **Queue System:**
  - Redis-backed queues (replacing V1's database queue driver)
  - Named queues: `default`, `webhooks`, `sync`, `notifications`, `long_running`
  - Queue worker configuration with appropriate timeouts per queue
  - Horizon dashboard for queue monitoring (optional, recommended)

- **Scheduled Commands:**

| Command | Schedule | Description |
|---------|----------|-------------|
| `RefreshExpiredClioAccessTokens` | Every 5 minutes | Refresh tokens for active tenants only |
| `RefreshExpiredImanageAccessTokens` | Every 5 minutes | Refresh tokens for active tenants only |
| `ExtendWebhookExpiry` | Daily at 06:00 | Extend webhook registrations before they expire |
| `ExpireSubscriptions` | Daily at 00:00 | Transition expired subscriptions |
| `SendSubscriptionReminders` | Daily at 07:00 | Send expiry reminders |
| `SendWebhookSummary` | Every 3 hours | Periodic status summary email |
| `FetchClioUsers` | Weekly | Refresh Clio user data |
| `PruneWebhookRequestBodies` | Monthly | Remove raw body data from completed requests older than 90 days |
| `HealthCheckPing` | Every minute | Self-health check and alert if degraded |

- **Logging:**
  - Structured JSON logging
  - Correlation ID in all log entries (via middleware that sets a request-scoped UUID)
  - Log channels: daily file rotation + external service (configurable)
  - Log levels: ERROR for failures, WARNING for degraded states, INFO for successful processing, DEBUG for development

- **Caching:**
  - Redis-backed cache
  - Cache tenant configurations (invalidate on change)
  - Cache iManage tokens (TTL based on expiry)

- **Testing:**
  - PHPUnit with feature tests and unit tests
  - Test database with migrations
  - Factory classes for all models
  - Test coverage target: 80%+
  - CI pipeline: lint, test, build

- **Deployment:**
  - Docker-compose for local development
  - Environment-based configuration
  - Zero-downtime deployment strategy
  - Database migration safety (no breaking changes without a migration plan)

**V1 Comparison:**

- V1 uses database queue driver. V2 uses Redis.
- V1 has no tests. V2 targets 80% coverage.
- V1 has no structured logging or correlation IDs. V2 adds both.
- V1 refreshes tokens every minute for ALL tenants. V2 only refreshes for active tenants every 5 minutes.
- V1 has no data pruning. V2 prunes old webhook body data.

**Acceptance Criteria:**

- [ ] Redis queue is operational with named queues
- [ ] All scheduled commands run on their defined schedule
- [ ] Correlation IDs appear in all log entries for a given request
- [ ] Test suite runs green with 80%+ code coverage
- [ ] Docker-compose setup works for local development
- [ ] Health check endpoint responds correctly

---

## 5. Data Model Overview

### Entity Relationship Summary

```
Tenant (central entity)
  |-- has one TenantSetting
  |-- has one TenantSequenceConfig
  |-- has one DisplayNumberParsingConfig
  |-- has one ClientNameTransformationConfig
  |-- has one MatterDescriptionTransformationConfig
  |-- has one WorkspaceNamingConfig
  |-- has many CustomFieldMappingRules
  |-- has many WebhookProcessingFilters
  |-- has many LegacyAliasMappings
  |-- has many Users
  |-- has many Webhooks
  |     |-- has many WebhookRequests
  |           |-- has one WorkspaceSecurityAudit
  |-- has many TenantSubscriptions
  |     |-- has many TenantSubscriptionReminders
  |-- has many ClioOAuthAccessTokens
  |-- has many ImanageOAuthAccessTokens
  |-- has many Libraries
  |-- has many ImanagePracticeAreas
  |     |-- has many ImanageSubPracticeAreas
  |-- has many ImanageTemplates
  |-- has many ImanageCustomFieldConfigs
  |     |-- has many ImanageCustomFields
  |-- has many ClioPracticeAreas
  |     |-- has one PracticeAreaMapping
  |     |-- has one TemplateMapping
  |-- has many ClioUsers
  |     |-- has one UserMapping -> ImanageUser
  |-- has many ClioGroups
  |     |-- has one GroupMapping -> ImanageGroup
  |-- has many ClioClients
  |-- has many ClioMatters
  |-- has many ImanageClients
  |-- has many ImanageMatters
  |-- has many ImanageWorkspaces
  |-- has many ImanageGroups
  |-- has many ImanageUsers
  |-- belongs to ClioLocation
```

### Migration Strategy from V1

1. Create all new tables (configuration engine tables) via migrations
2. Run a seeder/command that reads V1 hardcoded logic and populates the new configuration tables for each existing tenant
3. Encrypt all existing plain-text credentials via a migration command
4. Import legacy JSON mapping files into `LegacyAliasMappings` table
5. Verify by running comparison tests: process the same webhook payload through V1 and V2 logic and confirm identical output

---

## 6. Integration Architecture

### System Context

```
                     +-------------------+
                     |   Clio (SaaS)     |
                     |  Legal Practice   |
                     |  Management       |
                     +--------+----------+
                              |
                   Webhooks   |  REST API
                   (POST)     |  (GET/POST/PATCH)
                              |
                     +--------v----------+
                     |                   |
                     |  iman-clio V2     |
                     |  (This App)       |
                     |                   |
                     |  Laravel + Redis  |
                     |  + Queue Workers  |
                     |                   |
                     +--------+----------+
                              |
                   REST API   |
                   (GET/POST/ |
                    PATCH)    |
                              |
                     +--------v----------+
                     | iManage (SaaS)    |
                     | Document          |
                     | Management        |
                     +-------------------+
```

### Data Flow (Matter Created/Updated Webhook)

```
Clio                    iman-clio V2                                    iManage
 |                         |                                              |
 |--- POST webhook ------->|                                              |
 |                         |-- Verify signature                           |
 |                         |-- Find tenant                                |
 |                         |-- Check subscription                         |
 |                         |-- Apply parsing config                       |
 |                         |-- Apply processing filters                   |
 |                         |-- Apply name transformation                  |
 |                         |-- Create WebhookRequest                      |
 |                         |-- Dispatch job to queue                      |
 |                         |                                              |
 |                         |   [Queue Worker picks up job]                |
 |                         |                                              |
 |                         |-- GET client -------------------------------->|
 |                         |<-------------------------------- 200/404 -----|
 |                         |-- POST/PATCH client ------------------------->|
 |                         |<------------------------------------- 200 ---|
 |                         |-- GET matter -------------------------------->|
 |                         |<-------------------------------- 200/404 -----|
 |                         |-- POST/PATCH matter ------------------------->|
 |                         |<------------------------------------- 200 ---|
 |                         |-- Apply workspace naming config              |
 |                         |-- Apply custom field mapping rules           |
 |                         |-- POST/PATCH workspace ---------------------->|
 |                         |<------------------------------------- 200 ---|
 |                         |-- POST folder copy (from template) ---------->|
 |                         |<------------------------------------- 200 ---|
 |                         |-- POST security ----------------------------->|
 |                         |<------------------------------------- 200 ---|
 |<-- PATCH matter --------|   (write workspace URL back to Clio)         |
 |                         |-- Audit security ---------------------------->|
 |                         |<------------------------------------- 200 ---|
 |                         |-- Mark complete                              |
```

### Authentication Flows

**Clio OAuth 2.0:**
1. Admin clicks "Connect Clio" for a tenant
2. Redirect to `{clio_region_url}/oauth/authorize?client_id=...&redirect_uri=...`
3. User authorizes
4. Clio redirects back with authorization code
5. App exchanges code for access_token + refresh_token
6. Tokens stored encrypted in `clio_o_auth_access_tokens`
7. Tokens auto-refreshed before expiry by scheduled command

**iManage OAuth 2.0 (for OAuth tenants):**
1. Admin clicks "Connect iManage" for a tenant
2. Redirect to `{tenant.imanage_cloud_url}/auth/oauth2/authorize?...`
3. User authorizes
4. iManage redirects back with authorization code
5. App exchanges code for access_token + refresh_token
6. Tokens stored encrypted

**iManage Password Auth (for password tenants):**
1. Admin enters username + password in tenant config
2. Credentials stored encrypted
3. On each API call, obtain a fresh token via `POST /auth/oauth2/token` with grant_type=password

---

## 7. Non-Functional Requirements

### Performance

| Metric | Target |
|--------|--------|
| Webhook ingestion latency | < 500ms (receive to 200 response) |
| Dashboard page load | < 2 seconds |
| Webhook request list load (with filters) | < 3 seconds for 100K+ records |
| Queue processing throughput | 100+ webhooks/minute |
| Database query time (p95) | < 100ms |

### Scalability

- Support 200+ active tenants (up from current ~50)
- Support 500K+ webhook requests in the database
- Horizontal scaling: additional queue workers can be added without code changes
- Database indexing strategy for high-volume queries (tenant_id + created_at composite indexes)

### Reliability

- 99.9% uptime target for webhook ingestion endpoint
- Zero data loss: every received webhook is persisted before acknowledgment
- Graceful degradation: if iManage is down, webhooks are queued and processed when available
- Automatic recovery: failed jobs are retried, workers restart on crash

### Security

- OWASP Top 10 compliance
- All data encrypted in transit (TLS 1.2+) and sensitive data encrypted at rest
- Regular dependency vulnerability scanning
- No PII in application logs

### Maintainability

- PSR-12 coding standard enforced via PHP-CS-Fixer
- Static analysis via PHPStan (level 6+)
- All public methods documented with PHPDoc
- Architecture Decision Records (ADRs) for significant design choices

---

## 8. V1 Gaps Being Addressed

| # | V1 Gap | V2 Solution | Module |
|---|--------|-------------|--------|
| 1 | 40+ hardcoded display number parsing cases | Database-driven DisplayNumberParsingConfig | 4 |
| 2 | Hardcoded client name transformations | Configurable ClientNameTransformationConfig | 4 |
| 3 | Hardcoded workspace naming schemas (duplicated in 2 functions) | Single configurable WorkspaceNamingConfig template | 4 |
| 4 | Hardcoded custom field mapping rules | Rules-based CustomFieldMappingRule engine | 4 |
| 5 | Legacy JSON mapping files | Database LegacyAliasMapping table with import tool | 4 |
| 6 | Hardcoded processing filters (Griffitts, Gionis) | Configurable WebhookProcessingFilter rules | 4 |
| 7 | Global helper functions (1700+ lines, raw curl) | Service classes (ClioApiService, ImanageApiService) | 5, 6 |
| 8 | No webhook signature verification | Full X-Hook-Signature verification | 7, 16 |
| 9 | Unauthenticated debug routes in production | Removed | 16 |
| 10 | Plain-text credential storage | Encrypted at rest | 16 |
| 11 | Client webhook handlers are stubs | Implemented | 7 |
| 12 | Matter opened/closed handlers are stubs | Implemented | 7 |
| 13 | No tests | 80%+ test coverage target | 17 |
| 14 | No API for external consumption | Full REST API with Sanctum auth | 15 |
| 15 | No rate limiting on API calls | Rate limiting with backoff | 5, 6 |
| 16 | No idempotency (retry creates duplicates) | Check-before-create pattern | 6 |
| 17 | No admin action audit trail | ActivityLog model | 11 |
| 18 | No automatic subscription expiry | Scheduled expiry command | 9 |
| 19 | No health check endpoint | `/health` and `/api/v1/health` endpoints | 11, 15 |
| 20 | No logging correlation IDs | UUID correlation IDs through entire pipeline | 7, 17 |
| 21 | Incomplete `.env.example` | Complete with documentation | 17 |
| 22 | Database queue driver | Redis-backed queues | 17 |
| 23 | Scheduled commands process all tenants | Filter by active subscription | 9, 17 |
| 24 | No webhook filtering/search UI | Full filtering, search, and bulk operations | 11 |
| 25 | No tenant self-service portal | Tenant Portal module | 13 |
| 26 | Hardcoded iManage OAuth URL | Tenant-specific URL from database | 6 |
| 27 | Race condition in sequence number generation | Atomic operations with locking | 10 |
| 28 | Inconsistent error logging | Structured logging with levels | 17 |
| 29 | No bulk operations | Bulk reattempt, sync, extend | 14 |
| 30 | Siri Glimstad special workspace lookup logic | Configurable duplicate detection strategy per tenant | 4, 7 |

---

## 9. Open Questions / Future Scope

### Open Questions (To Resolve Before Development)

1. **Data migration strategy:** Should V2 be deployed alongside V1 with a gradual migration, or a hard cutover? Recommendation: parallel deployment with webhook routing switchover per tenant.

2. **iManage custom field slot allocation:** iManage has custom1-custom30. Custom1 is always client_id, custom2 is always matter_id, custom29 is practice_area, custom30 is sub_practice_area. Should slots 3-28 be fully configurable per tenant, or should some be reserved?

3. **Clio custom field write-back:** Currently only writes workspace URL. Should V2 support writing arbitrary data back to Clio custom fields?

4. **Workspace deactivation on matter close:** V1 stubs for matter opened/closed. Should V2 deactivate iManage workspaces when a Clio matter is closed? This could be a per-tenant configuration option.

5. **Multi-library support:** V1 assumes one library per tenant. Are there tenants that need workspaces created across multiple libraries?

6. **Notification channels:** Should Slack integration be a launch requirement or a post-launch enhancement? What about Microsoft Teams?

7. **Historical data:** Should V2 backfill configurations by analyzing existing webhook request bodies and their outcomes, or rely on manual re-configuration?

### Future Scope (Post-V2)

- **Document-level operations:** Beyond workspaces, manage individual documents in iManage.
- **Bi-directional sync:** Changes in iManage trigger updates back to Clio.
- **Multi-DMS support:** Extend beyond iManage to other document management systems (NetDocuments, SharePoint).
- **AI-powered mapping suggestions:** Use NLP to auto-suggest practice area and template mappings.
- **Mobile app:** Tenant users can monitor processing from mobile devices (API already supports this).
- **White-label tenant portal:** Law firms can customize the portal with their branding.
- **Billing integration:** Automatic invoice generation based on subscription and usage data.
- **Webhook replay:** Ability to replay historical webhooks through a new configuration for testing.
- **Configuration versioning:** Track and rollback configuration changes with a version history.

---

## Appendix A: V1 Tenant-Specific Configurations Reference

This table documents all tenant-specific logic discovered in V1 to ensure nothing is missed during V2 configuration migration.

### Display Number Parsing Patterns

| Tenant | V1 Pattern | V2 Strategy | V2 Config |
|--------|-----------|-------------|-----------|
| LCI, LCI-UK | Bracket extraction + dot split | `bracket_extraction` | inner_delim: `.` |
| Foundation Law, Cimmarustilaw, KW Law, Odle Law Firm, Mark Williams P.C. | Dash split: `{client}-{matter}` | `split_delimiter` | delim: `-`, client: 0, matter: 1 |
| Andrea Tazioli, Wisefield Law | Dot split: `{client}.{matter}` | `split_delimiter` | delim: `.`, client: 0, matter: 1 |
| Gionis Lilly | Dash split with validation + status filter | `split_delimiter` | delim: `-`, validation: `/^\d{5}-\d{3}$/`, status_filter: `OPEN`, matter=full display |
| KRB Lawyers | Dash split, matter=full display_number | `split_delimiter` | delim: `-`, client: 0, matter: full |
| Mkenga, Diaz Law | Dash split | `split_delimiter` | delim: `-`, client: 0, matter: 1 |
| PSGM | Dot split | `split_delimiter` | delim: `.`, client: 0, matter: 1 |
| TenkLaw | Dot split then dash split on matter part | `split_delimiter_nested` | primary: `.`, secondary: `-` |
| Osborne Wise | Underscore split then dot split | `split_delimiter_nested` | primary: `_`, secondary: `.` |
| Curata Partners | Dash split then dot split, swapped positions | `split_delimiter_nested` | primary: `-`, secondary: `.`, client: 1, matter: 0 |
| Valkenaar, Mobley | Dash split then dot split | `split_delimiter_nested` | primary: `-`, secondary: `.` |
| MFB | Slash split | `split_delimiter` | delim: `/` |
| Khazaeli | Dot split, client from payload client_id | `split_delimiter` | delim: `.`, matter: 0, client: payload |
| Vladeck Raskin | Entire display_number = client_id | `display_number_as_client` | - |
| Frost | Dot split then dash split on first part | `split_delimiter_nested` | primary: `.`, secondary: `-` |
| CBMS Law | Dash split | `split_delimiter` | delim: `-` |
| Scale LLP | Dash split, client from payload | `split_delimiter` | delim: `-`, matter: 0, client: payload |
| Embry Law | Client and matter from Clio IDs with prefix | `clio_ids` | prefix: `CLIO-` |
| Hall & Diana | Matter=display_number, client from payload | `display_number_as_matter` | client: payload |
| Shiels Law | Dash split, matter=first part, client from payload | `split_delimiter` | custom config |
| Inceptiv Law | Both from Clio payload IDs | `clio_ids` | no prefix |
| Mills Co | Custom field extraction + legacy fallback | `custom_field_extraction` | field: `Proclaim Matter Number`, fallback: legacy |
| Nautica Law | Dash split then slash split | `split_delimiter_nested` | primary: `-`, secondary: `/` |
| Scharf Banks | Dash split, matter zero-padded to 4 digits | `split_delimiter` | post_process: pad_left(4) |
| Blacks Legal, Daake Law | Client from payload, matter from dash split | `split_delimiter` | custom config |
| Siri Glimstad | Legacy lookup fallback | `legacy_alias_lookup` | fallback: dash split |
| Horn Williamson | Complex legacy + auto-increment | `legacy_alias_lookup` | fallback: auto-increment |
| Acevedo Belt | Client and matter from Clio IDs with "Clio-" prefix | `clio_ids` | prefix: `Clio-` |
| CHK Legal, Munzinger Law | Dash split then dot split | `split_delimiter_nested` | primary: `-`, secondary: `.` |
| McKinley Irvin | Semicolon split then dash split | `split_delimiter_nested` | primary: `;`, secondary: `-` |
| AviaLaw PLLC | ` - ` split then dash split | `split_delimiter_nested` | primary: ` - `, secondary: `-` |
| FT Legal | Regex extraction | `regex` | pattern: `/\/(\d+)\.(\d+)/` |
| The Estate Lawyers | Client from payload, matter from number field | `clio_ids` | custom config |
| Mathers McHenry | Client from payload, matter from dash split | `split_delimiter` | custom config |
| Groundswell Law | Dash split: `Name-ClientID-MatterID` | `split_delimiter` | delim: `-`, client: 1, matter: 2 |
| Hackler Flynn | Dash split then dot split | `split_delimiter_nested` | primary: `-`, secondary: `.` |
| Elegy Law | Both from Clio payload IDs | `clio_ids` | no prefix |
| Sofos Law LLC | Dash split | `split_delimiter` | delim: `-` |
| Griffitts LLP | Custom field filter + dash/slash split | Filter + `split_delimiter_nested` | filter: iManage Creation=yes |
| Adams Law | Dot split, matter part before dash | `split_delimiter_nested` | primary: `.`, secondary: `-` |
| Tannenbaum Law Group | Dot split | `split_delimiter` | delim: `.` |

### Client Name Transformations

| Tenant | Transformation |
|--------|---------------|
| Embry Law | `last_name_first` - "John Smith" -> "Smith, John" |
| McKinley Irvin (Test + Prod) | `reverse_words` - "John Michael Smith" -> "Smith Michael John" |
| All others | `none` |

### Matter Description Overrides

| Tenant | Override |
|--------|---------|
| Vladeck Raskin | `use_client_description` - matter description becomes client name |
| Embry Law | `use_display_number` |
| Acevedo Belt | `composite_template` - `{display_number_part_0}-{display_number_part_1} - {description}` |
| Gionis Lilly | `strip_prefix` - removes matter_id prefix from description |
| All others | `none` |

---

## Appendix B: Clio API Capabilities Reference (v4 OpenAPI Analysis)

This appendix documents the full scope of the Clio API based on analysis of the official OpenAPI specification. It is used to inform V2 design decisions and identify opportunities beyond what V1 currently exploits.

---

### B.1 API Overview

| Property | Detail |
|----------|--------|
| API Version | v4 (latest minor: 4.0.13). Specified via `X-API-VERSION` header. V1 is pinned to 4.0.8. |
| Base URLs | US: `https://app.clio.com/api/v4` · EU: `https://eu.app.clio.com/api/v4` · CA: `https://ca.app.clio.com/api/v4` · AU: `https://au.app.clio.com/api/v4` |
| Authentication | OAuth 2.0 authorization code flow |
| Pagination | Token-based via `page_token` query param. Max 200 records per page. |
| Field Selection | Explicit `fields` param required. Nested associations use curly-brace syntax. |
| ETags | Every resource returns an `etag` for optimistic concurrency. Required for PATCH operations. |
| Rate Limiting | Externally documented. Not defined in spec. V1 has no rate limit handling. |
| Regions | **4 regions: US, EU, CA, AU.** V1 only has 3 regions configured (missing AU). |

**V2 Action:** Add Australia region to `clio_locations` table. Upgrade `X-API-VERSION` to 4.0.13 across all API calls.

---

### B.2 Webhook Events — Full Capability Matrix

Clio supports **10 webhook models** with up to **6 event types** each, giving 60 possible webhook subscriptions. V1 uses 5.

| Model | created | updated | deleted | matter_opened | matter_pended | matter_closed | V1 Status |
|-------|---------|---------|---------|---------------|---------------|---------------|-----------|
| `matter` | ✅ | ✅ | ✅ | stub only | — | stub only | Core, partially implemented |
| `contact` | ✅ | ❌ | ❌ | — | — | — | Created only, logs only |
| `document` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `folder` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `activity` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `bill` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `calendar_entry` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `task` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `communication` | ❌ | ❌ | ❌ | — | — | — | Not implemented |
| `clio_payments_payment` | ❌ | ❌ | ❌ | — | — | — | Not implemented |

**V2 Priority Webhook Additions:**

| Priority | Model + Event | Use Case |
|----------|--------------|----------|
| High | `contact` updated, deleted | When a client name, address, or custom field changes in Clio, update the iManage client record |
| High | `matter` matter_opened, matter_pended | Update iManage workspace status field when matter status changes |
| Medium | `document` created, updated, deleted | Phase 2: Sync Clio documents into iManage (or log document activity on workspace) |
| Medium | `folder` created, updated, deleted | Phase 2: Mirror Clio folder structure changes |
| Low | `task` created, updated, deleted | Write task metadata to iManage workspace custom fields |
| Low | `calendar_entry` created, updated, deleted | Write court date metadata to iManage workspace |

**Webhook Configuration Fields:**
- `url` — HTTPS only
- `fields` — Controls exactly which fields are included in the payload (same syntax as API `fields` param)
- `model` — The resource to watch
- `events` — Array of event types
- `expires_at` — ISO-8601 expiry. Must be renewed.
- `shared_secret` — Auto-generated. Used for HMAC payload signature verification. **V1 stores this but never verifies it.**

**V2 Action:** Implement `X-Hook-Signature` HMAC verification on every incoming webhook. Register `contact` updated/deleted and `matter` opened/pended webhooks for all tenants.

---

### B.3 Webhook Payload Fields — Matter (Current vs Expanded)

**V1 currently requests these fields** on matter webhooks:
```
id, etag, number, display_number, custom_number, description, status, location,
client_reference, client_id, open_date, close_date, pending_date, client,
originating_attorney, practice_area, responsible_attorney, statute_of_limitations,
custom_field_values{id,etag,field_name,value,custom_field},
custom_field_set_associations, group{id,name}
```

**Additional fields available in V2 (add to webhook field spec):**

| Field | Type | Why It Matters |
|-------|------|---------------|
| `billable` | boolean | Could flag workspace for billing status in iManage |
| `billing_method` | string | flat / contingency / hourly — useful metadata |
| `last_activity_date` | date | Detect stale workspaces |
| `matter_stage` | object | Stage name and order — lifecycle tracking |
| `matter_stage_updated_at` | datetime | When stage last changed |
| `responsible_staff` | object | Additional user for mapping/security |
| `relationships` | array | Opposing counsel, co-counsel, judges — enrich workspace security |
| `matter_bill_recipients` | array | Who receives invoices |
| `currency` | object | For multi-currency firms |
| `created_at` | datetime | Matter creation timestamp |
| `folder` | object | Clio's root folder ID for this matter |

---

### B.4 Matter Resource — Complete Field Reference

#### Base Fields (all available via API and webhook)

| Field | Type | V1 Uses | Notes |
|-------|------|---------|-------|
| `id` | integer | ✅ | |
| `etag` | string | ✅ | Required for PATCH |
| `number` | integer | ❌ | Sequential auto-number within account |
| `display_number` | string | ✅ | Human-readable reference (core to V1 parsing) |
| `custom_number` | string | ✅ | User-defined number |
| `description` | string | ✅ | |
| `status` | string | ✅ | Pending / Open / Closed |
| `location` | string | ✅ | Geographic location |
| `client_reference` | string | ✅ | External reference |
| `client_id` | integer | ✅ | Client contact ID |
| `billable` | boolean | ❌ | |
| `billing_method` | string | ❌ | flat / contingency / hourly |
| `open_date` | date | ✅ | |
| `close_date` | date | ✅ | |
| `pending_date` | date | ✅ | |
| `last_activity_date` | date | ❌ | Most recent activity |
| `matter_stage_updated_at` | datetime | ❌ | |
| `has_tasks` | boolean | ❌ | |
| `shared` | boolean | ❌ | Clio Connect sharing status |
| `maildrop_address` | string | ❌ | Unique email for matter — could store in workspace |
| `require_utbms_codes` | boolean | ❌ | |
| `created_at` | datetime | partial | |
| `updated_at` | datetime | ❌ | |

#### Association Fields (requested via `fields` param)

| Association | V1 Uses | Notes |
|-------------|---------|-------|
| `client` | ✅ | Contact base |
| `practice_area` | ✅ | |
| `group` | ✅ | |
| `responsible_attorney` | ✅ | |
| `originating_attorney` | ✅ | |
| `responsible_staff` | ❌ | Different from responsible_attorney |
| `matter_stage` | ❌ | Pipeline stage (name, order, practice_area_id) |
| `folder` | ❌ | Clio root folder for this matter |
| `statute_of_limitations` | ✅ | |
| `contingency_fee` | ❌ | |
| `matter_budget` | ❌ | Budget amount and utilization |
| `currency` | ❌ | |
| `relationships` | ❌ | Related contacts with role descriptions |
| `matter_bill_recipients` | ❌ | |
| `custom_field_values` | ✅ | Core to custom field mapping |
| `custom_field_set_associations` | ✅ | |
| `task_template_list_instances` | ❌ | Applied task templates |
| `account_balances` | ❌ | Trust/operating balance |

---

### B.5 Contact/Client Resource — Complete Field Reference

**V1 currently requests:** `id, name, created_at, etag, custom_field_values{...}, custom_field_set_associations`

**Missing fields V1 does not request:**

| Field | V2 Use Case |
|-------|------------|
| `first_name`, `last_name`, `middle_name` | Structured name parts — removes need for name-reversal transformations (Embry Law, McKinley Irvin) if names can be composed from parts instead |
| `prefix`, `title` | Professional title for workspace metadata |
| `primary_email_address` | Store in iManage workspace custom field |
| `primary_phone_number` | Store in iManage workspace custom field |
| `addresses` | City, province, postal code — workspace metadata |
| `company` | Parent company relationship |
| `is_client`, `is_bill_recipient`, `is_co_counsel` | Role classification |
| `type` | Company vs Person — affects name formatting |
| `related_contacts` | Contact network |
| `date_of_birth` | Relevant for PI/medical matters |

**Key insight:** If V2 requests `first_name` and `last_name` separately on contact webhooks, many of the V1 name-transformation hacks (reversing words, splitting on commas) can be replaced with proper name composition logic using structured fields — no tenant-specific code required.

---

### B.6 Custom Fields — Complete Type Reference

Clio supports **12 custom field types:**

| Type | Description | V2 Handling |
|------|-------------|-------------|
| `text_line` | Single-line text | Direct string mapping |
| `text_area` | Multi-line text | Direct string mapping |
| `numeric` | Number | Cast to string for iManage |
| `currency` | Monetary value | Format with currency symbol |
| `date` | Date value | Format per tenant locale |
| `time` | Time value | Format per tenant locale |
| `checkbox` | Boolean | Map to yes/no or 1/0 |
| `email` | Email address | Direct string mapping |
| `url` | URL | Direct string mapping |
| `picklist` | Dropdown (has `picklist_option` with `option` string) | Map to iManage value via lookup |
| `contact` | Reference to another Contact | Resolve to contact name |
| `matter` | Reference to another Matter | Resolve to matter display_number |

**V1 Gap:** V1 only handles `text_line`, `picklist` (partially), and `date` types. Types like `contact`, `matter`, `currency`, and `checkbox` are not handled gracefully.

**V2 Action:** The custom field mapping engine (Module 4, Section 4.6) must handle all 12 field types with appropriate transformation and fallback logic.

**Picklist Resolution:** For `picklist` type fields, the value returned is the raw `option` string. V1 resolves picklist options via a separate API call for some tenants (McKinley Irvin does this for Jurisdiction County). V2 should cache picklist options per field and resolve automatically without per-tenant code.

---

### B.7 Practice Areas — Category Field

Clio practice areas have a `category` field with **56 standardized values** (e.g., `family`, `personal_injury`, `real_estate`, `criminal`, `employment_and_labor`, etc.).

**V1 Gap:** V1 does not sync or store the `category` field, only `name` and `code`.

**V2 Opportunity:** The `category` field is a standardized enum that could be used to:
- Auto-suggest iManage practice area mappings during tenant onboarding
- Drive default workspace template selection based on category
- Power reporting and analytics across tenants

**V2 Action:** Add `category` field to `ClioPracticeArea` model and sync it.

---

### B.8 Matter Stages — New Concept for V2

**Not used in V1 at all.**

Matter Stages are a pipeline system within Clio where each practice area can define ordered stages a matter moves through (e.g., "Initial Consultation" → "Discovery" → "Trial Prep" → "Closed").

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | |
| `name` | string | Stage name |
| `order` | integer | Position in pipeline |
| `practice_area_id` | integer | Which practice area this stage belongs to |

**V2 Opportunity:**
- Sync matter stages per tenant
- Expose stage changes in the tenant portal (matter lifecycle view)
- Trigger iManage workspace custom field updates when a matter moves to a new stage
- Show stage progression in the webhook request detail view

---

### B.9 Relationships API — Workspace Security Enrichment

**Not used in V1 at all.**

The Relationships API (`/relationships.json`) defines explicit links between contacts and matters with a `description` field indicating the role (opposing counsel, expert witness, co-counsel, judge, etc.).

**V2 Opportunity:**
- Fetch relationships when processing a matter webhook
- Map Clio relationship types to iManage security group assignments
- Auto-apply workspace security: if a contact is "co-counsel" on a matter, grant their iManage user read access to the workspace
- Expose related contacts in the tenant portal matter detail view

**Required API call in webhook pipeline:**
```
GET /matters/{matter_id}/related_contacts.json?fields=id,name,type,relationship_description
```

---

### B.10 Unused Resources — V2 Phase Roadmap

#### Phase 2 (Near-term, post-launch)

| Resource | Use Case |
|----------|----------|
| **Contact updated/deleted webhooks** | Keep iManage client records in sync when firm updates client data in Clio |
| **Matter Stage sync** | Expose matter lifecycle in tenant portal; trigger workspace updates on stage changes |
| **Relationships** | Enrich iManage workspace security based on matter relationships (co-counsel, opposing party) |
| **Related Contacts** | Pull full cast of matter contacts for workspace metadata |
| **Custom Actions** | Register a "View in iManage" link inside Clio matter UI pointing to the iManage workspace URL |
| **Log Entries** | Sync Clio audit trail to workspace for compliance-heavy tenants |

#### Phase 3 (Future, Tier 2 feature)

| Resource | Use Case |
|----------|----------|
| **Documents + Folders** | Full bidirectional document sync between Clio and iManage (large scope, separate feature) |
| **Calendar Entries** | Sync court dates to iManage workspace as metadata or linked documents |
| **Tasks** | Sync matter tasks to iManage workspace metadata |
| **Matter Stages (full)** | Drive iManage workspace lifecycle: auto-close/archive workspaces when matter reaches terminal stage |
| **Notes** | Store Clio notes as documents in iManage |
| **Communications** | Archive email/phone communications to iManage workspace |
| **UTBMS Codes** | Standardized billing code mapping for workspace document categorization |

#### Phase 4 (Long-term / client demand)

| Resource | Use Case |
|----------|----------|
| **Bills / Activities** | Financial data in iManage for compliance-heavy tenants |
| **Report Presets** | Generate iManage sync reports directly from Clio report framework |
| **Bank Transactions** | Trust account reconciliation against iManage workspace records |
| **Matter Budget** | Budget utilization metadata in iManage workspace |

---

### B.11 Clio Custom Actions — "View in iManage" Button

**Not used in V1.**

The Custom Actions API allows V2 to register custom links directly inside the Clio UI. Available placement locations: `activities`, `documents`, `contacts`, `matters`, `folders`.

**V2 Opportunity:** After creating an iManage workspace, register a Custom Action on the matter that adds a "View in iManage" button. Clicking it opens the workspace URL directly in iManage. This replaces the current approach of writing the workspace URL to a Clio custom field.

**How it works:**
- POST `/custom_actions.json` with `label`, `url` (can include `{matter.id}` interpolation), and `ui_reference` (which Clio page to show the action on)
- Can be done per-tenant at onboarding or per-matter at workspace creation time

---

### B.12 API Version Upgrade Notes (4.0.8 → 4.0.13)

V1 is pinned to `X-API-VERSION: 4.0.8`. The changes in minor versions that are relevant to V2:

| Version | Change |
|---------|--------|
| 4.0.9 | Contact visibility filtering added. Contacts filtered/redacted based on firm's visibility permission settings. |
| 4.0.10-4.0.12 | Minor additions and deprecations (review Clio changelog before upgrading) |
| 4.0.13 | Association limits on Contacts. Returns 422 when exceeded. Latest default version. |

**V2 Action:** Pin to `4.0.13` as the target version. Test all endpoints against the new version during development. Review Clio's changelog for any breaking changes between 4.0.8 and 4.0.13.

---

### B.13 API Patterns & Gotchas for V2 Developers

1. **Webhook payloads ≠ API responses.** The webhook payload only contains the fields specified in the `fields` param when the webhook was registered. If you need more data, make a follow-up API call (V1 already does this pattern — receives webhook, then calls `get_single_clio_matter()`).

2. **ETags are required for PATCH.** Every PATCH request must include the `etag` value from the latest GET response or it will be rejected with a conflict error.

3. **Users are read-only.** No POST/PATCH/DELETE on users. Only GET.

4. **Pagination is token-based.** Use `page_token` from the previous response, not offset. Max 200 per page.

5. **Field selection is mandatory.** Never request without a `fields` param — you'll get minimal data or a slow response. Always specify exactly what you need.

6. **Picklist values are strings.** When reading a picklist custom field, the `value` is the raw option text string, not an ID. The associated `picklist_option` object provides the ID. V2's custom field mapping engine must handle type-aware value resolution.

7. **Webhook expiry is real.** Webhooks stop firing after `expires_at`. V2 must have automatic renewal logic running ahead of expiry.

8. **Webhook shared_secret is one-time.** The `shared_secret` is only returned in the creation response. Store it encrypted immediately — it cannot be retrieved again.

9. **Contact visibility (v4.0.9+).** If the OAuth user has restricted contact visibility, contact data in webhook payloads may be redacted. Ensure the authorized Clio user has full contact visibility.

10. **Rate limits.** Not defined in the spec but enforced by Clio. Implement exponential backoff and respect `Retry-After` headers.

---

*This document serves as the complete specification for building iman-clio V2. All existing V1 behavior should be reproducible through the new configuration-driven architecture. No tenant-specific logic should be hardcoded in V2.*
