# Duty Reporting — Tenant Settings

> One row per tenant in `duty_reporting_settings`. Primary key = `tenant_id`.
> Default row auto-seeded for every active tenant by migration 022 and lazily
> by `DutyReportingSettings::ensureRow()` on first read.

Edit via `/duty-reporting/settings` (requires `airline_admin` or `super_admin`).

## Fields

### Module access

| Field | Type | Default | Effect |
| --- | --- | --- | --- |
| `enabled` | bool | `1` | Master switch. When off, iPad hides Duty Reporting and API endpoints return 403 `module_disabled`. |
| `allowed_roles` | CSV | `pilot,cabin_crew,engineer` | User's roles must intersect this list. Enforced in `ensureModuleAndRole()` on every API call. |

> **Note:** `allowed_roles` is stored as a comma-separated string. The web form
> sends an array that is joined with `,` by `DutyReportingSettings::normalise()`.

### Geo-fence

| Field | Type | Default | Effect |
| --- | --- | --- | --- |
| `geofence_required` | bool | `0` | When `1`, a check-in outside all base radii triggers the exception flow with reason `outside_geofence`. When `0`, the check-in proceeds; `inside_geofence` still recorded on the row. |
| `allow_outstation` | bool | `1` | When `1`, lat/lng with no base match triggers an `outstation` exception (valid duty path). When `0`, it falls through — usually ending as a normal check-in unless `geofence_required` was also set. |
| `default_radius_m` | int | `500` | Fallback radius for bases that have a geo-fix but no explicit `geofence_radius_m`. Per-base override is entered on each base's edit screen. |

### Exceptions & reminders

| Field | Type | Default | Effect |
| --- | --- | --- | --- |
| `exception_approval_required` | bool | `1` | When `1`, exception check-ins land in `exception_pending_review` until a manager approves. When `0`, exception is recorded for audit but the duty proceeds as a regular `checked_in`. |
| `clock_out_reminder_minutes` | int | `840` (14h) | iPad nudges the crew to clock out after this many minutes. Also drives `markOverdue()`: a record whose check-in is older than this + 6h grace is promoted to `missed_report`. |

### Device / biometric

| Field | Type | Default | Effect |
| --- | --- | --- | --- |
| `trusted_device_required` | bool | `0` | When `1`, check-in requires the iPad's `device_id` to be present and approved in `devices` table. (API-side enforcement: future — currently the field is written on the row but not blocked.) |
| `biometric_required` | bool | `0` | When `1`, the iPad prompts for Face ID / Touch ID before submitting. `check_in_method` is then recorded as `biometric`. Enforced client-side; the server trusts the client flag. |

> Biometric data is **never** transmitted to or stored by OpsOne. The platform
> only records whether a biometric prompt succeeded on the device.

### Retention

| Field | Type | Default | Effect |
| --- | --- | --- | --- |
| `retention_days` | int | `180` (6 months) | Per-tenant override for `duty_reporting` in `RetentionService`. Minimum effective floor is **30 days** enforced inside `RetentionService::purge()` regardless of config. |

## Programmatic access

```php
$settings = DutyReportingSettings::forTenant($tenantId);
// $settings is normalised: booleans are real booleans, ints are real ints,
// allowed_roles is still a CSV string.

$allowed = DutyReportingSettings::allowedRoles($tenantId);
// array<string> of role slugs.

DutyReportingSettings::userAllowed($tenantId, $userRoleSlugs);
// bool — whether any of the user's role slugs is in the allowed list.

DutyReportingSettings::save($tenantId, $fields, $updatedBy);
// $fields is a whitelist-checked associative array. Unknown keys ignored.
// updated_at is set automatically; updated_by stored for audit.
```

Every save action writes `duty_reporting.settings.updated` to the audit log
via the web controller. Direct service calls (e.g. a future admin script)
should log their own audit entry.

## Relationship to other settings

- **`tenant_modules.is_enabled`** — platform-level toggle at the Module
  Catalogue (`/platform/modules`). If this is `0`, the tenant never sees
  Duty Reporting regardless of `duty_reporting_settings.enabled`. Platform
  super admins maintain this.
- **`tenant_retention_policies`** — tenant-level override used by
  `RetentionService::getPolicy()`. Writing `retention_days` here only sets
  the per-tenant `duty_reporting_settings.retention_days` field, which is
  informational unless a background purge job is also configured to read it.
  The shared framework uses `tenant_retention_policies(module='duty_reporting')`
  as the canonical retention source; align both fields if you diverge.
- **`bases.latitude / longitude / geofence_radius_m / timezone`** — per-base
  geofence data. Nullable: a base without lat/lng never matches, and a base
  with `geofence_radius_m = NULL` has no geofence.

## Forward-looking fields (reserved, currently informational)

- `trusted_device_required` — enforcement hook exists; full block-on-untrusted
  path will land when the trusted-device workflow is expanded.
- `biometric_required` — enforced client-side today; a future server
  challenge-response flow can tighten this.

## Validation rules (server-side)

- `default_radius_m`, `clock_out_reminder_minutes`, `retention_days` — coerced
  to non-negative integers via `DutyReportingSettings::normalise()`.
- Booleans — always stored as `0` / `1`.
- `allowed_roles` — trimmed and joined; empty result means *nobody* can use
  the module for this tenant (safer than silently defaulting).
