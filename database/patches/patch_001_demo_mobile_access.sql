-- Patch 001: Enable mobile_access for all demo accounts
-- Run once on the live Namecheap MySQL database.
-- These accounts are for iPad demo/testing across all role dashboards.
-- Safe to run multiple times (idempotent UPDATE).

UPDATE users
SET mobile_access = 1
WHERE email IN (
    'demo.superadmin@acentoza.com',
    'demo.airadmin@acentoza.com',
    'demo.hr@acentoza.com',
    'demo.scheduler@acentoza.com',
    'demo.chiefpilot@acentoza.com',
    'demo.headcabin@acentoza.com',
    'demo.engmanager@acentoza.com',
    'demo.safety@acentoza.com',
    'demo.fdm@acentoza.com',
    'demo.doccontrol@acentoza.com',
    'demo.basemanager@acentoza.com',
    'demo.pilot@acentoza.com',
    'demo.cabin@acentoza.com',
    'demo.engineer@acentoza.com'
)
AND tenant_id = 1;

-- Verify
SELECT email, mobile_access FROM users WHERE email LIKE 'demo.%@acentoza.com' ORDER BY email;
