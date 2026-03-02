# MOVOER MVP Monorepo Starter

This repository now includes an **actual file-based MVP starter** (not only docs) for a MOVOER-style product:

- Multi-tenant workspaces + roles
- Unified inbox data model
- AI draft endpoint
- Gmail sync job scaffolding
- Automation engine
- Multi-channel connector registry (Gmail, Facebook, Instagram, WhatsApp, Telegram, Slack, Website)
- Super-admin-only API controls for users and workspace roles
- React 3-panel inbox UI starter (login, filters, Gmail connect/sync, AI draft)

## Project layout

- `backend/` → Laravel-oriented starter files (migrations, models, middleware, services, controllers, routes, seeders)
- `frontend/` → React starter inbox UI (`src/App.jsx`, `src/api.js`)
- `docs/MOVOER_MVP_PACK.md` → detailed implementation guide

## Quick start (fresh Laravel app)

Because this repo provides the code pack files, create a Laravel app and copy `backend/` contents into it:

```bash
composer create-project laravel/laravel movoer
cd movoer
composer require laravel/sanctum stripe/stripe-php filament/filament:"^3.0"
```

Then copy files from this repository's `backend/` into your Laravel project and run:

Admin control APIs are protected with `super_admin` middleware (`/api/admin/*`) so only your super-admin account can manage users/roles globally.

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

## Run with XAMPP (Windows-friendly)

Yes — you can run this with XAMPP.

1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Create database `movoer` in phpMyAdmin.
3. In Laravel `.env`, use MySQL + database queue:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=movoer
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
```

4. Create queue table and migrate:

```bash
php artisan queue:table
php artisan migrate
php artisan db:seed
```

5. Run app + worker + scheduler:

```bash
php artisan serve
php artisan queue:work
php artisan schedule:work
```

> Note: migrations are already MySQL-compatible (`json` columns), so XAMPP setup is now straightforward.

For frontend:

```bash
npm create vite@latest movoer-ui -- --template react
cd movoer-ui
npm i axios
```

Then replace `src/App.jsx` and `src/api.js` with files from `frontend/src/`.
