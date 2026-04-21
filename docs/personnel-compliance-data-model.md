# Personnel Compliance — Data Model

This document describes every table that the Personnel Compliance Record
System adds or extends. Migration file: `023_phase6_personnel_compliance.sql`
(MySQL) / `023_phase6_personnel_compliance_sqlite.sql` (SQLite dev).

Multi-tenant rule: every row has a `tenant_id` (direct or via `user_id`)
scoped to the airline. The sole exception is `role_required_documents`
where `tenant_id = NULL` represents a **system default** applicable to any
tenant that hasn't overridden it.

---

## Existing tables — extensions

### `crew_profiles` — added columns
| Column                 | Type         | Notes                                  |
|------------------------|--------------|----------------------------------------|
| `profile_photo_path`   | VARCHAR(500) | Relative to `storage/`                 |
| `address`              | VARCHAR(500) | Free-text                              |
| `visa_number`          | VARCHAR(100) |                                        |
| `visa_country`         | VARCHAR(100) |                                        |
| `visa_type`            | VARCHAR(100) |                                        |
| `visa_expiry`          | DATE         | Triggers expiry alerts                 |

### `users` — added column
| Column            | Type         | Notes                                    |
|-------------------|--------------|------------------------------------------|
| `line_manager_id` | INT UNSIGNED | FK-style link to `users.id` (nullable)   |

### `licenses` — added columns
| Column                      | Type          | Notes                      |
|-----------------------------|---------------|----------------------------|
| `status`                    | ENUM          | `valid / expired / pending_approval / pending_renewal / suspended` |
| `approved_by`               | INT UNSIGNED  | Reviewer user id           |
| `approved_at`               | TIMESTAMP     |                            |
| `file_id`                   | INT UNSIGNED  | Link to `files` (scan)     |
| `document_scan_path`        | VARCHAR(500)  | Alt: filesystem path       |
| `pending_change_request_id` | INT UNSIGNED  | Link to CR while pending   |

### `qualifications` — added columns
Same approval-metadata block as `licenses`.

---

## New tables

### `crew_documents`
The unified document vault. Replaces ad-hoc handling of scans. Every row
represents one scanned/uploaded document with a full lifecycle.

| Column                  | Type          | Notes                         |
|-------------------------|---------------|-------------------------------|
| `id`                    | PK            |                               |
| `tenant_id`             | FK tenants    |                               |
| `user_id`               | FK users      | Subject of the document       |
| `doc_type`              | VARCHAR(80)   | passport, medical, visa, license, type_rating, company_id, airside_permit, contract, certificate, … |
| `doc_category`          | VARCHAR(60)   | identification, regulatory, medical, training, contract, company |
| `doc_title`             | VARCHAR(200)  | Human label                   |
| `doc_number`            | VARCHAR(100)  |                               |
| `issuing_authority`     | VARCHAR(150)  |                               |
| `issue_date`            | DATE          |                               |
| `expiry_date`           | DATE          | Drives expiry alerts          |
| `file_path`, `file_name`, `file_mime`, `file_size` | | Stored scan |
| `status`                | ENUM          | `valid / expired / pending_approval / rejected / revoked` |
| `approved_by`           | FK users      |                               |
| `approved_at`           | TIMESTAMP     |                               |
| `rejection_reason`      | VARCHAR(500)  |                               |
| `replaces_document_id`  | self-FK       | Supersession chain            |
| `uploaded_by`           | FK users      |                               |
| `notes`                 | TEXT          |                               |
| `created_at`, `updated_at` |            |                               |

Lifecycle: `pending_approval` → (`valid` \| `rejected`); `valid` →
`expired` (derived) or `revoked` (manual). When a newer doc is approved
with `replaces_document_id = OLD.id`, OLD is set to `revoked` so the
historical record is preserved.

### `emergency_contacts`
Secondary / additional emergency contacts (the primary pair is still
stored on `crew_profiles.emergency_*` for backward compatibility).

| Column           | Type         | Notes                         |
|------------------|--------------|-------------------------------|
| `contact_name`   | VARCHAR(200) |                               |
| `relation`       | VARCHAR(100) |                               |
| `phone_primary`  | VARCHAR(40)  |                               |
| `phone_alt`      | VARCHAR(40)  |                               |
| `email`          | VARCHAR(255) |                               |
| `address`        | VARCHAR(500) |                               |
| `is_primary`     | BOOL         | 1 if mirrors crew_profiles    |
| `sort_order`     | INT          |                               |

### `role_required_documents`
The role → required-document-type catalogue.

| Column            | Type          | Notes                              |
|-------------------|---------------|------------------------------------|
| `tenant_id`       | FK (nullable) | NULL = system default              |
| `role_slug`       | VARCHAR(50)   | e.g. `pilot`, `engineer`, `base_manager` |
| `doc_type`        | VARCHAR(80)   | Matches `crew_documents.doc_type`  |
| `doc_label`       | VARCHAR(150)  | Human label                        |
| `is_mandatory`    | BOOL          | Drives blocked/warning eligibility |
| `warning_days`    | INT           | Days before expiry → warning       |
| `critical_days`   | INT           | Days before expiry → critical      |
| `description`     | VARCHAR(500)  |                                    |
| `is_active`       | BOOL          | Soft-disable                       |

Resolution order in `RoleRequiredDocumentModel::forRole`:
tenant-specific rows first; if any exist for the `(tenant, role)` pair
they replace the system defaults; otherwise fall back to `tenant_id IS NULL`
defaults.

Seeded defaults cover: pilot, cabin_crew, engineer, base_manager, scheduler,
document_control, hr, airline_admin.

### `compliance_change_requests`
Approval workflow entity.

| Column                  | Type         | Notes                               |
|-------------------------|--------------|-------------------------------------|
| `tenant_id`             | FK tenants   |                                     |
| `user_id`               | FK users     | Target of the change                |
| `requester_user_id`     | FK users     | Usually `= user_id` (self-service)  |
| `target_entity`         | ENUM         | `profile / license / qualification / document / emergency_contact / assignment` |
| `target_id`             | INT          | Existing row id for updates; NULL for creates |
| `change_type`           | ENUM         | `create / update / delete / replace`|
| `payload`               | TEXT (JSON)  | Proposed values                     |
| `supporting_file_id`    | FK files     | Optional                            |
| `supporting_document_id`| FK crew_documents | For document submissions       |
| `status`                | ENUM         | `submitted / under_review / approved / rejected / info_requested / withdrawn` |
| `reviewer_user_id`      | FK users     |                                     |
| `reviewer_notes`        | TEXT         |                                     |
| `submitted_at`, `reviewed_at`, `created_at`, `updated_at` | | |

### `expiry_alerts`
Notification ledger. Deduped by `(tenant, user, entity_type, entity_id, alert_level)`.

| Column            | Type      | Notes                                  |
|-------------------|-----------|----------------------------------------|
| `entity_type`     | VARCHAR   | `license / medical / passport / visa / document / qualification / contract` |
| `entity_id`       | INT       | Points into the relevant table         |
| `expiry_date`     | DATE      | Denormalised from source               |
| `alert_level`     | ENUM      | `warning / critical / expired`         |
| `sent_to_user`, `sent_to_hr`, `sent_to_manager` | BOOL | |
| `last_sent_at`    | TIMESTAMP |                                        |
| `cleared_at`      | TIMESTAMP | Set when replacement is approved       |

`ExpiryAlertService::scanTenant()` is idempotent and safe to run multiple
times a day. When a new valid document supersedes an expired one, the
matching `expiry_alerts` row should be `cleared_at`-stamped (planned for
integration with the approval flow).
