-- SQLite port of migration 035 — password reset tokens + TOTP 2FA.

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL,
    token_hash    TEXT    NOT NULL UNIQUE,
    email         TEXT    NOT NULL,
    requested_ip  TEXT    DEFAULT NULL,
    expires_at    TEXT    NOT NULL,
    used_at       TEXT    DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_prt_user    ON password_reset_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_prt_expires ON password_reset_tokens(expires_at);

CREATE TABLE IF NOT EXISTS user_2fa (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL UNIQUE,
    secret        TEXT    NOT NULL,
    enabled_at    TEXT    DEFAULT NULL,
    last_used_at  TEXT    DEFAULT NULL,
    backup_codes  TEXT    DEFAULT NULL,
    created_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at    TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
