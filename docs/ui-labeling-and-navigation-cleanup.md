# UI Labeling and Navigation Cleanup — Phase 1.2

## Why This Matters

Consistent, professional labeling reduces crew confusion and reflects airline operational standards. Informal or ambiguous labels erode trust in a safety-critical system. When crew see "My Reports" instead of "Safety Reports," the label signals personal records rather than a formal regulated process. Aviation operators refer to these systems as SMS portals, safety reporting systems, and occurrence reporting — not personal filing cabinets.

The Phase 1.2 labeling pass aligns the product with that standard.

---

## Changes Made in Phase 1.2

| Before | After | Location |
|---|---|---|
| "My Reports" (safety module) | "Safety Reports" | Web sidebar, iPad nav title, button labels |
| "My Notices" | "Operational Notices" | Web sidebar, crew-facing notice portal |
| "Notifications" (iPad bell badge) | Unchanged — correct for the notification bell | iPad |
| Safety module nav item | "Safety Reports" | Web sidebar label |

---

## Rationale per Change

### "Safety Reports" vs "My Reports"
"My" is personal and informal. In an aviation SMS context, safety reports are formal regulatory submissions — not personal notes. "Safety Reports" clearly identifies the module, is consistent with how operators describe the system to regulators, and is the term used in EU Regulation 376/2014 and ICAO Annex 13 documentation. It also removes ambiguity when users navigate between modules: "Safety Reports" vs "Documents" vs "My Roster" are unambiguous in scope.

### "Operational Notices" vs "My Notices"
Notices in an airline context are company communications — operational orders, NOTAMs forwarded by ops, regulatory updates, crew information bulletins. They are authoritative documents issued to the workforce, not messages to an individual. "My Notices" implies personal messages or a personal inbox. "Operational Notices" reflects the nature of the content and aligns with industry terminology (NOTAM = Notice to Airmen, OM = Operations Manual notice, etc.).

---

## Navigation Structure (as of Phase 1.2)

### Crew Sidebar (all authenticated airline users)

| Label | Route |
|---|---|
| Dashboard | `/dashboard` |
| Safety Reports | `/safety` |
| Operational Notices | `/my-notices` |
| My Roster | `/my-roster` |
| Documents | `/my-files` |
| [Other modules per tenant activation] | — |

### Safety Team Additional Sidebar Items

The following items are appended to the sidebar for users holding `safety_officer` or `airline_admin`:

| Label | Route |
|---|---|
| Safety Queue | `/safety/queue` |
| Corrective Actions | `/safety/team/actions` |
| Safety Publications | `/safety/publications` |
| Safety Settings | `/safety/settings` |

---

## iPad Tab and Navigation Labels (as of Phase 1.2)

| Screen | Label |
|---|---|
| Safety module entry | "Safety Reports" |
| Reports list view | "Safety Reports" |
| Drafts list | "Drafts" |
| Notice module | "Operational Notices" (future — currently "Notifications" serves combined purpose; see note below) |
| Notification bell (badge) | "Notifications" — unchanged, correct |

> Note: The iPad currently uses "Notifications" as the combined label for the notification bell and the notice/communication portal. "Operational Notices" as a dedicated label applies once the notice module receives its own dedicated tab in a future phase. The notification bell label is correct and is not changed.

---

## Future Label Considerations

The following label decisions are flagged for future phases but not changed in Phase 1.2:

| Item | Current | Proposed / Consideration |
|---|---|---|
| Reporter follow-up section | Not yet surfaced as a named section | "Follow-Ups" — for reporter replies awaiting safety team response |
| Investigation status badge | "Under Investigation" | Shorten to "Investigation" — shorter for badge display, still unambiguous |
| Action status badge | "Action In Progress" | Keep as-is for clarity; abbreviate only if display width forces it |
| Quick Report button | "Quick Report" | Keep — user-facing label is clear and action-oriented |
| Full Report button | "Full Report" | Keep |

### Badge Length Note
"Investigation" (13 chars) fits badge constraints on both web (`badge-orange`) and iPad (`statusOrange`). "Under Investigation" (18 chars) wraps in narrow contexts. Recommend adopting "Investigation" as the standard badge label across both platforms in the next label pass.
