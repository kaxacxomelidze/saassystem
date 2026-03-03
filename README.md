# MOVOER (Fully PHP Backend)

This repository now runs on a **plain PHP backend** with a React frontend.

## Structure

- `php-backend/` → API backend (router, controllers, services, PDO, SQL schema)
- `frontend/` → React inbox UI demo
- `docs/` → lightweight docs pointers

## Quick start

```bash
cd php-backend
cp .env.example .env
mysql -u root -p movoer < database/schema.sql
php -S 127.0.0.1:8080 -t public
```

Then run frontend (separate folder) and it will call `http://127.0.0.1:8080/api`.

## What works now

- Auth (register/login)
- Workspaces (list/create)
- Inbox list + conversation detail
- Multi-channel provider connect endpoint
- Gmail auth URL + sync-now placeholder
- AI draft placeholder endpoint
- Super-admin user listing
