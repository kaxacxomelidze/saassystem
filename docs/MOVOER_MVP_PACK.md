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
