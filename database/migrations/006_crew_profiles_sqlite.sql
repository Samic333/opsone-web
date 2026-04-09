-- =====================================================
-- Migration 006: Crew Profiles & Licences (SQLite)
-- =====================================================

CREATE TABLE IF NOT EXISTS "crew_profiles" (
    "id"                  INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id"             INTEGER NOT NULL UNIQUE,
    "tenant_id"           INTEGER NOT NULL,
    "date_of_birth"       TEXT    DEFAULT NULL,
    "nationality"         TEXT    DEFAULT NULL,
    "phone"               TEXT    DEFAULT NULL,
    "emergency_name"      TEXT    DEFAULT NULL,
    "emergency_phone"     TEXT    DEFAULT NULL,
    "emergency_relation"  TEXT    DEFAULT NULL,
    "passport_number"     TEXT    DEFAULT NULL,
    "passport_country"    TEXT    DEFAULT NULL,
    "passport_expiry"     TEXT    DEFAULT NULL,
    "medical_class"       TEXT    DEFAULT NULL,
    "medical_expiry"      TEXT    DEFAULT NULL,
    "contract_type"       TEXT    DEFAULT NULL,
    "contract_expiry"     TEXT    DEFAULT NULL,
    "created_at"          TEXT    NOT NULL DEFAULT (datetime('now')),
    "updated_at"          TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY ("user_id")   REFERENCES "users"("id") ON DELETE CASCADE,
    FOREIGN KEY ("tenant_id") REFERENCES "tenants"("id") ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS "licenses" (
    "id"                INTEGER PRIMARY KEY AUTOINCREMENT,
    "user_id"           INTEGER NOT NULL,
    "tenant_id"         INTEGER NOT NULL,
    "license_type"      TEXT    NOT NULL,
    "license_number"    TEXT    DEFAULT NULL,
    "issuing_authority" TEXT    DEFAULT NULL,
    "issue_date"        TEXT    DEFAULT NULL,
    "expiry_date"       TEXT    DEFAULT NULL,
    "notes"             TEXT    DEFAULT NULL,
    "created_at"        TEXT    NOT NULL DEFAULT (datetime('now')),
    "updated_at"        TEXT    NOT NULL DEFAULT (datetime('now')),
    FOREIGN KEY ("user_id")   REFERENCES "users"("id") ON DELETE CASCADE,
    FOREIGN KEY ("tenant_id") REFERENCES "tenants"("id") ON DELETE CASCADE
);
