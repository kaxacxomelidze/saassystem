# MOVOER Plain PHP Backend

This is the active backend implementation (no Laravel).

## Run

1. Copy env:

```bash
cd php-backend
cp .env.example .env
```

2. Create MySQL DB `movoer` and import schema + demo data:

```bash
mysql -u root -p movoer < database/schema.sql
```

3. Start API server:

```bash
php -S 127.0.0.1:8080 -t public
```

## Demo users

- Owner: `owner@movoer.test` / `password123`
- Super admin: `admin@movoer.test` / `AdminPass123!`

## Implemented endpoints

- `POST /api/register`
- `POST /api/login`
- `GET /api/workspaces`
- `POST /api/workspaces`
- `GET /api/inbox?status=&priority=`
- `GET /api/inbox/{id}`
- `POST /api/ai/{conversationId}/draft` (placeholder draft response)
- `GET /api/channels/providers`
- `POST /api/channels/connect`
- `GET /api/gmail/auth-url`
- `POST /api/gmail/sync-now` (placeholder sync marker)
- `GET /api/admin/users` (super admin only)

## Notes

This is a rewrite baseline. Next phase can implement full Gmail ingestion, automation rules engine, Stripe billing, and stronger JWT/session/auth middleware.
