-- Migration 052 (SQLite) — Per-diem extension requests.
--
-- A pilot on outstation may need to extend their stay beyond the originally
-- assigned period. The original `per_diem_claims` row stays intact; the
-- extension request lives in this child table so finance can approve / reject
-- the additional days independently and the audit trail is preserved.
--
-- Idempotent — uses CREATE TABLE IF NOT EXISTS.

BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS per_diem_extension_requests (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    tenant_id       INTEGER NOT NULL,
    claim_id        INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    extra_days      REAL    NOT NULL,
    new_period_to   TEXT    DEFAULT NULL,         -- new end date the pilot is requesting
    extra_amount    REAL    DEFAULT NULL,         -- extra_days × rate at request time
    currency        TEXT    DEFAULT NULL,
    reason          TEXT    NOT NULL,
    status          TEXT    NOT NULL DEFAULT 'pending'
        CHECK(status IN ('pending','approved','rejected','withdrawn')),
    decided_by      INTEGER DEFAULT NULL,
    decided_at      TEXT    DEFAULT NULL,
    decision_notes  TEXT    DEFAULT NULL,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (tenant_id)  REFERENCES tenants(id)         ON DELETE CASCADE,
    FOREIGN KEY (claim_id)   REFERENCES per_diem_claims(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)           ON DELETE CASCADE,
    FOREIGN KEY (decided_by) REFERENCES users(id)           ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_pdex_user   ON per_diem_extension_requests(user_id);
CREATE INDEX IF NOT EXISTS idx_pdex_claim  ON per_diem_extension_requests(claim_id);
CREATE INDEX IF NOT EXISTS idx_pdex_status ON per_diem_extension_requests(status);

COMMIT;
