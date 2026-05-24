# 👤 Laravel User Management

A single-module Laravel application for **user management** with full authentication and authorization — built with a modern, minimal stack.

---

## 🧱 Stack

| Layer | Technology |
|---|---|
| Backend | Laravel (latest) |
| Auth | Laravel Fortify |
| Frontend | Livewire 4 (SPA mode) |
| UI / Styling | Flux-inspired layout (Tailwind CSS) |
| Architecture | Single-module (User Management) |

---

## ✨ Features

- **Authentication**
  - Login / Logout
  - Registration
  - Password reset (email)
  - Email verification
  - Two-factor authentication (2FA) via Fortify

- **Authorization**
  - Role-based access control (Admin / User)
  - Gate & Policy definitions per action
  - Middleware-protected routes

- **User Management (Admin)**
  - List all users (paginated)
  - Create new user
  - Edit user details & roles
  - Deactivate / delete user

- **User Profile (Self)**
  - View & edit own profile
  - Change password
  - Manage 2FA settings

- **SPA Experience**
  - Livewire 4 navigate (SPA-like routing, no full page reloads)
  - Flux-inspired layout: sidebar nav, top bar, clean content area

---

## 📁 Project Structure

```
app/
├── Http/
│   └── Controllers/        # Minimal — logic lives in Livewire components
├── Livewire/
│   └── Users/
│       ├── Index.php        # User list with search & pagination
│       ├── Create.php       # Create user form
│       ├── Edit.php         # Edit user form
│       └── Profile.php      # Self-service profile management
├── Models/
│   └── User.php
├── Policies/
│   └── UserPolicy.php
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php    # Flux-inspired shell layout
│   └── livewire/
│       └── users/
│           ├── index.blade.php
│           ├── create.blade.php
│           ├── edit.blade.php
│           └── profile.blade.php
routes/
└── web.php
```

---

## ⚙️ Installation

```bash
# 1. Clone the repo
git clone https://github.com/your-org/user-management.git
cd user-management

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Copy and configure environment
cp .env.example .env
php artisan key:generate

# 5. Configure your database in .env, then migrate
php artisan migrate --seed

# 6. Build frontend assets
npm run dev

# 7. Serve the app
php artisan serve
```

---

## 🔐 Fortify Configuration

Fortify features are enabled in `config/fortify.php`:

```php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

---

## 🎨 UI / Layout

The layout follows a **Flux-inspired** design pattern:

- Fixed **sidebar** with navigation links (visible to authenticated users)
- **Top bar** with user avatar, name, and logout
- Clean **content area** with card-based UI components
- Fully responsive (mobile-friendly collapse)
- Powered by **Tailwind CSS**

---

## 🔒 Roles & Permissions

| Action | Admin | User |
|---|---|---|
| View user list | ✅ | ❌ |
| Create user | ✅ | ❌ |
| Edit any user | ✅ | ❌ |
| Delete user | ✅ | ❌ |
| Edit own profile | ✅ | ✅ |
| Change own password | ✅ | ✅ |

Roles are managed via a `role` column on the `users` table (`admin` / `user`).

---

## 🧪 Seeded Accounts

After running `php artisan migrate --seed`:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| User | `user@example.com` | `password` |

---

## 📦 Key Dependencies

```json
{
  "laravel/framework": "^11.0",
  "laravel/fortify": "^1.x",
  "livewire/livewire": "^4.0"
}
```

---

## 📄 License

MIT © Your Name / Your Organization