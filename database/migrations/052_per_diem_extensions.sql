-- Migration 052 (MySQL) — Per-diem extension requests.
--
-- Sister of 052_per_diem_extensions_sqlite.sql. Adds the same table to the
-- production MySQL store.

CREATE TABLE IF NOT EXISTS per_diem_extension_requests (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id       INT UNSIGNED NOT NULL,
    claim_id        INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    extra_days      DECIMAL(5,2) NOT NULL,
    new_period_to   DATE         DEFAULT NULL,
    extra_amount    DECIMAL(11,2) DEFAULT NULL,
    currency        VARCHAR(3)   DEFAULT NULL,
    reason          TEXT         NOT NULL,
    status          ENUM('pending','approved','rejected','withdrawn')
                    NOT NULL DEFAULT 'pending',
    decided_by      INT UNSIGNED DEFAULT NULL,
    decided_at      DATETIME     DEFAULT NULL,
    decision_notes  TEXT         DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pdex_user   (user_id),
    KEY idx_pdex_claim  (claim_id),
    KEY idx_pdex_status (status),
    CONSTRAINT fk_pdex_tenant   FOREIGN KEY (tenant_id)  REFERENCES tenants(id)         ON DELETE CASCADE,
    CONSTRAINT fk_pdex_claim    FOREIGN KEY (claim_id)   REFERENCES per_diem_claims(id) ON DELETE CASCADE,
    CONSTRAINT fk_pdex_user     FOREIGN KEY (user_id)    REFERENCES users(id)           ON DELETE CASCADE,
    CONSTRAINT fk_pdex_decider  FOREIGN KEY (decided_by) REFERENCES users(id)           ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
