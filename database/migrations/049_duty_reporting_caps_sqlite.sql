-- Migration 049 (sqlite) — Configurable duty-time caps.
-- See 049_duty_reporting_caps.sql for context. SQLite has no
-- INFORMATION_SCHEMA, so add-column-if-missing logic is handled by
-- the apply script (try/skip).

ALTER TABLE duty_reporting_settings ADD COLUMN monthly_duty_cap_hours INTEGER NOT NULL DEFAULT 190;
ALTER TABLE duty_reporting_settings ADD COLUMN yearly_duty_cap_hours  INTEGER NOT NULL DEFAULT 2000;
