-- CareShare migration 002: reviewed borrower equipment offers and canonical staff identities.
-- Select the intended database before running. Idempotent and non-destructive.
-- Run with the intended database selected; do not import into a database to be reset.

CREATE TABLE IF NOT EXISTS equipment_offers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 borrower_id INT UNSIGNED NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 description TEXT NULL,
 photo_path VARCHAR(255) NOT NULL,
 status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
 staff_note TEXT NULL,
 reviewed_by INT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 equipment_id INT UNSIGNED NULL,
 equipment_item_id INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_offer_status(status), INDEX idx_offer_borrower(borrower_id),
 CONSTRAINT fk_offer_borrower FOREIGN KEY(borrower_id) REFERENCES borrowers(id),
 CONSTRAINT fk_offer_category FOREIGN KEY(category_id) REFERENCES equipment_categories(id),
 CONSTRAINT fk_offer_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_offer_equipment FOREIGN KEY(equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
 CONSTRAINT fk_offer_item FOREIGN KEY(equipment_item_id) REFERENCES equipment_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ID 7 is intentionally preserved. Avoid a duplicate-email error if the canonical
-- Fatima email already belongs to another row; emit an explicit preflight notice.
SET @fatima_email_owner := (SELECT id FROM users WHERE LOWER(email)=LOWER('fatima@careshar.ly') LIMIT 1);
UPDATE users SET full_name='Fatima Alshanfak', email='fatima@careshar.ly', role='Administrator'
 WHERE id=7 AND (@fatima_email_owner IS NULL OR @fatima_email_owner=7);
SELECT 'REVIEW REQUIRED: fatima@careshar.ly belongs to a user other than ID 7; no account was overwritten.' AS migration_notice
 WHERE @fatima_email_owner IS NOT NULL AND @fatima_email_owner<>7;

-- Fresh/demo installs may not yet contain ID 7. Reserve the requested ID only when
-- neither it nor the canonical email is already present; password must then be set.
INSERT INTO users(id,full_name,email,password_hash,role,status)
SELECT 7,'Fatima Alshanfak','fatima@careshar.ly',
       '!DISABLED_REQUIRES_SETUP!',
       'Administrator','Inactive'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE id=7)
  AND NOT EXISTS (SELECT 1 FROM users WHERE LOWER(email)=LOWER('fatima@careshar.ly'));

-- If the named staff login is absent, add a disabled-until-password-set account.
-- The marker is not a password hash; database/set_staff_password.php sets a real hash.
INSERT INTO users(full_name,email,password_hash,role,status)
SELECT 'Hamza Alshanfak','Hamza@careshare.ly',
       '!DISABLED_REQUIRES_SETUP!',
       'Staff','Inactive'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE LOWER(email)=LOWER('Hamza@careshare.ly'));

-- Preserve legacy rows and their foreign keys, while disabling other staff logins.
UPDATE users SET status='Inactive'
 WHERE role IN ('Administrator','Staff') AND id<>7 AND LOWER(email)<>LOWER('Hamza@careshare.ly');
