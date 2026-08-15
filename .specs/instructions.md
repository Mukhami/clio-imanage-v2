# Specs — Structure & Conventions

This folder contains the detailed implementation specifications for every module in iman-clio V2.
It is the **execution layer** of the two master documents:

- `../V2_REQUIREMENTS.md` — what the system must do and why
- `../V2_DESIGN.md` — class diagrams, database schema, service layer, data flow

These specs translate requirements and design into concrete, module-by-module work packages
that can be handed directly to a developer (or an AI coding agent) to implement.

---

## Folder Structure

```
.specs/
  instructions.md               ← This file
  module-01-auth-user-management/
    requirements.md
    design.md
    plan.md
  module-02-role-permission-management/
    requirements.md
    design.md
    plan.md
  module-03-tenant-management/
    ...
  module-04-tenant-configuration-engine/
    ...
  module-05-clio-integration-layer/
    ...
  module-06-imanage-integration-layer/
    ...
  module-07-webhook-processing-pipeline/
    ...
  module-08-mapping-engine/
    ...
  module-09-subscription-billing/
    ...
  module-10-sequence-number-engine/
    ...
  module-11-monitoring-observability/
    ...
  module-12-notification-system/
    ...
  module-13-tenant-portal/
    ...
  module-14-admin-panel-back-office/
    ...
  module-15-api-layer/
    ...
  module-16-security-compliance/
    ...
  module-17-infrastructure-devops/
    ...
```

---

## What Each File Contains

### `requirements.md`
The scoped, self-contained requirements for this module only. Includes:

- **Purpose** — one paragraph explaining what problem this module solves
- **Scope** — what is and is not included in this module
- **Dependencies** — which other modules must be built first
- **Functional Requirements** — numbered list (FR-01, FR-02, ...) of what the module must do
- **Non-Functional Requirements** — performance, security, reliability constraints specific to this module
- **Acceptance Criteria** — checkboxable list; the definition of done

### `design.md`
The technical design scoped to this module only. Includes:

- **Database Tables** — full column definitions for every table owned by this module
- **Eloquent Models** — model class with fillable, casts, relationships, scopes, and any custom methods
- **Service Classes** — full method signatures with parameter types, return types, and a one-line description of each method
- **Jobs / Livewire Components** — class name, queue, inputs, outputs, and what it dispatches next
- **Data Flow** — step-by-step flow specific to this module (where relevant)
- **Key Design Notes** — decisions, trade-offs, or gotchas specific to this module

### `plan.md`
The ordered implementation plan for this module. Includes:

- **Build Order** — a clear statement of what phase must come before what
- **Phases** — grouped sets of tasks (e.g., Phase 1: Database, Phase 2: Service Layer, Phase 3: Jobs, Phase 4: UI)
- **Tasks** — each task is:
  - Checkboxable (`- [ ]`)
  - Specific enough to be executed without ambiguity
  - At the level of: "create this file, this class, this method, with this behaviour"
  - Example: `- [ ] Create migration \`create_display_number_parsing_configs_table\` with columns: id, tenant_id (FK), strategy (enum), delimiter (nullable string), ...`
- **Test Checkpoints** — after each phase, a list of things to manually verify before moving to the next phase

---

## Module Build Order

Modules have dependencies. Build them in this order:

```
Phase A — Foundation (no dependencies)
  17. Infrastructure & DevOps       ← Laravel project setup, Redis, Horizon, queue config
  16. Security & Compliance         ← Encryption helpers, middleware, env validation
   1. Auth & User Management        ← Users table, Fortify, login flow
   2. Role & Permission Management  ← Spatie roles/permissions, seeder

Phase B — Core Domain (requires Phase A)
   3. Tenant Management             ← Tenants table, CRUD, credential storage
   9. Subscription & Billing        ← Subscriptions table, status gate
  10. Sequence Number Engine        ← Sequence config table, atomic number generation

Phase C — Configuration Engine (requires Phase B)
   4. Tenant Configuration Engine   ← Parsing, naming, mapping rules — the hardest module

Phase D — Integration Layer (requires Phase B)
   5. Clio Integration Layer        ← ClioApiService, OAuth, token management
   6. iManage Integration Layer     ← ImanageApiService, OAuth/password auth
   8. Mapping Engine                ← Practice area, template, group, user mappings

Phase E — Pipeline (requires Phase C + D)
   7. Webhook Processing Pipeline   ← Jobs, queue, WebhookRequest lifecycle

Phase F — Application Layer (requires Phase E)
  11. Monitoring & Observability    ← Dashboards, health checks, audit log
  12. Notification System           ← Laravel Notifications, mail, Slack
  13. Tenant Portal                 ← Self-service portal for law firms
  14. Admin Panel & Back Office     ← Full admin UI
  15. API Layer                     ← REST API with Sanctum
```

---

## Conventions

- **IDs** — all tables use `id` (auto-increment bigint) as primary key unless stated otherwise
- **Timestamps** — all tables include `created_at` and `updated_at` via `$timestamps = true`
- **Soft Deletes** — tables marked with `(soft)` use `deleted_at`
- **Encryption** — all sensitive fields use Laravel's `'encrypted'` cast, not a custom encryption service
- **Tenant Scoping** — all tenant-scoped models apply `TenantScope` global scope; cross-tenant admin queries use `withoutGlobalScope(TenantScope::class)`
- **Queue Names** — `webhooks`, `imanage`, `long_term`, `maintenance`, `notifications` (see Module 17)
- **Service Injection** — services are resolved via Laravel's container; never instantiated with `new` in controllers or jobs
- **No Hardcoding** — no tenant name, ID, or firm-specific logic ever appears in code; all config is database-driven

---

## How to Use These Specs

When starting a module:

1. Read `requirements.md` to understand the full scope before touching code
2. Read `design.md` to understand the exact schema, classes, and contracts you're implementing
3. Work through `plan.md` top to bottom, checking off tasks as you go
4. At each test checkpoint, verify before moving to the next phase
5. Cross-reference `../V2_DESIGN.md` for system-wide diagrams and cross-module data flows
6. Cross-reference `../V2_REQUIREMENTS.md` for the original rationale behind any requirement

---

## Status Tracking

Each `plan.md` uses checkboxes. As implementation progresses:

- `- [ ]` — not started
- `- [x]` — complete
- `- [~]` — in progress
- `- [!]` — blocked (add a note below explaining why)

---

## Packages & Installation

This section is the authoritative list of every package required for V2. It is the first thing
executed when setting up the new project (covered in detail in Module 17's plan.md).

V1 ran on Laravel 8 / Livewire 2 / Laravel Mix / PHPUnit / Yajra DataTables.
**V2 replaces all of these.** Do not carry V1's composer.json forward.

---

### System Requirements

| Requirement | Version |
|-------------|---------|
| PHP | ^8.3 |
| MySQL | ^8.0 |
| Redis | ^7.0 |
| Node.js | ^20.0 (for Vite) |
| Composer | ^2.7 |

---

### PHP / Composer Packages

#### Production Dependencies

```bash
# Laravel framework — latest stable 11.x
composer require laravel/framework:^11.0

# Authentication backend (replaces Jetstream's auth scaffolding)
composer require laravel/fortify

# API token authentication (for Module 15 API layer)
composer require laravel/sanctum

# Queue monitoring dashboard
composer require laravel/horizon

# Livewire 3 — full-stack UI framework
composer require livewire/livewire:^3.0

# Role & permission management (carried over from V1, updated to v6)
composer require spatie/laravel-permission:^6.0

# Admin action audit logging (new in V2 — replaces missing audit trail in V1)
composer require spatie/laravel-activitylog:^4.0

# Structured HTTP client — replaces all raw curl calls in V1's ClioApiHelper and iManageApiHelper
# Used to build ClioApiService and ImanageApiService as proper SDK connectors
composer require saloonphp/laravel-saloon:^3.0

# Redis PHP client (required for queues, cache, and tenant job locks)
composer require predis/predis:^2.0

# Livewire data tables — replaces Yajra DataTables (jQuery-based) from V1
composer require rappasoft/laravel-livewire-tables:^3.0

# Error tracking (carried over from V1, updated)
composer require sentry/sentry-laravel:^4.0

# AWS SDK — retained if AWS Secrets Manager is used for credential storage
# Remove if credentials are stored only in the encrypted database
composer require aws/aws-sdk-php:^3.0
```

#### Development Dependencies

```bash
# Pest — replaces PHPUnit directly. Modern, expressive testing framework.
composer require pestphp/pest:^2.0 --dev
composer require pestphp/pest-plugin-laravel:^2.0 --dev

# Pest plugin for Livewire component testing
composer require pestphp/pest-plugin-livewire:^2.0 --dev

# Debugging toolbar (dev only)
composer require barryvdh/laravel-debugbar:^3.0 --dev

# IDE helper — generates PHPDoc for facades and models (used by PhpStorm / VSCode Intelephense)
composer require barryvdh/laravel-ide-helper:^3.0 --dev

# Laravel Telescope — request/job/query inspector for local development
composer require laravel/telescope:^5.0 --dev
```

---

### Node / npm Packages

V1 used Laravel Mix. **V2 uses Vite** (Laravel 11 default build tool).

#### Install

```bash
npm install
```

#### Packages (added on top of Laravel 11 Vite scaffold)

```bash
# Tailwind CSS 3 and its official plugins
npm install -D tailwindcss@^3.0 @tailwindcss/forms @tailwindcss/typography postcss autoprefixer

# Alpine.js — lightweight JS reactivity (ships with Livewire but pinned explicitly)
npm install -D alpinejs

# Axios — HTTP client for any JS-side requests
npm install -D axios
```

#### Optional UI Component Library

V2 uses Livewire + Tailwind. Choose **one** of the following for pre-built components
(modals, dropdowns, toasts, tables). The plan.md files assume whichever is chosen is installed.

| Option | Package | Cost | Notes |
|--------|---------|------|-------|
| **Flux** *(recommended)* | `livewire/flux` | Paid (one-time) | Official Livewire component library from Caleb Porzio. Best DX and design quality. |
| **TallStackUI** | `tallstackui/tallstackui` | Free | Full Tailwind + Alpine + Livewire component set. Good free alternative. |
| **None** | — | Free | Build all components from scratch using Tailwind + Alpine directly. Most work. |

Install Flux (if chosen):
```bash
composer require livewire/flux
php artisan flux:install
```

Install TallStackUI (if chosen):
```bash
composer require tallstackui/tallstackui
php artisan tallstackui:install
```

---

### Post-Install Artisan Commands

Run these after all packages are installed and `.env` is configured:

```bash
# Generate app key
php artisan key:generate

# Publish and run all migrations
php artisan migrate

# Publish Horizon assets
php artisan horizon:install
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"

# Publish Sanctum migration (if not already published)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Publish Fortify config
php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"

# Publish Spatie Permission migration and config
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Publish Spatie Activity Log migration and config
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"

# Publish Sentry config
php artisan sentry:publish --dsn=your-dsn-here

# Publish Telescope (dev only)
php artisan telescope:install

# Run seeders (roles, permissions, Clio locations)
php artisan db:seed

# Generate IDE helpers (dev only)
php artisan ide-helper:generate
php artisan ide-helper:models --nowrite

# Build frontend assets
npm run build
```

---

### What Was Removed Compared to V1

| V1 Package | Reason Removed | V2 Replacement |
|------------|---------------|----------------|
| `laravel/jetstream` | Overkill; team management not needed | `laravel/fortify` (auth only) |
| `livewire/livewire ^2` | Major version upgrade | `livewire/livewire ^3` |
| `yajra/laravel-datatables` | jQuery-based, not Livewire-native | `rappasoft/laravel-livewire-tables` |
| `fruitcake/laravel-cors` | Built into Laravel 11 | Native CORS middleware |
| `doctrine/dbal` | Not needed in Laravel 11 | Removed |
| `laravel-mix` | Replaced by Vite | `vite` (Laravel 11 default) |
| `phpunit/phpunit` (direct) | Replaced by Pest | `pestphp/pest` |
| `facade/ignition` | Replaced by Spatie's built-in ignition in L11 | Built-in |
| `ext-curl` | Replaced by Saloon + Guzzle | `saloonphp/laravel-saloon` |
| Global helper files (`ClioApiHelper.php`, `iManageApiHelper.php`) | Anti-pattern, replaced by service classes | `ClioApiService`, `ImanageApiService` via Saloon |
