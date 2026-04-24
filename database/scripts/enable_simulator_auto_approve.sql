-- Opt a tenant into the "auto-approve simulator devices" dev convenience.
--
-- When the flag is set, a mobile register request whose `platform` or `model`
-- contains "Simulator" lands with approval_status = 'approved' directly
-- instead of 'pending', so `simctl install` doesn't require a manual approval
-- every dev cycle.
--
-- The backend guard is a double-check: both the flag AND the simulator
-- detection must be true. Production devices always still pend.
--
-- Run: pick the tenant id, paste into phpMyAdmin / mysql CLI.

SET @tenant_id := 1;  -- change to your test tenant

UPDATE tenants
SET    settings = JSON_SET(COALESCE(settings, '{}'), '$.auto_approve_simulator_devices', TRUE)
WHERE  id = @tenant_id;

-- Verify the flag is set.
SELECT id, name, JSON_EXTRACT(settings, '$.auto_approve_simulator_devices') AS auto_approve_sim
FROM   tenants
WHERE  id = @tenant_id;

-- To disable later:
-- UPDATE tenants
-- SET    settings = JSON_SET(settings, '$.auto_approve_simulator_devices', FALSE)
-- WHERE  id = @tenant_id;
