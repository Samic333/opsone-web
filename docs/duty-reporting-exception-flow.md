# Duty Reporting — Exception Flow

Real-world operational cases where a "clean" check-in isn't possible.
The goal is to record duty reliably without breaking operational reality.

## Reason codes (`duty_exceptions.reason_code`)

| Code | Human label | When it fires |
| --- | --- | --- |
| `outside_geofence` | Outside geo-fence | Tenant requires geofence but location lands outside all base radii. |
| `gps_unavailable` | GPS unavailable | Client (iPad) reports it couldn't obtain a fix. |
| `offline` | No connectivity | Check-in originated from the iPad offline queue. |
| `forgot_clock_out` | Forgot to clock out | Previous duty never closed — this is the path for cleaning up a missed clock-out. |
| `wrong_base_detected` | Wrong base detected | Geofence matched a different base than expected — crew can submit a correction path. |
| `duplicate_attempt` | Duplicate check-in attempt | Reserved for client-side flag when the user retries after an unclear sync state. |
| `outstation` | Reporting from out-station | No base match but lat/lng provided; tenant allows outstation reporting. |
| `manual_correction` | Manual correction | Admin-driven path on the web for after-the-fact fixes. |
| `other` | Other | Catch-all; requires a note. |

## Client-side flow (iPad)

```
User taps Report for Duty
  ↓
LocationManager attempts fix (WhenInUse)
  ↓
If fix ok  → POST /api/duty-reporting/check-in with lat/lng
If no fix → POST /api/duty-reporting/check-in with gps_unavailable=true + optional note
If offline → queue the same payload in SyncStore; flush later with offline_queue=true
  ↓
API response → {success, duty_report, inside_geofence, matched_base, exception}
  ↓
If exception present and state = exception_pending_review:
  - UI shows "Pending manager review" banner
  - state card stays yellow
  - crew can still clock out; record stays in exception branch
```

The iPad prompts for a reason note inline whenever the server tells it
`requires_note` is true. The same API call is re-sent with
`exception_reason_text` set — no separate "submit exception" endpoint.

## Server decision tree

See `duty-reporting-states-and-rules.md` §"Check-in decision tree". The service
method is `DutyReportingService::performCheckIn()`. It returns a structured
result array; the controller then audits + notifies.

## Review (web)

1. **Manager opens `/duty-reporting/exceptions`** (or the sidebar badge deep-links from
   any page when `duty_exceptions.status = 'pending'` count > 0).
2. Tabs: Pending / Approved / Rejected / All. Default = Pending.
3. Click **Open** on a row → detail page shows the full `duty_reports` record +
   the exception list + admin correction form.
4. **Approve** / **Reject** buttons POST to
   `/duty-reporting/exception/{id}/approve` or `.../reject`. Body may include
   `review_notes`.
5. Server: `DutyReportingService::applyExceptionReview()` writes
   `duty_exceptions.status / reviewed_by / reviewed_at / review_notes` and
   flips `duty_reports.state` to `exception_approved` / `exception_rejected`.
6. `AuditService::log('duty_reporting.exception.approved'|'.rejected')` writes
   the full audit row.
7. `NotificationService::notifyUser(crew_id, ...)` posts an inbox entry for the
   affected crew member with a deep-link to the record.

## Admin correction

Separate from exception review. Use when:

- Check-in time was wrong (clock drift, manual entry error).
- Clock-out time needs to be set retrospectively after `forgot_clock_out`.
- State needs to be moved (e.g. `missed_report` → `checked_out` once the real end time is known).

Form: on the detail page. Requires a **correction note** (enforced server-side).
Writes `check_in_method = 'admin_corrected'` and appends the note to the
record's `notes` field. Audit: `duty_reporting.record.corrected` including
the fields that changed.

## What exception flow does NOT do

- It does not re-run geofence evaluation after a manager approves. `inside_geofence` stays as recorded at check-in.
- It does not block a clock-out on an exception-pending record. The record closes but stays in the exception branch pending review.
- It does not automatically notify "everyone" — only users with roles `airline_admin` and `chief_pilot` are notified on submission. Add more via `NotificationService::notifyTenant` calls if policy changes.

## Operational guidance for managers

- **Approve** when the exception reason is legitimate and the duty was truly performed. This counts the record as valid duty for downstream consumers (per diem, rostering stats).
- **Reject** when the reason doesn't hold up (e.g. crew was not actually on duty, or record is spurious). The record remains in the database with `exception_rejected` — it is not deleted, so the audit trail stays intact.
- **Correct** when the times are wrong but the duty itself is real. Always add a note explaining the correction.

The complete audit trail (who submitted, who reviewed, who corrected, with timestamps and notes) is queryable via `/audit-log` filtered by `entity_type = 'duty_reports'` or `entity_type = 'duty_exceptions'`.
