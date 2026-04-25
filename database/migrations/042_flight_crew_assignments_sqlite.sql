-- Migration 042 (SQLite) — Flight crew assignments. See MySQL version.

BEGIN TRANSACTION;

CREATE TABLE IF NOT EXISTS flight_crew_assignments (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    flight_id       INTEGER NOT NULL,
    user_id         INTEGER NOT NULL,
    role_on_flight  TEXT    NOT NULL DEFAULT 'cabin_crew',
    is_lead         INTEGER NOT NULL DEFAULT 0,
    notes           TEXT    DEFAULT NULL,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE (flight_id, user_id, role_on_flight),
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_fca_flight ON flight_crew_assignments(flight_id);
CREATE INDEX IF NOT EXISTS idx_fca_user   ON flight_crew_assignments(user_id);

-- Demo seed: assign cabin + engineer to every flight that has a captain.
INSERT OR IGNORE INTO flight_crew_assignments (flight_id, user_id, role_on_flight, is_lead, notes)
SELECT f.id, u.id, 'cabin_crew', 1, 'Demo seed (042) — purser/lead.'
  FROM flights f, users u
 WHERE f.tenant_id = 1
   AND f.captain_id IS NOT NULL
   AND u.email = 'demo.cabin@acentoza.com'
   AND u.tenant_id = 1;

INSERT OR IGNORE INTO flight_crew_assignments (flight_id, user_id, role_on_flight, is_lead, notes)
SELECT f.id, u.id, 'engineer', 0, 'Demo seed (042) — line engineer.'
  FROM flights f, users u
 WHERE f.tenant_id = 1
   AND f.captain_id IS NOT NULL
   AND u.email = 'demo.engineer@acentoza.com'
   AND u.tenant_id = 1;

COMMIT;
