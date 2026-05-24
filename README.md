# User Management (Laravel)

This repository contains a Laravel-based user and admin management system built with Livewire full-page components. The app includes authentication, role & permission support, notifications, settings, activity/audit logging, and an admin UI for managing users, roles, and system settings.

**Primary features**
- **Authentication:** registration, login, logout, session-based auth, and optional email verification hooks.
- **Authorization:** role-based rules stored on the `users` table plus finer-grained permissions; policy-based checks via `UserPolicy`.
- **User Management:** admin CRUD for users with search, filtering, pagination, and modals for create/edit/delete workflows.
- **Roles & Permissions:** role model and permission registry with seeding and sync support.
- **Activity / Audit Logs:** activities recorded to `activity_logs` for admin auditing.
- **Settings:** application settings persisted via a `settings` table and accessed through a `Settings` helper.
- **Notifications:** app-level notifications stored in the `app_notifications` table and surfaced in the UI.
- **Livewire UI:** SPA-like navigation using Livewire full-page components and `wire:navigate` with Blade layouts and Tailwind styling.

## Stack

- **PHP:** 8.2+
- **Framework:** Laravel 12
- **UI:** Livewire 4, Tailwind CSS
- **Build tool:** Vite

## Key files and places to look
- [app/Livewire/](app/Livewire/) — Livewire page components for auth, dashboard, users, roles, settings, notifications, activities.
- [app/Models/](app/Models/) — `User`, `Role`, `Permission`, `ActivityLog`, `Setting`, `AppNotification`.
- [app/Support/PermissionRegistry.php](app/Support/PermissionRegistry.php) — permission registration and syncing.
- [app/Support/Settings.php](app/Support/Settings.php) — settings helper for reading/writing system settings.
- [database/factories/UserFactory.php](database/factories/UserFactory.php) — user factory used by seeders and tests.
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php) — seeds initial roles, permissions, admin user and settings.
- [database/seeders/LargeUserSeeder.php](database/seeders/LargeUserSeeder.php) — helper seeder that creates 50,000 users in chunks for load testing.

## Quick start (local)

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

## Default accounts

After seeding, the DatabaseSeeder creates a default admin and test user:

| Role | Email | Password |
|---|---|---|
| Admin | admin@example.com | password |
| User | test@example.com | password |

## Routes (high level)
- Guest: `/login`, `/register`
- Authenticated: `/dashboard`
- Admin: `/users`, `/roles`, `/settings`, `/activities`, `/notifications`

## Testing

Run tests with:

```bash
php artisan test
```

## Notes & maintenance

- The UI is implemented using Livewire and Blade — no separate JS SPA framework is required.
- Debugbar is installed for local development and will display runtime profiling information when enabled.
- Permissions are registered via `PermissionRegistry::syncAndRegister()` at seed time; add new permissions to the registry to have them seeded and available.
- The `LargeUserSeeder` is intentionally chunked to avoid memory spikes during mass insertion; monitor database and disk usage when running it.

## Contributing

Contributions are welcome. Please open issues or PRs for bug fixes, features, or documentation improvements.

## License

MIT

## Author

Rodgel Dantes
