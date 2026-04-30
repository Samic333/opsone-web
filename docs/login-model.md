# OpsOne — Login Model

> **Status:** Production reference. Implemented across Phase B (slug column, tenant-scoped routes, controller methods) and Phase H (visual polish, slug auto-generation, this doc).
>
> **Audience:** Anyone touching auth, tenant onboarding, or the login UI.
>
> **Don't break:** the global `/login` route, the iPad `POST /api/auth/login` API contract, the `APP_DEBUG`-gated demo quick-picker.

---

## North star

A visitor landing on `acentoza.com` should never see a generic *"Airline Login"* button as the headline action. Login is a quiet support function for **already-onboarded airlines**, not a public sign-up surface. Every onboarded airline gets a **branded, tenant-scoped portal** at `/airline/{slug}/login`. The platform side has no visible login at all — platform staff use the global `/login` and resolve to platform context via their `tenant_id IS NULL` user record.

Three concentric surfaces, each with one job:

```
┌─────────────────────────────────────────────────────────────────────┐
│ 1. Public marketing site            (no login as primary action)    │
│    /, /home, /features, /pricing, /contact, …                       │
│    Primary CTAs: Request Demo · Contact Sales · Request Assessment  │
│    "Client Login" only as a small text link in navbar/footer        │
└─────────────────────────────────────────────────────────────────────┘
        │
        │ tenant URL shared by the airline (e.g. on a per-airline microsite,
        │ in ops emails, on iPad device handoff cards)
        ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 2. Tenant-scoped airline portal     (branded, opinionated)          │
│    /airline/{slug}/login                                            │
│    • Shows airline logo + display_name as H1                        │
│    • "AIRLINE OPERATIONS PORTAL" eyebrow                            │
│    • Email + password, "Forgot your password?" link                 │
│    • "Powered by OpsOne" footnote at bottom of card                 │
│    • Tenant-match enforcement on the POST handler                   │
└─────────────────────────────────────────────────────────────────────┘
        │
        │ fallback for crew who don't know their airline's slug
        ▼
┌─────────────────────────────────────────────────────────────────────┐
│ 3. Global fallback /login           (email-first → resolves tenant) │
│    • Same view template, no airline branding header                 │
│    • Demo quick-picker ONLY when APP_DEBUG=true (dev/local/dev env) │
│    • Quick-picker is amber-bannered as INTERNAL DEMO · DEV ONLY     │
└─────────────────────────────────────────────────────────────────────┘

iPad app continues to use POST /api/auth/login (global). Tenant is derived
from the user row after authentication. No change required to CrewAssist.
```

---

## Routes (live in `config/routes.php`)

| Method | Path | Handler | Notes |
|---|---|---|---|
| `GET` | `/login` | `AuthController::showLogin` | Global fallback. Renders the login view with `$tenant=null`. |
| `POST` | `/login` | `AuthController::login` | Tenant resolved from email after password verify. |
| `GET` | `/airline/{slug}/login` | `AuthController::showTenantLogin` | Looks up tenant by slug. **404** if not found / inactive. |
| `POST` | `/airline/{slug}/login` | `AuthController::tenantLogin` | Sets `_tenant_login_lock` then delegates to `login()`. |
| `GET` | `/forgot-password` | `PasswordResetController::showRequest` | Branded password-reset request page. |
| `POST` | `/forgot-password` | `PasswordResetController::submitRequest` | Always returns "if on file you'll get a link" regardless of existence. |
| `GET` | `/reset-password` | `PasswordResetController::showReset` | Token-gated reset form. |
| `POST` | `/reset-password` | `PasswordResetController::submitReset` | Final reset action. |
| `GET` | `/2fa/challenge` | `TwoFactorController::showChallenge` | Post-password 2FA gate when `user_2fa.enabled_at IS NOT NULL`. |
| `POST` | `/api/auth/login` | `AuthApiController::login` | iPad CrewAssist endpoint — global, JSON, returns bearer token + user + tenant_id. |
| `GET` | `/logout` | `AuthController::logout` | Destroys session, redirects to `/login`. |

The global `/login` is **never removed**. It's the safety net for crew who don't know their airline's URL slug, and it's the path platform staff use (their `tenant_id IS NULL` record can't sign in via `/airline/{slug}/login`).

---

## Tenant-match enforcement (security contract)

Implemented in [`AuthController::login()`](../app/Controllers/AuthController.php). The contract:

| Path | Caller's `tenant_id` | Outcome |
|---|---|---|
| `/airline/A/login` | `tenant_id = A` | ✅ Allowed → `/dashboard` |
| `/airline/A/login` | `tenant_id = B` | ❌ *"This account does not belong to this airline."* — redirected to `/airline/A/login` |
| `/airline/A/login` | `tenant_id = NULL` (platform user) | ❌ Same rejection. Platform users can only use global `/login`. |
| `/airline/A/login` | (slug `A` doesn't exist) | ❌ **404** — does NOT reveal whether other slugs exist. |
| `/login` | any | ✅ Tenant resolved from user row after password verify. |

**Identical-shape error messages** for unknown email and wrong password (`"Invalid email or password."`). This prevents user enumeration. Don't tighten this to `"User not found"` vs `"Wrong password"`.

**Rate limiting** is in place per IP+email (5-attempt rolling 5-min window with exponential lockout). Same for tenant URLs because they all delegate to `login()`.

---

## Slug auto-generation

When a platform admin creates a new tenant via `TenantController::store()`, [`Tenant::generateUniqueSlug($name)`](../app/Models/Tenant.php) is called and the result is written to `tenants.slug` via a separate `UPDATE` statement (wrapped in `try/catch` so a missing column never blocks onboarding).

Rules:
- Lowercased
- `&` → `and`
- Anything not `a-z0-9` → `-` (collapsed)
- Trimmed of leading/trailing `-`
- Empty → `airline`
- Conflict → append `-2`, `-3`, … (extreme fallback: 6-char hex)

Examples:
- *"748 Air Services"* → `748-air-services`
- *"Skylink Aviation Ltd."* → `skylink-aviation-ltd`
- *"Gulf Wings & Charter"* → `gulf-wings-and-charter`
- Two airlines named *"Sky Air"* → `sky-air`, `sky-air-2`

Existing tenants without a slug are backfilled by the same rules during migration 048's `UPDATE`.

---

## Demo quick-picker (dev only)

The demo block on `/login` is rendered iff:

```php
APP_ENV ∈ {development, local, dev}
   AND
APP_DEBUG = "true"
```

In **production** (`APP_ENV=production` or `APP_DEBUG=false`) the entire block is **not in the rendered DOM** — there's no class to toggle, no display:none. It's a full conditional.

The block carries an **amber-bordered banner** reading:

> ⚠ INTERNAL DEMO · DEVELOPMENT TESTING ONLY  
> Hidden in production. Shared seed password used for local QA only — never used by real airline accounts.  
> Password: `DemoOps2026!`

This is a **screenshare-safe** label — anyone reviewing a dev build over a call immediately reads "this is internal" and won't paste credentials into an airline buyer's lap.

**Demo accounts** (28 total) are seeded by `database/seeders/demo_seed.php` with the shared password `DemoOps2026!`. They use the `demo.*@acentoza.com` email pattern. **Never** seed real airline emails or passwords. **Never** copy demo seed data into production.

---

## Production rollout (when next pushing)

The slug column is required for `/airline/{slug}/login` to do anything other than 404. Production MySQL has not yet run migration 048.

**One-time on production**:

```bash
# From cPanel terminal:
cd ~/acentoza.com

# Apply the migration
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
    < database/migrations/048_tenant_slug.sql

# Verify the column exists and existing tenants got slugs
mysql -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e \
    "SELECT id, name, slug FROM tenants;"
```

Expected output for the existing live airline:
```
+----+-------------------+-------------------+
| id | name              | slug              |
+----+-------------------+-------------------+
|  1 | 748 Air Services  | 748-air-services  |
+----+-------------------+-------------------+
```

After the migration, `/airline/748-air-services/login` becomes a working branded portal and you can hand out that URL to the airline. The global `/login` continues to work unchanged — nothing breaks for users mid-flight.

---

## Don'ts

| ❌ Don't | ✅ Why |
|---|---|
| Don't expose any public `/tenants` list endpoint | Lets attackers enumerate every airline on the platform |
| Don't echo the tenant's name in 401/404 responses | Confirms the slug exists and helps phishing |
| Don't make error messages distinguish between unknown email and wrong password | Same enumeration concern |
| Don't show the demo quick-picker on a screenshare without `APP_DEBUG=false` | Buyers will think they can paste demo creds and get in |
| Don't seed real airline accounts with the demo password | Demo block is shared-secret; real airlines must use the activation/reset flow |
| Don't remove `/login` | iPad and crew-without-slug both depend on it |
| Don't change `POST /api/auth/login` shape | iPad CrewAssist deploys are pinned to that contract |
| Don't auto-redirect from `/login` to `/airline/{slug}/login` based on email guess | That's a tenant-discovery leak — keep the routes orthogonal |

---

## What's still on the table (not in scope today)

1. **Subdomain support** — `748airservices.opsone.aero` rewrite to `/airline/748-air-services/login`. Requires the `opsone.aero` domain (April 29 task) and an Apache/LiteSpeed RewriteRule. Document it here when it lands.
2. **Per-tenant 2FA enforcement** — currently 2FA is per-user. A tenant admin should be able to require 2FA for all users in their airline. Schema change in `tenant_settings`.
3. **SSO / OAuth** for enterprise airlines — Microsoft/Google identity provider. Out of scope until the first airline asks.
4. **Branded password-reset email** — currently the reset email is OpsOne-branded; should optionally show the airline logo when the user belongs to a tenant.
