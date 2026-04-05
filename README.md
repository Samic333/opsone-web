# OpsOne Web Portal

**Internal Airline Operations Platform — Web Portal & API Server**

OpsOne is a secure, multi-tenant web portal and API backend for airline operations management. It serves as the central hub for document management, user administration, device approval, and content delivery to the OpsOne iPad app.

## Quick Start

```bash
# Setup database and seed demo data
php setup.php

# Start development server
php -S localhost:8080 -t public/

# Visit homepage
open http://localhost:8080/home

# Login (demo credentials)
Email: admin@airline.com
Password: demo
```

## Architecture

- **Framework:** Vanilla PHP 8.2+ (no composer dependencies required)
- **Database:** SQLite (development) / MySQL (production)
- **Auth:** Session-based (web) + Bearer token (API)
- **Multi-tenant:** Full tenant isolation via `tenant_id` columns
- **RBAC:** 14+ roles with module-level access control

## Directory Structure

```
opsone-web/
├── app/
│   ├── ApiControllers/    # API endpoints (JSON responses)
│   ├── Controllers/       # Web controllers (HTML views)
│   ├── Helpers/           # Utility functions
│   ├── Middleware/         # Auth, RBAC, install access
│   └── Models/            # Database models with tenant isolation
├── config/
│   ├── app.php            # Application configuration
│   ├── branding.php       # Product name & branding (configurable)
│   ├── database.php       # Database connection
│   └── routes.php         # All route definitions
├── database/
│   ├── migrations/        # SQL schema files
│   └── seeders/           # Demo data seeder
├── public/
│   ├── index.php          # Front controller
│   └── css/               # Stylesheets (app.css + public.css)
├── storage/
│   ├── builds/            # Enterprise .ipa build storage
│   ├── uploads/           # Document uploads
│   └── logs/              # Application logs
└── views/
    ├── layouts/           # app.php (admin) + public.php (marketing)
    ├── public/            # Public website pages
    ├── install/           # Protected install page
    ├── notices/           # Notice CRUD views
    ├── dashboard/         # Role-based dashboards
    └── ...                # Other admin views
```

## Key Features

### Public Website
- Marketing homepage with product overview
- Features, How It Works, FAQ, About, Support pages
- Privacy Policy and Terms of Use
- Responsive design with dark aviation theme

### Admin Portal
- User management with role assignment
- Document upload with category tagging and version control
- Device registration approval/revocation
- Notices & bulletins management
- Audit logging
- Multi-tenant architecture

### API Endpoints
- `POST /api/auth/login` — Authenticate and receive bearer token
- `GET /api/files` — List files for authenticated user's role
- `GET /api/sync/manifest` — Full sync manifest with files and notices
- `GET /api/notices` — Published notices for tenant
- `GET /api/app/version` — Latest app build info
- See `config/routes.php` for complete list

### Enterprise Install
- Protected install page (requires authentication)
- manifest.plist generation for OTA install
- Build version tracking
- Install access logging
- Step-by-step trust flow instructions

## Branding

All branding is controlled via `config/branding.php`. Change the product name there and it propagates to all templates, email footers, and API responses.

## Environment Variables

See `.env.example` for all configurable options. Key settings:
- `APP_MODE` — `multi_tenant` or `single_tenant`
- `DB_DRIVER` — `sqlite` or `mysql`
- `APP_URL` — Base URL for manifest and download links

## Production Deployment

1. Set `DB_DRIVER=mysql` and configure MySQL credentials
2. Set `APP_ENV=production` and `APP_DEBUG=false`
3. Set `APP_URL` to your production domain
4. Run `php setup.php` to initialize the database
5. Configure web server to point to `public/` directory
6. Ensure `storage/` is writable by the web server

## License

Internal use only. Not for public distribution.
