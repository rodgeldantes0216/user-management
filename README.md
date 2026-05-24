# Laravel User Management

A simple single-module Laravel application for user management with authentication, authorization, and SPA-style navigation using Livewire v4.

This project is built around one core module:

- User authentication
- Role-based authorization
- Admin-only user management CRUD

## Stack

- PHP 8.2+
- Laravel 12
- Livewire 4
- Tailwind CSS 4
- Vite 7
- SQLite by default for local development

## What This App Does

The app provides a lightweight user management system with two roles:

- `admin`
- `user`

Regular users can:

- Register an account
- Sign in
- Access the dashboard
- Sign out

Administrators can:

- Access the dashboard
- Open the user management page
- Search users
- Filter users by role
- Create users
- Edit users
- Delete users except themselves

Navigation between authenticated pages uses Livewire full-page components and `wire:navigate` for an SPA-like experience without adding a frontend SPA framework.

## Features

### Authentication

- Login page
- Registration page
- Logout flow
- Session-based authentication

### Authorization

- Role-based access using a `role` column on the `users` table
- Admin-only access to the user management screen
- Policy-based authorization via `UserPolicy`

### User Management

- Paginated user listing
- Search by name or email
- Filter by role
- Create user modal
- Edit user modal
- Delete confirmation modal

### UI

- Flux-inspired styled layout
- Responsive sidebar app shell
- Auth layout and dashboard layout
- Tailwind CSS utility styling

## Important Note About Flux UI

This repository includes a Composer repository entry for Flux UI:

`https://composer.fluxui.dev`

At the time this app was built, Composer package installation was blocked by authentication on that private repository. Because of that:

- The app uses Livewire v4
- The UI is styled in a Flux-inspired way
- The real `livewire/flux` package is not currently installed

If you have valid Flux credentials, you can install the official package later and swap the custom Blade markup to Flux components.

## Project Structure

```text
app/
├── Livewire/
│   ├── Auth/
│   │   ├── Login.php
│   │   └── Register.php
│   ├── Users/
│   │   └── Index.php
│   └── Dashboard.php
├── Models/
│   └── User.php
└── Policies/
    └── UserPolicy.php

database/
├── factories/
│   └── UserFactory.php
├── migrations/
│   └── 2026_05_24_000003_add_role_to_users_table.php
└── seeders/
    └── DatabaseSeeder.php

resources/
├── css/
│   └── app.css
└── views/
    ├── components/
    │   └── layouts/
    │       ├── app.blade.php
    │       └── auth.blade.php
    └── livewire/
        ├── auth/
        │   ├── login.blade.php
        │   └── register.blade.php
        ├── users/
        │   └── index.blade.php
        └── dashboard.blade.php

routes/
└── web.php

tests/
└── Feature/
    ├── AuthFlowTest.php
    └── UserManagementTest.php
```

## Default Accounts

After seeding the database, these accounts are available:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| User | `test@example.com` | `password` |

## Local Setup

### 1. Clone the project

```bash
git clone <your-repository-url>
cd user-management
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Install frontend dependencies

```bash
npm install
```

### 4. Configure environment

Copy `.env.example` to `.env` if needed, then generate the app key:

```bash
php artisan key:generate
```

### 5. Configure the database

By default, this project already includes `database/database.sqlite`.

Make sure your `.env` points to SQLite, for example:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

If you prefer MySQL or another database, update your `.env` accordingly.

### 6. Run migrations and seed data

```bash
php artisan migrate --seed
```

If you want a fresh reset:

```bash
php artisan migrate:fresh --seed
```

### 7. Start the app

Run the Laravel development stack:

```bash
composer run dev
```

This starts:

- Laravel local server
- Queue listener
- Laravel Pail logs
- Vite dev server

If you prefer running them separately:

```bash
php artisan serve
npm run dev
```

## Production Build

To build frontend assets for production:

```bash
npm run build
```

On Windows PowerShell with restricted script execution, use:

```powershell
npm.cmd run build
```

## Available Routes

### Guest

- `/login`
- `/register`

### Authenticated

- `/dashboard`

### Admin Only

- `/users`

### Redirects

- `/` redirects to `/dashboard`

## Authorization Rules

The app uses `UserPolicy` to protect user management actions.

| Action | Admin | User |
|---|---|---|
| View dashboard | Yes | Yes |
| View user list | Yes | No |
| Create user | Yes | No |
| Edit user | Yes | No |
| Delete other users | Yes | No |
| Delete self | No | No |

## SPA Behavior

This project is not a JavaScript SPA in the React/Vue sense. Instead, it uses Livewire full-page components with:

- `wire:navigate`
- Livewire page components
- Blade layouts for shared app shell rendering

That gives the app:

- Faster page transitions
- Persistent app-like navigation feel
- Laravel-first development without API layering

## Testing

Run the test suite with:

```bash
php artisan test
```

Current automated test coverage includes:

- User registration
- User login
- Root redirect behavior
- Admin authorization
- User CRUD flow

## Verified Commands

These commands were successfully run against this project:

```bash
php artisan test
php artisan migrate:fresh --seed
npm.cmd run build
```

## Notes for Future Improvements

Possible next steps if you want to expand the app:

- Install the real Flux UI package once Composer authentication is available
- Add password reset
- Add profile management
- Add email verification
- Add audit logs for admin actions
- Add bulk user actions
- Add role and permission management beyond `admin` and `user`

## License

This project is open-sourced under the MIT license.

## Author

Rodgel Dantes 
Fullstack Web-Software Developer
