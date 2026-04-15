-- Phase 4: Notice Categories, Role Visibility, File Expiry
-- MySQL version

CREATE TABLE IF NOT EXISTS notice_categories (
    id         INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tenant_id  INT UNSIGNED    NOT NULL,
    name       VARCHAR(120)    NOT NULL,
    slug       VARCHAR(80)     NOT NULL,
    sort_order TINYINT         NOT NULL DEFAULT 0,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uq_nc_tenant_slug (tenant_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notice_role_visibility (
    notice_id  INT UNSIGNED NOT NULL,
    role_id    INT UNSIGNED NOT NULL,
    PRIMARY KEY (notice_id, role_id),
    FOREIGN KEY (notice_id) REFERENCES notices(id)  ON DELETE CASCADE,
    FOREIGN KEY (role_id)   REFERENCES roles(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE files
    ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL;
