# Eligibility & Readiness Model

`EligibilityService::computeForUser($userId)` returns a clean readiness
signal that the roster / scheduling module will consume as a pre-check
before any assignment.

## Contract

```php
[
  'status'  => 'eligible' | 'warning' | 'blocked',
  'reasons' => [ 'Medical expired on 2026-03-11', ... ],
  'details' => [
    'missing_required' => [ ['doc_type' => 'medical', 'label' => 'Medical Certificate'] ],
    'expired'          => [ ['doc_type' => 'license', 'label' => '...', 'expiry_date' => '...', 'days' => -12] ],
    'expiring_soon'    => [ ['doc_type' => 'passport', 'level' => 'critical', 'days' => 10, ...] ],
    'pending_approval' => [ ['kind' => 'document', 'doc_type' => 'visa', 'id' => 42] ],
  ],
  'checked_at' => '2026-04-21 12:34:56',
]
```

## Rules

### BLOCKED
- `users.status` is `inactive` or `suspended`.
- Any **mandatory** required document is **missing** (not in
  `crew_documents` / `crew_profiles` / `licenses` / `qualifications`).
- Any **mandatory** required document is **expired**.
- Mandatory licence / medical / passport / visa present but past
  `expiry_date`.

### WARNING
- Any required document (mandatory or optional) has
  `days_until_expiry ≤ warning_days` but is not yet expired.
- Any compliance change request is `submitted / under_review /
  info_requested` on a sensitive target.
- Non-active status other than inactive/suspended (e.g. `on_leave`).

### ELIGIBLE
- Neither blocked nor warning criteria met.

## Resolution — where "document" lives

For each `role_required_documents` row, `resolveDocStatus()` checks
sources in this order until it finds a match:

1. `crew_documents` with `status = 'valid'` and matching `doc_type`.
2. Well-known types mapped to `crew_profiles` fields:
   - `passport` → `passport_expiry`
   - `medical` → `medical_expiry`
   - `visa` → `visa_expiry`
   - `contract` → `contract_expiry`
3. `licenses` table (latest expiry_date) for `doc_type = 'license'`.
4. `qualifications` table with best-match keyword search for types like
   `type_rating`, `type_auth`, `cabin_attestation`, `dangerous_goods`.

If none match and the requirement is `is_mandatory`, status goes BLOCKED.
If not mandatory, the detail is recorded but status stays ELIGIBLE unless
other rules trigger.

## Bulk & summary

- `EligibilityService::bulkForTenant($tenantId)` returns
  `[user_id => result]` for all active staff.
- `EligibilityService::tenantSummary($tenantId)` returns
  `['eligible' => N, 'warning' => N, 'blocked' => N, 'total' => N]`.

These are used by:

- Web: `/personnel/eligibility` overview card
- Web: `/compliance` dashboard readiness strip
- iPad: `EligibilityBadge` (via `GET /api/personnel/eligibility`)

## Roster consumption (planned)

When the roster module assigns a duty, it will call:

```php
$e = EligibilityService::computeForUser($crewUserId);
if ($e['status'] === 'blocked') {
    // hard stop — show $e['reasons'] to scheduler
}
if ($e['status'] === 'warning') {
    // soft flag — allow but display yellow indicator
}
```

The service is stateless and recomputes on demand, so it reflects the
latest approved records as of the call.

## Testing

`EligibilityService` is fully covered by the Phase 6 test scenarios:

- A — pilot can view their own profile and compliance records
- B — engineer / base manager / other staff roles supported
- E — user submits CR with upload; status becomes `pending_approval`,
      eligibility moves to `warning` if the field is sensitive
- F — reviewer approves → record mutated, CR becomes `approved`
- G — expiry_alerts rows recorded with correct levels
- H — eligibility transitions reflect the latest state
- I — management can filter `/personnel/eligibility?status=blocked`

## Alert dispatch

`ExpiryAlertService::recipientsFor($tenantId, $userId)` resolves:

- the crew user
- all active users with the `hr` role in the tenant
- `users.line_manager_id`

Dispatch (email / push) is intentionally out of scope for Phase 6. The
`expiry_alerts` ledger records *intent*; a later notification job will
actually send and flip the `sent_to_user / sent_to_hr / sent_to_manager`
bits via `ExpiryAlertModel::markSent()`.
