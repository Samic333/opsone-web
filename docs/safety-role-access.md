# Safety Reporting — Role Access Matrix

## Role Overview

The following roles interact with the safety reporting module. Role slugs match `roles.slug` in the database and the session-based `$_SESSION['user_roles']` array checked by `RbacMiddleware::requireRole()`.

### Safety Manager (`safety_officer`)

Owns the safety queue. Has full access to all reports within the tenant regardless of which user filed them. Can:

- View all reports in all statuses
- Change report status through the full lifecycle
- Add both public and internal thread messages
- Assign reports to safety staff members
- Create and publish safety bulletins (`safety_publications`)
- Configure `safety_module_settings` (enabled types, Just Culture statement, contact info)
- Export reports and view audit history

### Safety Staff / Investigator

Safety staff are users assigned to a report via `safety_report_assignments`. In Phase 1, safety staff access is controlled by the `safety_officer` role (no separate `safety_staff` role is defined in the Phase 0 role catalog). A dedicated `safety_staff` / `investigator` role with narrower permissions is a Phase 2 consideration.

Under the current model:
- A `safety_officer` can assign any report to any user in the tenant
- The assigned user receives an in-app notification
- The assigned user can view the report via `SafetyReportModel::forUser()` which returns reports where `assigned_to = userId`
- The assigned user cannot change status to `closed` or create publications unless they also hold `safety_officer` or `airline_admin`

### Pilot / Captain / First Officer (`pilot`)

Flight crew role. Can file flight-operations-related reports. Primarily an iPad user but may access the web portal (`web_access = 1`).

- Can submit: `general_hazard`, `flight_crew_occurrence`, `tcas`, `frat`, `hse`, `environmental`
- Can view: own submitted reports only
- Cannot view: any other user's reports, internal thread notes, final severity before closure

### Engineer / Maintenance (`engineer`)

Maintenance and engineering crew. Primary reporter for aircraft technical occurrences.

- Can submit: `general_hazard`, `maintenance_engineering`, `hse`, `environmental`
- Can view: own submitted reports only

### Cabin Crew (`cabin_crew`)

- Can submit: `general_hazard`, `hse`, `environmental`
- Can view: own submitted reports only

### Engineering Manager (`engineering_manager`)

Tenant admin role. Has web portal access. Oversight of engineering operations.

- Can submit: `general_hazard`, `maintenance_engineering`, `hse`, `environmental`
- Can view: own reports + reports filed by engineers under their supervision (Phase 2 scoping; in Phase 1 treated as crew-level visibility)

### Ground Ops (mapped to `base_manager` or `scheduler` in current role catalog)

No dedicated `ground_ops` role exists in the Phase 0 role catalog. Ground operations staff are typically assigned `base_manager` or `scheduler` roles. For safety reporting, these roles are treated as general crew:

- Can submit: `general_hazard`, `ground_ops`, `hse`, `environmental`
- Can view: own submitted reports only

### Quality Manager (mapped to `document_control` or `airline_admin`)

No dedicated `quality_manager` role exists in Phase 0. Quality reporting is accessible to `airline_admin` and, by configuration, any user granted the `quality` type in `safety_module_settings.enabled_types`. In Phase 1:

- `airline_admin` can submit all report types including `quality`
- Other roles cannot submit `quality` unless explicitly granted via a user capability override

### Airline Admin (`airline_admin`)

Full airline workspace access. Equivalent to a super admin within the tenant. Can perform all safety team actions including queue management, status changes, publications, and settings.

---

## Report Type Access Matrix

The following matrix shows submission eligibility by role. "View own" means the user can view reports they personally filed. Safety team roles (`safety_officer`, `airline_admin`) can view all reports in the tenant.

| Report Type | pilot | engineer | cabin_crew | base_manager / scheduler | document_control | safety_officer | airline_admin |
|---|---|---|---|---|---|---|---|
| General Hazard | Submit + View own | Submit + View own | Submit + View own | Submit + View own | Submit + View own | View all | View all |
| Flight Crew Occurrence | Submit + View own | View own only | View own only | View own only | View own only | View all | View all |
| Maintenance Engineering | View own only | Submit + View own | View own only | View own only | View own only | View all | View all |
| Ground Ops | View own only | View own only | View own only | Submit + View own | View own only | View all | View all |
| Quality | View own only | View own only | View own only | View own only | Submit + View own | View all | View all |
| HSE | Submit + View own | Submit + View own | Submit + View own | Submit + View own | Submit + View own | View all | View all |
| TCAS | Submit + View own | View own only | View own only | View own only | View own only | View all | View all |
| Environmental | Submit + View own | Submit + View own | Submit + View own | Submit + View own | Submit + View own | View all | View all |
| FRAT | Submit + View own | View own only | View own only | View own only | View own only | View all | View all |

**"View own only"** entries indicate that the role can see the report type in their `My Reports` list if they somehow submitted one (e.g., through a role change after submission), but cannot access the submission form for that type.

**Enforcement mechanism:** `SafetyReportModel::TYPE_ROLES` defines the allowed filer roles per type. `SafetyController::submitForm()` and `SafetyApiController::types()` filter the available types against the current user's roles before rendering. Attempting to POST a disallowed type returns a `403`.

---

## Safety Team Data Visibility

### What Reporters Can See

| Data | Visible to Reporter |
|---|---|
| Their own report content (all base fields) | Yes |
| Their own report's current status | Yes |
| Public thread messages on their report | Yes |
| Assigned investigator's name | No — shown as "Safety Team" |
| Internal (private) thread messages | No — not returned in queries |
| Other users' reports | No |
| Final assigned severity (before closure) | No — visible only after `closed` |
| Reporter identity on their own report | Yes (but they see their own name, not others') |

### What Safety Manager (`safety_officer`) Can See

| Data | Visible |
|---|---|
| All reports in tenant (all types, all statuses) | Yes |
| Reporter identity (name, employee ID) on non-anonymous reports | Yes |
| Anonymous reporter — identity field | No — shown as "Anonymous Reporter" |
| All thread messages (public + internal) | Yes |
| Assignment history from `safety_report_assignments` | Yes |
| Status change history from `safety_report_status_history` | Yes |
| `extra_fields` JSON in full | Yes |
| Audit log entries for the report | Yes (via `AuditLogController`) |

### What Assigned Staff Can See (current Phase 1 behaviour)

In Phase 1, assigned users must hold `safety_officer` or `airline_admin` to access the report detail view. Assignment grants notification and queue prominence but not a separate lower-privilege access path. A dedicated safety staff access tier is planned for Phase 2.

---

## Just Culture — Reporter Protection

### Anonymous Reporting

When `is_anonymous = 1` on a report:

1. `SafetyReportModel::allForTenant()` replaces `reporter_name` with `'Anonymous'` before returning the result set
2. `SafetyReportModel::find()` replaces `reporter_name` with `'Anonymous'` and sets `reporter_employee_id = null`
3. These substitutions occur at the model layer — no view or controller can bypass them by reading `reporter_id` directly through this method
4. `NotificationService` suppresses reporter-directed notifications (no delivery to an anonymous identity)

The `reporter_id` foreign key is retained in the database for system traceability (e.g., if the reporter later contests a finding under Just Culture protections). Access to the raw `reporter_id` is restricted to platform super admins only and requires a direct database query outside the application layer.

### Non-Anonymous Reports

Even when a report is not anonymous:

- Reporter identity (`reporter_name`, `reporter_employee_id`) is visible only to `safety_officer` and `airline_admin`
- The web portal renders reporter identity only within the `safety/view.php` template, which is gated by `RbacMiddleware::requireRole(['safety_officer', 'airline_admin', 'super_admin'])`
- The API `SafetyApiController::show()` returns reporter identity fields only when the requesting user holds a safety team role
- Crew colleagues with access to the web portal cannot browse the safety queue and have no route to view another user's report

### Publications Must Not Identify Reporters

`SafetyController::storePublication()` validates that the publication body does not contain the reporter's name before saving. If the body text matches the reporter's `users.name` value for any linked report, the controller rejects the submission with an error: `"Publication content must not include identifiable reporter information."` Safety managers must paraphrase or anonymise findings before publishing.

---

## Tenant Configuration

Airline admins (`airline_admin`) and safety managers (`safety_officer`) can configure which report types are active for their airline via `safety_module_settings.enabled_types`.

### Behaviour of Enabled/Disabled Types

| Scenario | Effect |
|---|---|
| Type is **enabled** | Appears in the iPad tile grid and web submission form type selector |
| Type is **disabled** | Hidden from submission form and iPad; existing reports of that type are unaffected and remain in the queue |
| All types disabled | The safety module home screen shows an empty state with a message directing the user to contact their Safety Manager |

`SafetyApiController::types()` reads `safety_module_settings.enabled_types` for the tenant and returns only the enabled subset. The iPad `SafetyReportsListView` renders tiles dynamically from this response rather than a hard-coded list.

### Settings Access

| Role | Can Read Settings | Can Write Settings |
|---|---|---|
| `safety_officer` | Yes | Yes |
| `airline_admin` | Yes | Yes |
| `super_admin` (platform) | Yes | Yes (via platform access) |
| All other roles | No — settings route is role-gated | No |

Settings changes are logged to `audit_logs` with action `safety_module_settings.update` by `AuditService::log()`.

---

## Module Capability Reference

The `safety_reports` module in `role_capability_templates` defines the following capabilities for Phase 1 roles:

| Role | `view` | `create` | `edit` | `submit` | `review` | `approve` | `export` | `view_audit` |
|---|---|---|---|---|---|---|---|---|
| `airline_admin` | Y | Y | — | — | Y | Y | Y | Y |
| `safety_officer` | Y | Y | Y | Y | Y | Y | Y | Y |
| `fdm_analyst` | Y | — | — | — | — | — | Y | — |
| `pilot` | Y | Y | — | Y | — | — | — | — |
| `engineer` | Y | Y | — | Y | — | — | — | — |
| `cabin_crew` | Y | Y | — | Y | — | — | — | — |
| `base_manager` | Y | Y | — | Y | — | — | — | — |
| `scheduler` | Y | Y | — | Y | — | — | — | — |
| `document_control` | Y | Y | — | Y | — | — | — | — |

`view` for crew roles is scoped to own reports only; `view` for `safety_officer` / `airline_admin` is tenant-wide. This scoping is enforced at the model query level, not the capability check level.

Per-user overrides stored in `user_capability_overrides` are evaluated first by `AuthorizationService::hasCapability()` before falling back to the role template. An individual user can be granted or revoked specific capabilities without changing their role.
