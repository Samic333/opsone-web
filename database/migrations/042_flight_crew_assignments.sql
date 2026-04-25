-- Migration 042 — Flight crew assignments table.
--
-- The legacy `flights` table only has captain_id + fo_id, which means
-- cabin crew, engineers, and other rostered roles can never be linked
-- to a flight. As a direct consequence:
--   * /api/flights/mine returns [] for cabin crew (verified with curl).
--   * Cabin Flight Folder shows "No assigned flights" on iPad.
--   * After-Mission (Cabin) form is unreachable.
--
-- Fix: a dedicated join table that supports an arbitrary number of
-- crew roles per flight. Captain + FO stay on the parent table for
-- backwards-compatibility (existing FlightApi reads from there) — the
-- assignments table is additive.
--
-- Idempotent: CREATE TABLE IF NOT EXISTS + INSERT IGNORE.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `flight_crew_assignments` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `flight_id`       INT UNSIGNED NOT NULL,
    `user_id`         INT UNSIGNED NOT NULL,
    `role_on_flight`  VARCHAR(40)  NOT NULL DEFAULT 'cabin_crew'
                                   COMMENT 'cabin_crew | purser | scc | engineer | observer | jumpseat',
    `is_lead`         TINYINT(1)   NOT NULL DEFAULT 0,
    `notes`           VARCHAR(255) DEFAULT NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_fca_flight_user_role` (`flight_id`, `user_id`, `role_on_flight`),
    KEY `idx_fca_flight` (`flight_id`),
    KEY `idx_fca_user`   (`user_id`),
    FOREIGN KEY (`flight_id`) REFERENCES `flights`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resolve demo accounts by email so the seed works on both local and
-- production where numeric user IDs differ.
SELECT @cabin_id    := id FROM `users` WHERE email = 'demo.cabin@acentoza.com'    AND tenant_id = 1 LIMIT 1;
SELECT @engineer_id := id FROM `users` WHERE email = 'demo.engineer@acentoza.com' AND tenant_id = 1 LIMIT 1;

-- Assign Noor (cabin) and Eric (engineer) to every demo flight in
-- tenant 1 that already has a captain assigned. This makes the cabin
-- and engineer demo accounts useful out-of-the-box on iPad.
INSERT IGNORE INTO `flight_crew_assignments` (flight_id, user_id, role_on_flight, is_lead, notes)
SELECT f.id, @cabin_id, 'cabin_crew', 1, 'Demo seed (042) — purser/lead.'
  FROM `flights` f
 WHERE f.tenant_id = 1
   AND f.captain_id IS NOT NULL
   AND @cabin_id IS NOT NULL;

INSERT IGNORE INTO `flight_crew_assignments` (flight_id, user_id, role_on_flight, is_lead, notes)
SELECT f.id, @engineer_id, 'engineer', 0, 'Demo seed (042) — line engineer.'
  FROM `flights` f
 WHERE f.tenant_id = 1
   AND f.captain_id IS NOT NULL
   AND @engineer_id IS NOT NULL;

COMMIT;
