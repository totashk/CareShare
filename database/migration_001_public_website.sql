-- CareShare migration 001: public website, borrower accounts and contact messages.
-- Run in phpMyAdmin (database `careshare` selected) AFTER careshare.sql has been imported.
-- It only ADDS things and keeps all existing rows. It is safe to run more than once.
-- (Written without "ADD COLUMN IF NOT EXISTS" because MySQL, unlike MariaDB, does not support it.)
-- Run with the intended database selected. This keeps test databases isolated too.

-- Helper: run a statement only when a condition is true.
DROP PROCEDURE IF EXISTS careshare_run;
DELIMITER $$
CREATE PROCEDURE careshare_run(IN cond_sql TEXT, IN stmt TEXT)
BEGIN
  SET @c := 0;
  SET @q := CONCAT('SELECT (', cond_sql, ') INTO @c');
  PREPARE p1 FROM @q; EXECUTE p1; DEALLOCATE PREPARE p1;
  IF @c = 1 THEN
    SET @s := stmt;
    PREPARE p2 FROM @s; EXECUTE p2; DEALLOCATE PREPARE p2;
  END IF;
END$$
DELIMITER ;

-- 1. Borrowers can have an online account (password_hash NULL = staff-created record without online access).
CALL careshare_run(
 "SELECT COUNT(*)=0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='borrowers' AND COLUMN_NAME='password_hash'",
 "ALTER TABLE borrowers ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
CALL careshare_run(
 "SELECT COUNT(*)=0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='borrowers' AND COLUMN_NAME='last_login_at'",
 "ALTER TABLE borrowers ADD COLUMN last_login_at DATETIME NULL AFTER password_hash");
-- One borrower per email address (NULL emails are still allowed many times).
-- Only added when no duplicate emails already exist, so existing data is never rejected.
CALL careshare_run(
 "SELECT (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='borrowers' AND INDEX_NAME='uq_borrower_email')=0 AND (SELECT COUNT(*) FROM (SELECT email FROM borrowers WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*)>1) d)=0",
 "ALTER TABLE borrowers ADD UNIQUE INDEX uq_borrower_email (email)");

-- 2. Requests submitted online by a borrower have no staff creator, and may name the exact equipment model.
CALL careshare_run(
 "SELECT COUNT(*)=1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='loan_requests' AND COLUMN_NAME='created_by' AND IS_NULLABLE='NO'",
 "ALTER TABLE loan_requests MODIFY created_by INT UNSIGNED NULL");
CALL careshare_run(
 "SELECT COUNT(*)=0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='loan_requests' AND COLUMN_NAME='equipment_id'",
 "ALTER TABLE loan_requests ADD COLUMN equipment_id INT UNSIGNED NULL AFTER category_id, ADD INDEX idx_request_equipment (equipment_id), ADD CONSTRAINT fk_request_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id)");
CALL careshare_run(
 "SELECT COUNT(*)=0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='loan_requests' AND COLUMN_NAME='cancelled_by_borrower'",
 "ALTER TABLE loan_requests ADD COLUMN cancelled_by_borrower TINYINT(1) NOT NULL DEFAULT 0");

-- 3. Messages sent through the public Contact page.
CREATE TABLE IF NOT EXISTS contact_messages (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL,
 subject VARCHAR(150) NOT NULL,
 message TEXT NOT NULL,
 status ENUM('New','Handled') NOT NULL DEFAULT 'New',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 handled_by INT UNSIGNED NULL,
 handled_at DATETIME NULL,
 INDEX idx_contact_status(status),
 CONSTRAINT fk_contact_handler FOREIGN KEY(handled_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

DROP PROCEDURE IF EXISTS careshare_run;
