# MOVOER MVP Pack (Improved)

This repository now ships a concrete MVP starter pack as files:

- Backend code under `backend/`
- Frontend code under `frontend/`

## Backend files included

- Migrations for users/workspaces/channels/inbox/tags/AI logs/automation
- Models: `Workspace`, `WorkspaceUser`, `Channel`, `Contact`, `Conversation`, `Message`, `Tag`, `AiLog`, `AutomationRule`
- Middleware: `RequireWorkspace`
- Services: `AutomationService`, `GmailService`
- Job + command: `SyncGmailChannelJob`, `SyncGmailCommand`
- Controllers: `WorkspaceController`, `InboxController`, `AiController`
- API routes scaffold
- Demo seeder

## Frontend files included

- `frontend/src/api.js`
- `frontend/src/App.jsx`

## Notes

This is intentionally a **starter monorepo pack** so you can copy these files into a fresh Laravel + Vite React setup quickly. It is much closer to “full code” than the previous docs-only response.


## Multi-channel platform additions

- Added `ChannelController` with provider registry and generic connect/sync endpoints for: Gmail, Facebook, Instagram, WhatsApp, Telegram, Slack, and Website channels.
- Added `SyncChannelJob` so non-Gmail channels are supported by one unified sync entrypoint while provider-specific ingestion can be implemented incrementally.
- Added `RequireSuperAdmin` middleware and `AdminController` for super-admin-only user governance APIs under `/api/admin/*`.


## XAMPP run guide

You can run this starter on XAMPP (Apache + MySQL):

- Set Laravel `.env` to MySQL (`DB_CONNECTION=mysql`, `DB_PORT=3306`, `DB_USERNAME=root`)
- Use `QUEUE_CONNECTION=database` if you are not running Redis
- Run `php artisan queue:table && php artisan migrate && php artisan db:seed`
- Start workers with `php artisan queue:work` and scheduler with `php artisan schedule:work`

Compatibility note:
- Migrations in this pack already use `json` columns for MySQL/XAMPP compatibility.
