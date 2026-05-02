-- Migration 049 — Configurable duty-time caps on duty_reporting_settings.
--
-- The Pilot Duty Time page renders monthly + yearly threshold progress
-- bars against a hard-coded reference cap (190h / 2000h) inside the
-- controller. Airlines need to set their own caps so the threshold
-- pills (Normal / Approaching / Exceeded) reflect their FTL policy.
--
-- Idempotent: ALTER ... ADD COLUMN guarded by INFORMATION_SCHEMA check.

START TRANSACTION;

SET @add_monthly := (
    SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'duty_reporting_settings'
       AND COLUMN_NAME  = 'monthly_duty_cap_hours'
);

SET @sql := IF(@add_monthly = 0,
    'ALTER TABLE `duty_reporting_settings`
        ADD COLUMN `monthly_duty_cap_hours` INT UNSIGNED NOT NULL DEFAULT 190
            COMMENT ''Reference monthly duty hours cap; airline ops manual is source of truth''
            AFTER `retention_days`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @add_yearly := (
    SELECT COUNT(*)
      FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'duty_reporting_settings'
       AND COLUMN_NAME  = 'yearly_duty_cap_hours'
);

SET @sql := IF(@add_yearly = 0,
    'ALTER TABLE `duty_reporting_settings`
        ADD COLUMN `yearly_duty_cap_hours` INT UNSIGNED NOT NULL DEFAULT 2000
            COMMENT ''Reference yearly duty hours cap''
            AFTER `monthly_duty_cap_hours`',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

COMMIT;
