# User Management (Laravel)

This repository contains a Laravel-based user and admin management system built with Livewire full-page components. The app includes authentication, role and permission support, notifications, settings, activity/audit logging, profile management, dashboard and reports analytics, system health checks, and an admin UI for managing users, roles, and system settings.

**Primary features**
- **Authentication:** registration with usernames, login by either email or username, logout, session-based auth, login throttling, and optional email verification hooks.
- **Authorization:** role-based rules stored on the `users` table plus finer-grained permissions; policy-based checks via `UserPolicy`.
- **User Management:** admin CRUD for users with search, filtering, pagination, modals, and active/idle/offline presence indicators.
- **User Presence:** `last_seen_at` and `logged_out_at` tracking with green, yellow, and red status dots in the users table.
- **Roles & Permissions:** role model and permission registry with seeding and sync support.
- **Activity / Audit Logs:** important actions and login events recorded to `activity_logs` for admin auditing, analytics, and drawer-based activity detail review.
- **Profile Management:** user profile page with editable profile fields and profile picture support.
- **Settings:** application settings persisted via a `settings` table and accessed through a `Settings` helper.
- **Notifications:** app-level notifications stored in the `app_notifications` table and surfaced in the UI.
- **Dashboard analytics:** interactive dashboard charts for signup trends and role distribution using Chart.js.
- **Reports / Analytics:** operational analytics for user growth, permission usage, login trends, user presence, and system health signals.
- **System Health:** detailed checks for database, cache, queue, failed jobs, mail configuration, storage links, writable paths, app key, debug mode, environment, session security, and lockfile hygiene.
- **Theme Switching:** light/dark mode support through the app layout and theme toggle script.
- **Livewire UI:** SPA-like navigation using Livewire full-page components and `wire:navigate` with Blade layouts and Tailwind styling.
- **Module Builder:** dynamic CRUD module generation with built-in module record browsing and module scaffolding support.
- **Low-Code Foundation:** advanced module schema metadata, validation/condition configuration, computed fields, schema snapshots, and export-ready payload metadata while preserving the existing architecture.

## Recent Updates

- Added a Reports / Analytics module with charts for user growth, permission groups, login activity, and user presence.
- Added username support across registration, login, user management, profile settings, factories, and seed data.
- Added an Activity Detail Drawer for audit logs with metadata, affected record, request context, and before/after values where available.
- Added user presence tracking using `last_seen_at` and `logged_out_at`, plus green/yellow/red status indicators in the users table.
- Added a System Health page with infrastructure, filesystem, queue, mail, and security posture checks.
- Added profile management fields and profile page support.
- Added light/dark theme switching.
- Added interactive dashboard graphs for user signup trends and role distribution.
- Added auth throttling to login and registration to reduce brute-force risk.
- Added admin-managed application settings persisted through a `settings` table.
- Added managed roles and permissions with policy guards and a permission registry.
- Added an app notifications center with `app_notifications`.
- Added activity/audit logging via `activity_logs` for admin auditing.
- Added advanced low-code module builder features: rich_text/color/json/currency/date_range field support, JSON-driven validation rules, multi-rule conditional visibility with AND/OR groups, computed/read-only formula fields, schema snapshot versioning, export payload metadata, and per-module permission gates.

## Stack

- **PHP:** 8.2+
- **Framework:** Laravel 12
- **UI:** Livewire 4, Tailwind CSS
- **Build tool:** Vite
- **Charts:** Chart.js for interactive dashboard and reports visualizations

## Key Files And Places To Look

- [app/Livewire/](app/Livewire/) - Livewire page components for auth, dashboard, users, roles, settings, notifications, activities, reports, profile, and system health.
- [app/Models/](app/Models/) - `User`, `Role`, `Permission`, `ActivityLog`, `Setting`, `AppNotification`.
- [app/Livewire/Auth/Login.php](app/Livewire/Auth/Login.php) - login flow that accepts either email or username.
- [app/Livewire/Auth/Register.php](app/Livewire/Auth/Register.php) - registration flow with username validation.
- [app/Livewire/Activities/Index.php](app/Livewire/Activities/Index.php) - audit trail list and activity detail drawer state/formatting.
- [app/Livewire/Reports/Index.php](app/Livewire/Reports/Index.php) - reports and analytics data aggregation.
- [app/Livewire/SystemHealth/Index.php](app/Livewire/SystemHealth/Index.php) - system health and security posture checks.
- [app/Livewire/Modules/](app/Livewire/Modules/) - module builder and module record pages for dynamically generated CRUD modules.
- [app/Livewire/Modules/Builder.php](app/Livewire/Modules/Builder.php) - module builder state, schema editing, validation/condition configuration, and export payload generation.
- [app/Livewire/Modules/Records.php](app/Livewire/Modules/Records.php) - dynamic module record handling and action/query behavior.
- [app/Services/ModuleGenerator.php](app/Services/ModuleGenerator.php) - dynamic CRUD module generator.
- [app/Models/ModuleSnapshot.php](app/Models/ModuleSnapshot.php) - snapshot model for module schema versions and rollback metadata.
- [app/Services/ModuleSnapshotService.php](app/Services/ModuleSnapshotService.php) - service for creating, reading, and applying module schema snapshots.
- [app/Services/FieldTypeRegistry.php](app/Services/FieldTypeRegistry.php) - runtime registry for advanced field types.
- [app/Services/ValidationRuleBuilder.php](app/Services/ValidationRuleBuilder.php) - builds Livewire validation rules from JSON config.
- [app/Services/ConditionEvaluator.php](app/Services/ConditionEvaluator.php) - evaluates multi-rule visibility and condition configs.
- [app/Services/FormulaEvaluator.php](app/Services/FormulaEvaluator.php) - evaluates safe computed/read-only field formulas.
- [app/Http/Middleware/TrackUserActivity.php](app/Http/Middleware/TrackUserActivity.php) - updates user presence timestamps during authenticated requests.
- [app/Support/PermissionRegistry.php](app/Support/PermissionRegistry.php) - permission registration and syncing.
- [app/Support/Settings.php](app/Support/Settings.php) - settings helper for reading/writing system settings.
- [resources/js/dashboard-charts.js](resources/js/dashboard-charts.js) - chart initialization for the dashboard and reports pages.
- [database/factories/UserFactory.php](database/factories/UserFactory.php) - user factory used by seeders and tests.
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) - seeds initial roles, permissions, admin user, and settings.
- [database/seeders/LargeUserSeeder.php](database/seeders/LargeUserSeeder.php) - helper seeder that creates 50,000 users in chunks for load testing.

## Project Structure

Top-level layout (important folders and files):

```text
artisan
composer.json
package.json
phpunit.xml
README.md
vite.config.js
app/
    Http/
    Livewire/
    Models/
    Services/
    Support/
bootstrap/
config/
database/
    migrations/
    seeders/
public/
resources/
    css/
    js/
    views/
routes/
storage/
tests/
vendor/
```

## Quick Start

1. Clone and install dependencies

```bash
git clone <your-repository-url>
cd user-management
composer install
npm install
```

2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

3. Use SQLite (default) or configure your preferred DB in `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

4. Run migrations and seeders

```bash
php artisan migrate --seed
```

To seed the large test dataset (50k users) separately:

```bash
php artisan db:seed --class=LargeUserSeeder
```

5. Run the app

```bash
php artisan serve
npm run dev
```

On Windows PowerShell, if `npm run dev` or `npm run build` is blocked by script execution policy, use:

```powershell
npm.cmd run dev
npm.cmd run build
```

## Default Accounts

After seeding, the DatabaseSeeder creates a default admin and test user:

| Role | Username | Email | Password |
|---|---|---|---|
| Admin | admin | admin@example.com | password |
| User | testuser | test@example.com | password |

## Routes

- Guest: `/login`, `/register`
- Authenticated: `/dashboard`, `/profile`
- Admin: `/users`, `/roles`, `/reports`, `/system-health`, `/settings`, `/activities`, `/notifications`, `/modules/builder`, `/modules/{module}`

## Testing

Run tests with:

```bash
php artisan test
```

Verification

- `php artisan test` passes: 35 passed, 137 assertions
- `vendor/bin/pint` ... passed
- `git diff --check` passed

Run this before using it locally:

```bash
php artisan migrate
```

One note: I saw untracked generated Post artifacts in the worktree (`app/Models/Generated/Post.php`, `2026_05_25_043709_create_posts_table.php`) and left them untouched.


Useful focused test slices:

```bash
php artisan test tests/Feature/UserManagementTest.php
php artisan test tests/Feature/AuthFlowTest.php
php artisan test tests/Feature/ActivityLogTest.php
php artisan test tests/Feature/ReportsAnalyticsTest.php
php artisan test tests/Feature/SystemHealthTest.php
```

Build frontend assets with:

```bash
npm.cmd run build
```

## Notes And Maintenance

- The UI is implemented using Livewire and Blade; no separate JS SPA framework is required.
- Debugbar is installed for local development and will display runtime profiling information when enabled.
- Permissions are registered via `PermissionRegistry::syncAndRegister()` from navigation configuration and generated modules. Admin roles are kept in sync with newly introduced permissions.
- Usernames are unique, validated with ASCII alpha-dash rules, and can be used interchangeably with email on the login screen.
- Presence tracking is handled by `TrackUserActivity`; authenticated requests update `last_seen_at`, while logout updates `logged_out_at`.
- Login events are logged to `activity_logs` so Reports / Analytics can show login trends. Activity metadata may include IP address, user agent, affected record, and before/after snapshots.
- The System Health page performs safe local checks only. External vulnerability audits such as `composer audit` or `npm audit` should be run from the CLI or a queued scan workflow.
- The `LargeUserSeeder` is intentionally chunked to avoid memory spikes during mass insertion; monitor database and disk usage when running it.
- The CRUD Maker / Module Builder is documented in [docs/MODULE_BUILDER.md](docs/MODULE_BUILDER.md).

## Contributing

Contributions are welcome. Please open issues or PRs for bug fixes, features, or documentation improvements.

## License

MIT

## Author

Rodgel Dantes
