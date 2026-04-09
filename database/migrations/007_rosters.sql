-- Phase 4: Roster Foundation (MySQL 8.0)
-- Run on production via phpMyAdmin

CREATE TABLE IF NOT EXISTS rosters (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id   INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    roster_date DATE NOT NULL,
    duty_type   ENUM('flight','standby','off','training','sim','leave','rest') NOT NULL DEFAULT 'off',
    duty_code   VARCHAR(20)  NULL COMMENT 'Short code e.g. FLT, SBY, OFF, SIM, LVE',
    notes       VARCHAR(255) NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_user_date  (user_id, roster_date),
    KEY        idx_ten_date  (tenant_id, roster_date),
    CONSTRAINT fk_rost_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    CONSTRAINT fk_rost_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
