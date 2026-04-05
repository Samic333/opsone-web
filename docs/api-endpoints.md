# OpsOne API Endpoints Reference

## Authentication

### POST /api/auth/login
Authenticate a user and receive a bearer token.

**Request Body:**
```json
{
    "email": "pilot@airline.com",
    "password": "your_password",
    "device_name": "iPad Pro"
}
```

**Response:**
```json
{
    "token": "abc123...",
    "user": { "id": 1, "name": "...", "email": "...", "roles": [...] },
    "tenant": { "id": 1, "name": "Gulf Wings Aviation" }
}
```

### POST /api/auth/logout
Invalidate the current bearer token.

**Headers:** `Authorization: Bearer <token>`

---

## Devices

### POST /api/devices/register
Register a new iPad device.

**Headers:** `Authorization: Bearer <token>`
**Request Body:**
```json
{
    "device_model": "iPad Pro 12.9",
    "device_platform": "iPadOS",
    "os_version": "17.2",
    "app_version": "1.0.0"
}
```

### GET /api/devices/status
Check current device approval status.

**Headers:** `Authorization: Bearer <token>`

---

## Files

### GET /api/files
List all published files the authenticated user has access to.

**Headers:** `Authorization: Bearer <token>`
**Response:** Array of files with title, category, version, MIME type, size.

### GET /api/files/download/{id}
Download a specific file by ID.

**Headers:** `Authorization: Bearer <token>`
**Response:** File binary stream.

---

## Sync

### GET /api/sync/manifest
Full sync manifest with files and notices.

**Headers:** `Authorization: Bearer <token>`
**Response:**
```json
{
    "files": [{ "id": 1, "title": "...", "category": "...", "version": "...", "updated_at": "..." }],
    "notices": [{ "id": 1, "title": "...", "priority": "...", "published_at": "..." }],
    "file_count": 12,
    "notice_count": 3,
    "generated_at": "2024-01-01 12:00:00"
}
```

### POST /api/sync/heartbeat
Report sync status from iPad app.

**Headers:** `Authorization: Bearer <token>`

---

## Notices

### GET /api/notices
Get published notices for the authenticated user's tenant.

**Headers:** `Authorization: Bearer <token>`
**Response:**
```json
{
    "notices": [
        { "id": 1, "title": "...", "body": "...", "priority": "normal|urgent|critical", "category": "...", "author": "...", "published_at": "..." }
    ]
}
```

---

## App Info

### GET /api/app/version
Get latest app build version info.

**Response:**
```json
{
    "product_name": "OpsOne",
    "latest_version": "1.0.0",
    "build_number": "1",
    "min_os_version": "16.0",
    "update_available": false
}
```

### GET /api/app/build
Get detailed latest build information.

---

## User

### GET /api/user/profile
Get authenticated user's profile.

**Headers:** `Authorization: Bearer <token>`
