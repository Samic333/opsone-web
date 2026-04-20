# Retention Policy Framework

## 1. Why Retention Matters in Aviation

Aviation operations data carries strict legal, regulatory, and safety obligations. Airlines operating under EASA, ICAO, FAA, or equivalent national authorities are legally required to retain certain records for defined minimum periods. Failure to comply can result in:

- Loss of Air Operator Certificate (AOC)
- Regulatory enforcement action during audits
- Inability to defend liability claims
- Loss of airworthiness evidence

OpsOne implements a **tenant-configurable, module-scoped retention system** that allows each airline to meet or exceed regulatory minimums while managing storage costs and data hygiene.

---

## 2. Regulatory Retention Minimums

The following minimums are drawn from ICAO Annex 6 (Flight Operations), ICAO Annex 13 (Accident Investigation), EASA Part-ORO, and EASA Part-CAT. Airlines are ultimately responsible for verifying compliance with their national civil aviation authority.

| Data Type | Regulatory Basis | Minimum Retention |
|---|---|---|
| Flight crew records | ICAO Annex 6, EASA ORO.MLR.115 | 5 years after last entry |
| Safety occurrence reports | ICAO Annex 13, EU Reg 376/2014 | 7 years minimum |
| Maintenance records (CRS) | EASA Part-145, Part-M | 3 years after return to service |
| Flight time records | EASA Part-ORO, FAR Part 121 | 12 months (operational) |
| FDR / FDM data | ICAO Annex 6, EASA SPA.HOFO.155 | 60 days FDR; FDM programme data indefinite |
| Operations manual amendments | EASA ORO.MLR.100 | Until superseded + 2 years |
| Risk assessments / SMS documents | ICAO Doc 9859 SMS Manual | 5 years minimum |
| Audit/investigation logs | Internal / national CAA | 5 years recommended |

---

## 3. Platform Default Retention Windows

These defaults apply to every tenant unless overridden. They are defined in `RetentionService::DEFAULTS` in `app/Services/RetentionService.php`.

| Module Key | Table(s) | Default Days | Rationale |
|---|---|---|---|
| `safety_reports` | `safety_reports`, `safety_report_updates` | **2555 days (7 years)** | ICAO Annex 13 minimum |
| `fdm_uploads` | `fdm_uploads` | **1825 days (5 years)** | ICAO Annex 6 / EASA guidance |
| `fdm_events` | `fdm_events` | **1825 days (5 years)** | Same as uploads |
| `roster_changes` | `roster_changes` | **730 days (2 years)** | Duty time audit trail |
| `notices` | `notices` | **365 days (1 year)** | Regulatory notice compliance window |
| `audit_log` | `audit_log` | **1095 days (3 years)** | Internal accountability |
| `notifications` | `notifications` | **90 days (90 days)** | Inbox hygiene — non-regulatory |

> **Safety floor:** `RetentionService::purge()` enforces a hard minimum of 30 days regardless of what a tenant configures. No data can be purged faster than 30 days from creation.

---

## 4. Tenant-Configurable Overrides

Each airline can extend (but never shorten below regulatory minimums) the retention window for any module.

### Database schema

Overrides are stored in `tenant_retention_policies` (created by migration `019_phase0_safety_reports_mysql.sql`, Section C):

```sql
CREATE TABLE tenant_retention_policies (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    module      VARCHAR(100) NOT NULL,
    retain_days INT UNSIGNED NOT NULL,
    note        VARCHAR(255) DEFAULT NULL,
    updated_by  INT UNSIGNED DEFAULT NULL,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_retention (tenant_id, module),
    FOREIGN KEY (tenant_id)  REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### API for reading effective policy

```php
$policy = RetentionService::getPolicy($tenantId, 'safety_reports');
// Returns:
// [
//   'module'      => 'safety_reports',
//   'retain_days' => 2555,
//   'source'      => 'default',  // or 'tenant'
//   'note'        => null,
// ]
```

The `source` field tells callers whether the value came from the tenant's custom config or the platform default.

---

## 5. Purge Execution

### Running a purge

```php
// Single module
$deleted = RetentionService::purge($tenantId, 'notifications');

// All modules for a tenant
$results = RetentionService::purgeAll($tenantId);
// Returns: ['safety_reports' => 0, 'notices' => 14, 'notifications' => 322, ...]
```

### Purge safety rules

1. **30-day safety floor** — if `retain_days < 30`, purge is skipped and an error is logged.
2. **Tenant-scoped deletes only** — every `DELETE` is filtered by `tenant_id`. Cross-tenant purges are architecturally impossible.
3. **All purge operations are logged** — `error_log()` records: tenant, module, rows deleted, and window used.
4. **No cascade deletes without review** — the purge only touches the primary module table. Child records (e.g., `safety_report_updates`) should be purged as a secondary step or handled by ON DELETE CASCADE FK.

---

## 6. Recommended Purge Schedule

Purge jobs should run **nightly at low-traffic hours** (e.g., 02:00 UTC). Implementation options:

| Option | How |
|---|---|
| Cron on web server | `php /path/to/scripts/purge_job.php` |
| Database event | MySQL `EVENT` with `DO CALL purge_procedure()` |
| Future: background worker | Queue-based job dispatcher (Phase 8+) |

**Recommended script pattern:**

```php
// scripts/purge_job.php
require_once __DIR__ . '/../app/bootstrap.php';

$tenants = Database::fetchAll('SELECT id FROM tenants WHERE status = "active"');
foreach ($tenants as $tenant) {
    $results = RetentionService::purgeAll((int)$tenant['id']);
    // Log results to audit_log: action = 'retention.purge', details = json_encode($results)
    AuditService::log('system', 0, 0, 'retention.purge', 'system', 0, $results);
}
```

---

## 7. Archive-Before-Delete (Future)

The current `purge()` implementation performs **hard deletes**. A future enhancement (Phase 8 — Compliance & Reporting) will add:

- `RetentionService::archive(int $tenantId, string $module): int` — exports expired records to `archived_*` tables or cold storage before deletion
- Configurable per-tenant: `archive_before_delete: true/false` in `tenant_retention_policies`
- Pending purge review queue: records flagged for deletion await a 7-day review window before final purge
- Export format: JSON or CSV audit package for CAA submission

---

## 8. Audit Log for Cleanup Actions

Every purge execution creates an entry in `audit_log`:

| Field | Value |
|---|---|
| `user_id` | `0` (system) |
| `action` | `retention.purge` |
| `entity` | Module name (e.g., `safety_reports`) |
| `details` | JSON: `{ "deleted": 14, "retain_days": 2555, "source": "default" }` |
| `tenant_id` | Executing tenant |

This ensures a full audit trail of when data was deleted, how much, and under what policy.

---

## 9. Adding Retention Support to a New Module

When implementing a new module, register it in `RetentionService`:

1. Add to `RetentionService::DEFAULTS`:
   ```php
   'new_module' => 1095,  // 3 years
   ```

2. Add to `RetentionService::MODULE_TABLE_MAP`:
   ```php
   'new_module' => ['table' => 'new_module_records', 'tenant_col' => 'tenant_id', 'ts_col' => 'created_at'],
   ```

3. Document the regulatory basis in this file under Section 2.

4. If the module has child tables, add a second map entry or extend `purgeAll()` to handle cascades.
