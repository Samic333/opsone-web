# Quick Report vs Full Report — Design Decision

## Why Two Modes?

Operational environments — ramp, cockpit, hangar, apron — require immediate capture without long forms. A pilot on short turnaround who witnesses a runway incursion must be able to file within 60 seconds. A maintenance engineer finding a structural defect before pushback needs to lock in the finding before task pressure pulls them away.

Quick Report captures the essential 5 fields immediately. It lowers the barrier to reporting, which directly increases occurrence capture rates — the primary goal of an SMS under ICAO Doc 9859.

Full Report provides complete structured documentation for situations where time allows, where the occurrence requires detailed investigation pre-filing, or where the reporter is completing a Quick Report they filed earlier.

---

## Quick Report

### When to Use
- Immediate capture in an operational environment
- Time-critical situations where completing a full form is not practical
- Initial lodgement before returning to complete documentation

### Fields
- Occurrence type (required)
- Title (required)
- Date (required)
- UTC time (required)
- Location / ICAO code (required)
- Brief description (required)
- Initial risk rating: simple 1-5 scale
- Attachment: optional, single file
- Anonymous toggle

### Workflow
The reporter can submit the Quick Report directly (status: `submitted`) or save it as a draft and complete it later as a Full Report. While the report remains in `draft` status, the reporter has full edit access to all fields and can expand it to a Full Report form.

Once submitted, neither Quick nor Full mode allows editing the original content. Corrections are made via public thread amendment.

### Offline Support
Quick Report fully supports offline draft with sync-on-reconnect. The draft is persisted locally on the iPad via `UserDefaults` keyed by `safety_draft_{reportType}`. A multi-draft offline write queue is planned for a future phase.

---

## Full Report

### When to Use
- Detailed documentation where time is available
- Maintenance findings requiring structured technical fields
- Investigation pre-filing where complete context is needed from the outset
- Completing a previously saved Quick Report draft

### Fields
All base fields plus type-specific sections surfaced via conditional DisclosureGroup components:

- Flight crew occurrence: aircraft registration, flight number, phase of flight, crew complement
- Maintenance engineering: part number, work order reference, task card, aircraft type, defect category
- Ground ops: equipment type, stand/gate, traffic type
- FRAT: checklist items grouped by category (Pilot, Environment, Mission) with numeric risk score and colour-coded risk band
- TCAS: advisory type, resolution advisory, traffic count, altitude band

### Progressive Disclosure
The reporter section (name, employee ID, base, role) is collapsed by default and auto-filled from the authenticated user's profile and roster data. The reporter can expand and edit this section if the pre-filled data is incorrect.

Advanced type-specific sections are in `DisclosureGroup` components. Sections irrelevant to the selected report type are hidden entirely.

### Risk Matrix
Full 5x5 ICAO aviation risk matrix:
- Likelihood axis: A (Improbable) through E (Frequent)
- Severity axis: 1 (Negligible) through 5 (Catastrophic)
- Reporter submits their initial assessment with the report
- Safety team records their final assessment at or before closure

### Offline Support
Fully supported via local draft persistence. Single draft per report type in Phase 1. Multi-draft queue and background sync are planned for a future phase.

---

## Field Coverage Comparison

| Field | Quick | Full |
|---|---|---|
| Occurrence / Hazard type | Yes | Yes |
| Title | Yes | Yes |
| Date | Yes | Yes |
| UTC Time | Yes | Yes |
| Location / ICAO | Yes | Yes |
| Description | Brief | Full |
| Risk Assessment | 1-5 simple | 5x5 ICAO matrix |
| Reporter Context | Auto-filled, hidden | Collapsed section, visible and editable |
| Aircraft / Flight fields | No | Yes (conditional) |
| Crew details | No | Yes (conditional) |
| Maintenance parts | No | Yes (conditional) |
| FRAT fields | No | Yes (conditional) |
| Attachments | 1 file | Multiple files |
| Anonymous toggle | Yes | Yes |

---

## Upgrade Path

A Quick Report in `draft` status can be converted to Full Report editing by the reporter. The reporter opens the draft and switches to the Full Report form, which pre-fills all Quick Report fields into the matching Full Report fields. No data is lost.

Once a report reaches `submitted` status (regardless of mode), neither Quick nor Full allows editing the original content. The reporter can post a public thread message describing any correction; the safety team applies the correction and records it in an internal thread note.

---

## Backend

Both modes POST to the same `SafetyController` methods:

- `submitQuickReport()` — Quick Report path
- `submit()` — Full Report path

Both create the same `safety_reports` row with the same schema. The `is_quick_report` flag (stored in `extra_fields`) distinguishes them in audit logs and in the safety team report detail view.

On the iPad, both modes call `SafetyApiController::store()`. The JSON body includes an `is_quick_report` boolean. The API response returns the same `{ "reference_no": "...", "id": ... }` structure regardless of mode.
