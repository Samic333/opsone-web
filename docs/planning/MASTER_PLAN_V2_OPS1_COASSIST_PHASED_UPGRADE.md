# MASTER PLAN V2 — OPS1 + COASSIST PHASED UPGRADE

> **Status:** Source of truth for the second-stage upgrade/refinement of the existing Ops1 + Coassist product.
> **Do NOT overwrite.** The previous master plan lives at `opsone-web/MASTER_PHASE_PLAN.txt` and remains authoritative for original Phase 0–10 scope.
> **This file governs all phase execution from this point forward.**

---

## SOURCE OF TRUTH DECLARATION

| Platform | Folder | Role |
|---|---|---|
| Web | `opsone-web/` | **Web source of truth** — all web platform work happens here. |
| iPad / iPhone | `CrewAssist/` | **Mobile source of truth** — all hybrid iPad/iPhone app work happens here. |
| iPad mirror | `opsone-ipad-app/` | **Duplicate / reference only.** Used only for explicit sync, export, or GitHub push tasks. Never primary development. |

For all iPad feature work, bug fixes, UI updates, logic changes, database/API integration, and testing preparation → work in `CrewAssist/`.
For all web work → `opsone-web/`.
Touch `opsone-ipad-app/` **only** when explicitly asked for sync/export/mirror update/GitHub preparation.

Always consider cross-platform integration between Ops1-web and CrewAssist for APIs, auth, roles, sync, notifications, reporting, and shared data flow.

---

## EXECUTION DISCIPLINE (NON-NEGOTIABLE)

Execute this plan **one phase at a time only**.

**Do NOT:**
- jump ahead to later phases
- merge multiple unrelated phases together
- redesign unrelated modules
- touch later modules unless the current phase requires a dependency fix
- improvise new architecture that conflicts with this master plan

### Required operating cycle per phase

1. **Read this master plan file first.**
2. Execute only the requested phase.
3. Check locally before finishing.
4. Fix obvious regressions inside the touched scope.
5. Commit cleanly.
6. Push to GitHub.
7. Report:
   - files changed
   - DB/schema changes required
   - migration or SQL tasks to run
   - local checks performed
   - live checks the user should do
   - short Anti-Gravity QA checklist for live/browser verification
8. **Stop and wait for next instruction.**

### Database / migration rule

Whenever a phase needs DB changes:
- clearly list them
- create migrations if the stack supports it
- summarize exact SQL/data actions the user may need to run
- do not hide DB-impacting changes

### Testing rule

Before pushing to GitHub for each phase:
- test locally
- check browser/app behavior relevant to that phase
- fix obvious regressions
- do not claim completion without phase-relevant local verification

### Delivery rule (per phase)

Provide:
- **A.** concise changelog
- **B.** changed files summary
- **C.** local test summary
- **D.** DB/schema changes
- **E.** live-site QA checklist for the user
- **F.** whether Anti-Gravity should do a browser audit for that phase

---

## PRODUCT CONTEXT

Multi-tenant airline operations platform with:
- web platform in **Ops1-web**
- hybrid iPad/iPhone app in **CoAssist (CrewAssist)**
- privilege-based modules
- tenant-configurable behavior
- offline-aware operational workflows where needed
- role-based access control
- airline-specific settings and isolation

## DESIGN / ENGINEERING PRINCIPLES

- Preserve existing working architecture where valid.
- Improve by controlled refinement, not random rebuild.
- Prefer reusable module patterns.
- Preserve tenant isolation.
- Preserve auditability.
- Keep operational language professional and airline-appropriate.
- Prioritize flow integrity, permissions, routing, retention, and usability.
- Build with later integration between modules in mind.
- Do not overcomplicate early phases with speculative features.
- Do not create duplicate logic when shared services/components/models can be reused.

---

# MASTER IMPLEMENTATION PLAN

## PHASE 0 — ARCHITECTURE FREEZE AND FOUNDATION CLEANUP

**Objective:** Stabilize shared system foundations before deeper module work.

**Core goals:**
- audit existing app structure
- preserve what is already working
- clean shared architectural patterns
- prevent later modules from diverging in behavior

**Shared foundations to standardize:**
- tenant isolation
- role/permission engine
- module activation/deactivation
- notification hook framework
- retention policy framework
- audit trail framework
- status workflow framework
- attachment handling framework
- offline sync strategy where applicable
- route/navigation consistency
- reusable module/page patterns

**Required deliverables:**
- architecture overview
- role-permission matrix
- module governance matrix
- retention framework summary
- offline/sync framework summary
- notification/event framework summary

**Completion standard:** No random feature work beyond what is required to stabilize the foundation.

---

## PHASE 1 — SAFETY REPORTING / SAFETY MANAGEMENT

**Objective:** Build and refine a production-grade safety reporting system for all relevant staff and safety teams.

**Users:** pilots, cabin crew, engineers, other staff where allowed, safety manager, safety staff / investigators, airline admins for privilege assignment.

**Core functions:**
- Safety Reports module
- Quick Report
- Full Report
- draft/save/submit flow
- anonymous or identified reporting
- report type selection by role/tenant permissions
- reporter submitted reports view
- safety queue
- safety dashboard
- report discussion thread
- safety follow-up questions/comments
- internal notes for safety team
- action assignment from reports
- status flow: Draft → Submitted → Under Review → Investigation → Action In Progress → Closed → Reopened
- attachment support
- configurable retention
- severity/risk matrix
- safety publications / bulletins

**Critical flow rules:**
- all staff allowed by tenant policy can submit safety reports
- safety roles receive submitted reports
- safety team can ask questions/comment in thread
- reporter can see and reply
- original submitted report body must not be silently overwritten
- safety management landing page must open to Safety Dashboard, not Start Report
- Safety Reports remains separately available for safety users who also want to submit reports

**Naming rules:**
- use "Safety Reports"
- use "Safety Dashboard"
- do not use vague labels like "My Reports"
- use "Operational Notices" or "Company Notices" where appropriate instead of "My Notices"

**Completion standard:** Safety workflow functions end-to-end on browser/app without broken routing, missing queue propagation, or wrong landing pages.

---

## PHASE 2 — DUTY REPORTING / CHECK-IN / CLOCK-OUT

**Objective:** Implement an airline-appropriate duty event system, not a generic office attendance tool.

**Users:** pilots, cabin crew, engineers, management roles with monitoring privileges.

**Core functions:**
- Duty Reporting module
- Report for Duty
- Clock Out
- active duty state
- duty summary
- duty history
- management monitoring views
- exception handling
- station/base awareness
- geo-fence-aware logic where configured
- trusted device / platform biometric confirmation where applicable
- offline-safe behavior where feasible
- configurable retention

**Recommended states:** Not Reported, Checked In, On Duty, Checked Out, Missed Report, Exception Pending Review, Exception Approved, Exception Rejected.

**Critical rules:**
- not a basic punch-clock design
- must support real-world exceptions
- geo-fence must not be absolute with no fallback
- GPS/location failure must have proper exception handling
- management must be able to review exceptions
- later roster/per diem integration must be possible

**Completion standard:** Crew can report for duty and clock out reliably; managers can monitor active duty and exceptions.

---

## PHASE 3 — CREW PROFILES + LICENSING + DOCUMENT RECORDS

**Objective:** Build a controlled Personnel Compliance Record System, not just a static employee profile page.

**Coverage:** pilots, cabin crew, engineers, base managers, operational staff, admin/support staff, any station-assigned personnel.

**Architecture layers:**
1. Profile layer
2. Compliance layer
3. Approval layer
4. Eligibility layer

**Confirmed business rules:**
- staff can edit limited personal fields directly
- all sensitive compliance fields require company approval
- expiry alerts go to crew + HR + line manager/base manager
- include profile photo, scanned docs, contract docs, next of kin
- keep finance/bank data separate
- roster must later read eligibility from this module

**Core functions:**
- profile records
- role-aware required documents
- licenses / medical / passport / visa / certifications / contract docs
- image/PDF upload
- sensitive change request workflow
- HR/admin approval/rejection
- expiry states
- expiring soon / expired views
- eligibility/readiness signal for roster

**Critical rules:**
- do not hard-code only pilot logic
- do not allow direct overwrite of approved sensitive records
- distinguish approved data vs pending requested changes
- expose assignment eligibility clearly

**Completion standard:** Management can see compliant/non-compliant personnel status, and crew can submit approved updates through controlled workflows.

---

## PHASE 4 — MANUALS + CONTROLLED DOCUMENT DISTRIBUTION + ACKNOWLEDGMENT

**Objective:** Implement a real document control and distribution workflow.

**Users:** document controller, management roles, pilots, cabin crew, engineers, other tenant-defined recipients.

**Core functions:**
- document folders
- upload file/folder structure
- role/group targeting: all staff, pilots only, engineers only, management only, selected departments/stations
- versioning
- publish / replace / archive
- acknowledgment required
- unread / read / acknowledged tracking
- proof of receipt
- notifications for new/revised docs

**Critical rules:**
- not generic file storage
- document controller workflow must be clear
- must support controlled visibility and acknowledgment
- audit trail for distribution and acknowledgment is required

**Completion standard:** A document controller can publish targeted documents and track who has acknowledged them.

---

## PHASE 5 — NOTIFICATION ENGINE REFINEMENT

**Objective:** Make notifications operationally meaningful across modules.

**Channels:** in-app, push, email.
**Priority classes:** critical, important, normal, silent.

**Events supported:**
- safety follow-ups
- report status changes
- duty reminders
- expiry alerts
- document acknowledgments required
- roster changes
- flight bag assigned
- FDM event notices
- appraisal due
- training due

**Core functions:**
- unread counts
- open/read/acknowledge distinction
- notification center
- correct routing when opened
- sound alerts only for important items on supported devices

**Critical rules:**
- notifications must open the correct target
- no dead notification links
- avoid duplicate noisy notifications
- tenant/user/role targeting must be correct

**Completion standard:** Notifications behave consistently and route users to the right item/page.

---

## PHASE 6 — FLEET MANAGEMENT + AIRCRAFT COMPLIANCE

**Objective:** Create the aircraft-side compliance and utilization control layer.

**Core functions:**
- aircraft registry
- aircraft type
- registration
- base/fleet assignment
- status
- hour-based tracking
- maintenance due tracking
- overhaul/expiry items
- aircraft document records
- maintenance visibility
- future linkage to pilot log / flight usage

**Critical rules:**
- must connect cleanly later to roster, flight bag, maintenance, and logbook
- use configurable due logic
- do not treat this as just a static aircraft list

**Completion standard:** Aircraft records can be monitored for status, due items, and compliance readiness.

---

## PHASE 7 — ELECTRONIC LOGBOOK

**Objective:** Build a simple but strong pilot-friendly logbook, especially for iPad use.

**Core functions:**
- assigned flight prefill where possible
- aircraft type/registration
- departure/arrival
- off / takeoff / landing / on times
- automatic block time
- airborne time
- day/night calculation
- IFR/VFR selection
- PIC/SIC selection
- number of landings
- remarks
- export/reporting
- linkage to pilot record
- future linkage to aircraft utilization

**Critical rules:**
- avoid a form jungle
- use simple operational input flow
- prefill from flight assignments where possible
- preserve edit/save logic clearly

**Completion standard:** A pilot can enter, review, and keep an accurate electronic log with low friction.

---

## PHASE 8 — ROSTERING / SCHEDULING REDESIGN

**Objective:** Redesign roster logic into a usable operational assignment system.

**Users:** scheduler (possibly separate schedulers by department), management roles, crew users consuming roster.

**Core functions:**
- one scheduler model or separate schedulers by department
- pilots/cabin crew/engineers/parts/base ops considerations
- rest logic
- qualification rules
- compliance eligibility checks from Phase 3
- aircraft/station assignment logic
- auto-suggestion support
- manual override
- publishing
- revision history
- acknowledgment/visibility of changes

**Critical rules:**
- must consume personnel eligibility from profiles/licensing
- must support different airline staffing models
- do not implement as a rigid one-company-only system
- must become simpler than the current tedious approach

**Completion standard:** Schedulers can create and publish operationally valid assignments with visibility into conflicts and eligibility.

---

## PHASE 9 — FLIGHT ASSIGNMENT + FLIGHT BAG + FLIGHT FOLLOWING

**Objective:** Create a structured operational flight package system for assigned flights.

**Core functions:**
- flight assignment notifications
- route/sector package
- uploaded navigation flight plan
- NOTAMs
- weather package
- company documents
- weight and balance / OPT related files
- pilot upload where authorized
- base manager upload
- configurable retention
- download/offline access
- assignment-linked folder or structured package

**Critical rules:**
- assigned crew must receive the package correctly
- documents must be targeted to the correct flight/crew
- offline access matters for operational use
- avoid turning this into a generic file dump

**Completion standard:** Assigned crew can access flight-specific operational information cleanly.

---

## PHASE 10 — FDM (FLIGHT DATA MONITORING)

**Objective:** Operationalize FDM events and pilot visibility.

**Core functions:**
- CSV upload by FDM analyst
- event parsing/mapping
- pilot-specific event visibility
- notification of assigned/relevant events
- acknowledgment if required
- configurable retention
- role-based access
- trend view later if feasible

**Critical rules:**
- pilots should normally see only their relevant items unless policy says otherwise
- management visibility must follow privilege rules
- uploaded CSV mapping must be stable and documented

**Completion standard:** FDM analysts can upload event data and the right users can see and act on it.

---

## PHASE 11 — PER DIEM MANAGEMENT

**Objective:** Link duty/assignment data to per diem visibility and processing.

**Core functions:**
- station/country rate table
- outstation duration logic
- roster-linked calculation
- payable amount visibility
- finance/admin adjustment
- crew visibility of entitlement/status
- audit trail

**Critical rules:**
- source of truth must be clear
- later integration with roster and duty data must be clean
- do not hard-code one airline's rate model only

**Completion standard:** Per diem can be calculated and reviewed with traceable logic.

---

## PHASE 12 — TRAINING MANAGEMENT + COMPLIANCE DASHBOARD

**Objective:** Track recurrent training and forward-looking readiness.

**Core functions:**
- training record types
- due dates
- simulator/recurrent events
- role-specific training requirements
- training due alerts
- training manager dashboard
- crew self-visibility
- manager visibility
- future linkage to roster eligibility

**Critical rules:**
- training due status must influence readiness logic
- roles may require different training sets
- dashboard should highlight upcoming gaps

**Completion standard:** Training due items are visible, manageable, and linked to compliance readiness.

---

## PHASE 13 — CREW APPRAISAL / PERFORMANCE REPORTING

**Objective:** Digitize the post-rotation appraisal flow currently handled on paper.

**Core functions:**
- trigger from roster/end of station/rotation
- identify who can appraise whom
- role-specific forms if needed
- due reminders
- submission status
- HR/management review visibility
- confidentiality controls

**Critical rules:**
- use roster linkage to know who served together
- do not expose appraisals to unauthorized users
- keep the workflow simple enough for real crew usage

**Completion standard:** Rotation-linked appraisals can be assigned, completed, and reviewed digitally.

---

## PHASE 14 — HR WORKFLOW HARDENING

**Objective:** Build a stricter HR operating flow on top of the profile/compliance base.

**Core functions:**
- onboarding
- employee activation/deactivation
- contract lifecycle
- role assignment
- probation/contract tracking
- controlled edit approvals
- audit log
- restricted HR visibility

**Critical rules:**
- HR processes must be stricter than general profile editing
- preserve separation between HR actions and ordinary user self-service
- do not mix in finance/payroll logic here

**Completion standard:** HR can manage lifecycle and governance of personnel records cleanly.

---

## PHASE 15 — HELP HUB / GUIDED USAGE / COMPANY ONBOARDING

**Objective:** Make the system teach itself during rollout.

**Core functions:**
- role-based help content
- scheduler help
- document controller help
- inline guidance
- onboarding help cards
- temporary help overlays/tooltips if useful
- admin usage guides

**Critical rules:**
- keep help contextual
- do not clutter production workflows
- this can later support formal customer onboarding/training

**Completion standard:** New users can understand how to use complex modules without guessing.

---

## PHASE 16 — ADVANCED INTEGRATIONS

**Objective:** Connect stable internal workflows to external data/services only after the core system is stable.

**Examples:** Jeppesen, advanced weather, performance / OPT, external roster imports, enterprise/mobile device management support, other operational integrations.

**Critical rules:**
- do not start this before core modules are stable
- integration must follow clean permissions, routing, and audit rules
- prefer stable internal workflow before adding external complexity

**Completion standard:** External integrations enhance the system without destabilizing the core product.

---

# GLOBAL RULES FOR EVERY PHASE

For every phase executed:
- work only in `opsone-web/` and `CrewAssist/` as primary sources
- treat `opsone-ipad-app/` as duplicate/reference only unless explicitly asked for sync/comparison
- check this master plan file first
- execute one phase at a time only
- test locally
- fix obvious regressions
- push to GitHub
- summarize DB changes
- tell the user exactly what to check live
- provide a short Anti-Gravity browser QA checklist
- then stop and wait

---

# PHASE RESUMPTION CONVENTIONS

**When the user says "next phase":**
1. open `docs/planning/MASTER_PLAN_V2_OPS1_COASSIST_PHASED_UPGRADE.md`
2. identify the next unfinished phase
3. execute only that phase
4. follow the delivery rules above
5. stop and wait

**When the user asks for a specific phase:**
1. open this master plan file
2. find that specific phase
3. execute only that phase
4. do not touch later phases unless absolutely necessary for dependency repair
5. document any dependency change clearly

**Phrases that trigger reading this file first:**
- "check the master plan"
- "continue next phase"
- "proceed with next phase"
- "phase plan second phase"
- "continue the upgrade plan"

---

# PHASE STATUS TRACKER

| Phase | Title | Status | Notes |
|---|---|---|---|
| 0 | Architecture Freeze and Foundation Cleanup | ✅ V2 | V1 docs + V2 cross-cutting fixes |
| 1 | Safety Reporting / Safety Management | ✅ V2 | Dashboard landing verified |
| 2 | Duty Reporting / Check-In / Clock-Out | ✅ V2 | `markOverdue` wired into dashboard |
| 3 | Crew Profiles + Licensing + Document Records | ✅ V2 | CrewProfileModel::save fix; visa/address preserved |
| 4 | Manuals + Controlled Document Distribution | ✅ V2 | Migration 024 — dept/base targeting, version chain, read receipts, notifications |
| 5 | Notification Engine Refinement | ✅ V2 | Migration 025 — priority/event/ack_required + inbox + bell + API |
| 6 | Fleet Management + Aircraft Compliance | ✅ V2 | Migration 026 — aircraft, docs, maintenance, KPI dashboard |
| 7 | Electronic Logbook | ✅ V2 | Migration 027 — logbook + CSV export + admin overview |
| 8 | Rostering / Scheduling Redesign | ✅ V2 | EligibilityGate with audited override on assign |
| 9 | Flight Assignment + Flight Bag + Flight Following | ✅ V2 | Migration 028 — flights + flight_bag, publish notifies crew |
| 10 | FDM (Flight Data Monitoring) | ✅ V2 | Migration 029 — pilot-tagging + /my-fdm ack |
| 11 | Per Diem Management | ✅ V2 | Migration 030 — rates + submit → approve → pay |
| 12 | Training Management + Compliance Dashboard | ✅ V2 | Migration 031 — auto-expiry from validity_months |
| 13 | Crew Appraisal / Performance Reporting | ✅ V2 | Migration 032 — confidential draft → submit → accept |
| 14 | HR Workflow Hardening | ✅ V2 | Lifecycle KPIs + activate/deactivate/contract expiry UI |
| 15 | Help Hub / Guided Usage / Company Onboarding | ✅ V2 | Role-aware topics with 16 guides |
| 16 | Advanced Integrations | ✅ V2 (stubs) | Migration 033 — registry + status transition + audit |

---

# COMPANION REFERENCE

- **Detailed source PDF:** `/Users/samic/Downloads/OPS1_CrewAssist_Master_Plan_V2_Detailed.pdf`
- **Previous (V1) master plan (do NOT overwrite):** `opsone-web/MASTER_PHASE_PLAN.txt`
- **Existing phase docs (V1 artifacts):** `opsone-web/docs/` (individual phase/framework markdown files)

End of master plan.
