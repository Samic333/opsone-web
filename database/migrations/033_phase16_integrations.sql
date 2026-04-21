-- Phase 16 (V2) — Advanced Integrations registry  (MySQL)

CREATE TABLE IF NOT EXISTS `integrations` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tenant_id`   INT UNSIGNED NOT NULL,
    `provider`    VARCHAR(60)  NOT NULL,    -- jeppesen, sita_wx, opt, roster_import, mdm, apns, ses
    `display_name` VARCHAR(120) NOT NULL,
    `status`      ENUM('disabled','pending','live','error') NOT NULL DEFAULT 'disabled',
    `config_json` TEXT         DEFAULT NULL,
    `last_sync_at` TIMESTAMP   NULL DEFAULT NULL,
    `last_error`  TEXT         DEFAULT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_int_tenant_provider` (`tenant_id`,`provider`),
    CONSTRAINT `fk_int_tenant` FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
