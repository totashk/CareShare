-- OPTIONAL test-only action: disable online access for the two sample borrowers.
-- Run after migration_001_public_website.sql against a disposable database only.
UPDATE borrowers SET password_hash=NULL WHERE email IN ('taylor@example.test','jordan@example.test');
