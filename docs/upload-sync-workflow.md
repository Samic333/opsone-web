# Upload-to-Sync Workflow

## Overview

Content flows from web admin portal to iPad apps through a publish-sync cycle:

```
Admin Portal (Upload) → Database (Publish) → API (Manifest) → iPad App (Download)
```

## Admin Upload Flow

1. Admin logs into web portal
2. Goes to Documents → Upload
3. Selects file (PDF, Word, image, etc.)
4. Sets:
   - **Title** — Display name
   - **Category** — Manuals, Notices, Safety, Training, etc.
   - **Version** — Revision number
   - **Role Visibility** — Which roles can see this file
5. File is stored in `storage/uploads/{tenant_id}/`
6. Record created in `files` table with `published = 0`
7. Admin toggles publish → file becomes available in sync manifest

## iPad Sync Process

1. App opens → calls `GET /api/sync/manifest`
2. Server returns manifest with:
   - List of published files (id, title, version, size, updated_at)
   - List of active notices
   - Timestamp of manifest generation
3. App compares manifest with local cache
4. Downloads only new/updated files via `GET /api/files/download/{id}`
5. Stores files locally on iPad
6. Reports sync heartbeat via `POST /api/sync/heartbeat`

## Key Tables

### files
- `id`, `tenant_id`, `title`, `file_name`, `mime_type`, `file_size`
- `category_id`, `version`, `published`, `published_at`
- `role_visibility` (JSON array of role slugs)

### notices
- `id`, `tenant_id`, `title`, `body`, `priority`, `category`
- `published`, `published_at`, `expires_at`

### sync_events
- `id`, `user_id`, `device_id`, `event_type`
- `file_count`, `bytes_transferred`, `created_at`

## Security

- All downloads require Bearer token authentication
- Files are filtered by user's role and tenant
- File paths are never exposed to the client
- Download events are logged in audit_logs
- Tenant isolation prevents cross-airline access
