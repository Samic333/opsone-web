- `app/Controllers/RoleController.php` (New)
- `views/roles/index.php` (New)
- `views/roles/show.php` (New)
- `app/Controllers/TenantController.php`
- `app/Controllers/UserController.php`
- `config/routes.php`
- `views/layouts/app.php`
- `views/users/edit.php`

**Playwright Checks Passed:**
- Validated `Roles & Permissions` structure logic for Airline Admin.
- Validated injection of capability overrides on single-user forms.
- Validated execution of global Navigation menus targeting Staff configuration tables.
- Remedied one isolated syntax failure (`csrfField()` vs `csrf_field()`) detected via subagent automated walkthrough.

**Next Phase Target:** Phase 3 — Crew Profiles & Data

**Known Blockers:** None.
