# Duty Reporting — State Model & Transition Rules

## States

| State | Meaning |
| --- | --- |
| `checked_in` | Crew has reported for duty. Default state immediately after a successful check-in. |
| `on_duty` | Alias for the active window between check-in and clock-out. Distinguishes "in the middle of a duty" from a fresh check-in; currently the writer never sets this explicitly — both `checked_in` and `on_duty` are treated identically by `STATES_OPEN`. Reserved for a future nuance (e.g. after confirmation of an assignment). |
| `checked_out` | Clock-out completed. `duration_minutes` populated. Terminal state. |
| `missed_report` | Open record whose duty start is older than `clock_out_reminder_minutes + 6h`. Set by `DutyReportingService::markOverdue()` (scheduled task hook). |
| `exception_pending_review` | Check-in entered the exception flow and tenant policy requires manager approval. An associated `duty_exceptions` row exists with `status = 'pending'`. |
| `exception_approved` | Manager approved the exception. Record is considered valid duty. |
| `exception_rejected` | Manager rejected the exception. Record stays on file but is not counted as valid duty. |

### Constants

Defined on `DutyReport`:

```
STATE_CHECKED_IN       = 'checked_in'
STATE_ON_DUTY          = 'on_duty'
STATE_CHECKED_OUT      = 'checked_out'
STATE_MISSED_REPORT    = 'missed_report'
STATE_EXCEPTION_PENDING  = 'exception_pending_review'
STATE_EXCEPTION_APPROVED = 'exception_approved'
STATE_EXCEPTION_REJECTED = 'exception_rejected'

STATES_OPEN = [STATE_CHECKED_IN, STATE_ON_DUTY, STATE_EXCEPTION_PENDING]
```

The same set of seven states is defined on the iPad in `DutyReportingState` (Swift enum).

## Transitions

```
           Report for Duty
             ↓
  (exception path? — see decision tree below)
             ↓                                             ↓
     checked_in ───────────── Clock Out ────────────→ checked_out
           │                                                │
     (> clock_out_reminder + 6h)                             │
           ↓                                                │
     missed_report                                          │
                                                            │
  exception_pending_review ──(review)──→ exception_approved │
                                  │                          │
                                  └────→ exception_rejected  │
                                                            │
  (admin correction: any state → any state via adminCorrect)┘
```

**Clock-out does NOT override an exception_pending record** — if the crew clocks out before a manager reviews, the record stays in its exception branch with the clock-out fields populated. Review can still proceed.

## Check-in decision tree (where an exception is triggered)

Order matters. The first match wins.

1. **Duplicate** — user already has an open (`checked_in / on_duty / exception_pending_review`) record for the tenant. Return 409 `already_on_duty`. No new row.
2. **Module disabled** — `duty_reporting_settings.enabled = 0`. Return 403 `module_disabled`.
3. **`gps_unavailable` flag** from client → exception.
4. **`offline_queue` flag** from client → exception.
5. **Geofence required AND outside** — `settings.geofence_required = 1` and no base match within radius → exception.
6. **Outstation** — lat/lng provided, no base match, `settings.allow_outstation = 1` → exception.
7. **Explicit reason code** supplied by client → exception.
8. Otherwise → normal check-in.

When an exception is triggered:

- If `settings.exception_approval_required = 1` → state = `exception_pending_review` and a `duty_exceptions` row is created with `status = pending`.
- Else → state = `checked_in`, exception row is still created with `status = pending` for traceability (managers may later review retrospectively; treated as informational).

If the reason requires a note (everything except `offline`) and the client did not provide `exception_reason_text`, the API returns 422 `exception_note_required` — no row is created.

## Method field (`check_in_method`)

| Value | Set when |
| --- | --- |
| `device` | Normal iPad check-in (default). |
| `biometric` | Client confirmed via Face ID / Touch ID on tap. |
| `manual` | Reserved for future web-side manual entry. |
| `offline_queue` | Record originated from the iPad offline queue and flushed on reconnect. |
| `admin_corrected` | Set by `DutyReport::adminCorrect()` — any web correction flips the method here so history is obvious. |

## Geofence rules

- `bases.geofence_radius_m IS NULL` → that base has no geofence. Never matches.
- `bases.latitude / longitude IS NULL` → not a candidate for matching.
- `DutyReportingService::resolveBase()` returns the closest base within its own radius. Ties broken by distance.
- If `geofence_required` is off, an outside-geofence check-in is fine; `inside_geofence` column still records the evaluation result (`0 / 1 / NULL`).

## Duration

- `duration_minutes` = floor((check_out_at_utc − check_in_at_utc) / 60). Never negative (clamped to 0).
- Computed by `DutyReport::recordCheckOut()` at clock-out time. Not updated by admin correction unless explicitly set in the correction payload.

## Who can do what

| Action | Role gate |
| --- | --- |
| Check-in / clock-out (iPad) | user's role must be in `duty_reporting_settings.allowed_roles` (default `pilot,cabin_crew,engineer`) |
| View `On Duty Now`, History, Exceptions | `airline_admin, hr, chief_pilot, head_cabin_crew, engineering_manager, base_manager, scheduler, super_admin` |
| Approve / reject exception, admin correction | `airline_admin, chief_pilot, head_cabin_crew, engineering_manager, base_manager, super_admin` |
| Edit tenant settings | `airline_admin, super_admin` |

All checks are additive on top of `AuthorizationService::requireModuleAccess('duty_reporting', 'view')` which enforces `tenant_modules.is_enabled`.
