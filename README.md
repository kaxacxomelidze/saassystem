# MOVOER MVP Monorepo Starter

This repository now includes an **actual file-based MVP starter** (not only docs) for a MOVOER-style product:

- Multi-tenant workspaces + roles
- Unified inbox data model
- AI draft endpoint
- Gmail sync job scaffolding
- Automation engine
- React inbox UI starter

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

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

For frontend:

```bash
npm create vite@latest movoer-ui -- --template react
cd movoer-ui
npm i axios
```

Then replace `src/App.jsx` and `src/api.js` with files from `frontend/src/`.
