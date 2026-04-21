-- ─────────────────────────────────────────────────────────────────────────────
-- Migration 035 — Password reset tokens + TOTP 2FA enrolment
-- Targets: production MySQL.  A matching SQLite port lives in _sqlite.sql.
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64)     NOT NULL,                -- sha256 of the raw token
    `email`      VARCHAR(190) NOT NULL,                -- the email the link was sent to (so later email changes don't unlock old tokens)
    `requested_ip` VARCHAR(45) DEFAULT NULL,
    `expires_at` TIMESTAMP    NOT NULL,
    `used_at`    TIMESTAMP    NULL DEFAULT NULL,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_prt_token` (`token_hash`),
    KEY `idx_prt_user`    (`user_id`),
    KEY `idx_prt_expires` (`expires_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_2fa` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`       INT UNSIGNED NOT NULL,
    `secret`        VARCHAR(64)  NOT NULL,             -- base32 TOTP shared secret
    `enabled_at`    TIMESTAMP    NULL DEFAULT NULL,
    `last_used_at`  TIMESTAMP    NULL DEFAULT NULL,
    `backup_codes`  TEXT         DEFAULT NULL,         -- JSON array of sha256-hashed one-time codes
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_u2fa_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
