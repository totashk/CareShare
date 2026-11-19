-- CareShare database for MySQL 5.7+ / MAMP. Import in phpMyAdmin.
CREATE DATABASE IF NOT EXISTS careshare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE careshare;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS return_records, activity_logs, request_items, loans, loan_requests, equipment_items, equipment, borrowers, equipment_categories, users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('Administrator','Staff') NOT NULL DEFAULT 'Staff',
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE equipment_categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(80) NOT NULL UNIQUE,
 description VARCHAR(255) NULL
) ENGINE=InnoDB;
CREATE TABLE equipment (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 category_id INT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 description TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_equipment_category FOREIGN KEY(category_id) REFERENCES equipment_categories(id)
) ENGINE=InnoDB;
CREATE TABLE equipment_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 equipment_id INT UNSIGNED NOT NULL,
 asset_code VARCHAR(30) NOT NULL UNIQUE,
 condition_label ENUM('Good','Fair','Needs inspection','Damaged') NOT NULL DEFAULT 'Good',
 status ENUM('Available','On Loan','Awaiting Inspection','Under Repair','Retired') NOT NULL DEFAULT 'Available',
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_item_status(status), INDEX idx_item_equipment(equipment_id),
 CONSTRAINT fk_item_equipment FOREIGN KEY(equipment_id) REFERENCES equipment(id)
) ENGINE=InnoDB;
CREATE TABLE borrowers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 full_name VARCHAR(120) NOT NULL,
 phone VARCHAR(30) NOT NULL,
 email VARCHAR(190) NULL,
 address VARCHAR(500) NULL,
 notes TEXT NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_borrower_name(full_name)
) ENGINE=InnoDB;
CREATE TABLE loan_requests (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 borrower_id INT UNSIGNED NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 quantity SMALLINT UNSIGNED NOT NULL,
 start_date DATE NOT NULL,
 due_date DATE NOT NULL,
 notes TEXT NULL,
 status ENUM('Pending','Approved','Rejected','Issued','Cancelled') NOT NULL DEFAULT 'Pending',
 created_by INT UNSIGNED NOT NULL,
 reviewed_by INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 reviewed_at DATETIME NULL,
 INDEX idx_request_status(status), INDEX idx_request_period(start_date,due_date),
 CONSTRAINT fk_request_borrower FOREIGN KEY(borrower_id) REFERENCES borrowers(id),
 CONSTRAINT fk_request_category FOREIGN KEY(category_id) REFERENCES equipment_categories(id),
 CONSTRAINT fk_request_creator FOREIGN KEY(created_by) REFERENCES users(id),
 CONSTRAINT fk_request_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE request_items (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id INT UNSIGNED NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 INDEX idx_ri_request(request_id),
 CONSTRAINT fk_ri_request FOREIGN KEY(request_id) REFERENCES loan_requests(id) ON DELETE CASCADE,
 CONSTRAINT fk_ri_category FOREIGN KEY(category_id) REFERENCES equipment_categories(id)
) ENGINE=InnoDB;
CREATE TABLE loans (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 request_id INT UNSIGNED NULL,
 borrower_id INT UNSIGNED NOT NULL,
 item_id INT UNSIGNED NOT NULL,
 issued_by INT UNSIGNED NOT NULL,
 issued_at DATETIME NOT NULL,
 due_date DATE NOT NULL,
 returned_at DATE NULL,
 return_condition ENUM('Good','Fair','Needs inspection','Damaged') NULL,
 return_notes TEXT NULL,
 returned_by INT UNSIGNED NULL,
 INDEX idx_loan_active(returned_at,due_date), INDEX idx_loan_borrower(borrower_id),
 CONSTRAINT fk_loan_request FOREIGN KEY(request_id) REFERENCES loan_requests(id) ON DELETE SET NULL,
 CONSTRAINT fk_loan_borrower FOREIGN KEY(borrower_id) REFERENCES borrowers(id),
 CONSTRAINT fk_loan_item FOREIGN KEY(item_id) REFERENCES equipment_items(id),
 CONSTRAINT fk_loan_issuer FOREIGN KEY(issued_by) REFERENCES users(id),
 CONSTRAINT fk_loan_returner FOREIGN KEY(returned_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE return_records (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 loan_id INT UNSIGNED NOT NULL UNIQUE,
 returned_by INT UNSIGNED NOT NULL,
 returned_at DATE NOT NULL,
 condition_label ENUM('Good','Fair','Needs inspection','Damaged') NOT NULL,
 notes TEXT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_return_loan FOREIGN KEY(loan_id) REFERENCES loans(id),
 CONSTRAINT fk_return_user FOREIGN KEY(returned_by) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE activity_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NULL,
 action VARCHAR(160) NOT NULL,
 entity_type VARCHAR(50) NOT NULL,
 entity_id INT UNSIGNED NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_activity_time(created_at),
 CONSTRAINT fk_activity_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Fresh-install staff accounts start inactive and must receive a password through
-- database/set_staff_password.php. No staff password is shipped in SQL.
INSERT INTO users(id,full_name,email,password_hash,role,status) VALUES
(7,'Fatima Alshanfak','fatima@careshar.ly','!DISABLED_REQUIRES_SETUP!','Administrator','Inactive'),
(8,'Hamza Alshanfak','Hamza@careshare.ly','!DISABLED_REQUIRES_SETUP!','Staff','Inactive');
INSERT INTO equipment_categories(name,description) VALUES
('Wheelchairs','Manual and transport wheelchairs'),('Walking frames','Folding and fixed walking frames'),('Shower chairs','Seated bathing support'),('Walking sticks','Adjustable walking sticks and canes'),('Other mobility aids','Reusable community mobility equipment');
INSERT INTO equipment(name,category_id,description) VALUES
('Folding wheelchair',1,'Lightweight folding manual chair'),('Transit wheelchair',1,'Compact chair for assisted transport'),('Standard wheelchair',1,'Standard self-propelled wheelchair'),('Folding walking frame',2,'Height-adjustable aluminium frame'),('Wheeled walking frame',2,'Four-wheel rollator with seat'),('Compact shower chair',3,'Height-adjustable shower chair'),('Shower stool',3,'Compact non-slip stool'),('Adjustable walking stick',4,'Height adjustable with ferrule'),('Quad walking stick',4,'Four-point base walking stick'),('Transfer board',5,'Reusable transfer support board'),('Raised toilet seat',5,'Reusable height support seat'),('Forearm crutches',5,'Pair of adjustable forearm crutches');
INSERT INTO equipment_items(equipment_id,asset_code,condition_label,status,notes) VALUES
(1,'CS-WC-001','Good','On Loan',''),(1,'CS-WC-002','Good','Available',''),(1,'CS-WC-003','Fair','Available',''),(2,'CS-WC-004','Good','Available',''),(3,'CS-WC-005','Good','Available',''),(4,'CS-WF-001','Good','On Loan',''),(4,'CS-WF-002','Good','Available',''),(5,'CS-WF-003','Fair','Available',''),(6,'CS-SC-001','Good','Awaiting Inspection','Returned for routine check'),(6,'CS-SC-002','Good','Available',''),(7,'CS-SC-003','Fair','Under Repair','Replace rubber foot'),(8,'CS-WS-001','Good','Available',''),(8,'CS-WS-002','Good','Available',''),(9,'CS-WS-003','Fair','Available',''),(10,'CS-MA-001','Good','Available',''),(11,'CS-MA-002','Good','Available',''),(12,'CS-MA-003','Good','Available',''),(12,'CS-MA-004','Good','On Loan','');
INSERT INTO borrowers(full_name,phone,email,address,notes,status,registered_at) VALUES
('Taylor Brooks','+1 202 555 0101','taylor@example.test','18 Maple Lane','Prefers phone contact','Active','2026-05-20'),('Jordan Reed','+1 202 555 0102','jordan@example.test','4 Willow Court','','Active','2026-06-11'),('Casey Lane','+1 202 555 0103','casey@example.test',NULL,'','Active','2026-07-02'),('Riley Quinn','+1 202 555 0104',NULL,NULL,'','Active','2026-08-17'),('Avery Chen','+1 202 555 0105','avery@example.test','7 Birch Road','','Active','2026-09-09'),('Sam Rivera','+1 202 555 0106',NULL,NULL,'','Active','2026-10-02');
INSERT INTO loan_requests(id,borrower_id,category_id,quantity,start_date,due_date,notes,status,created_by,reviewed_by,created_at,reviewed_at) VALUES
(1,1,1,1,'2026-10-15','2026-11-15','Collection arranged','Issued',8,7,'2026-10-12 10:00:00','2026-10-13 12:00:00'),
(2,2,2,1,'2026-10-25','2026-11-18','','Issued',8,7,'2026-10-22 09:00:00','2026-10-23 12:00:00'),
(3,3,4,1,'2026-11-10','2026-11-24','Would prefer adjustable height','Pending',8,NULL,'2026-11-02 14:00:00',NULL),
(4,4,3,1,'2026-10-01','2026-10-20','','Issued',8,7,'2026-09-27 14:00:00','2026-09-28 10:00:00'),
(5,5,5,1,'2026-10-04','2026-10-18','','Issued',8,7,'2026-09-30 12:00:00','2026-10-01 11:00:00'),
(6,6,1,1,'2026-11-10','2026-11-30','','Approved',8,7,'2026-11-03 15:00:00','2026-11-04 09:00:00');
INSERT INTO request_items(request_id,category_id) VALUES(1,1),(2,2),(3,4),(4,3),(5,5),(6,1);
INSERT INTO loans(id,request_id,borrower_id,item_id,issued_by,issued_at,due_date,returned_at,return_condition,return_notes,returned_by) VALUES
(1,1,1,1,8,'2026-10-15 09:00:00','2026-11-15',NULL,NULL,NULL,NULL),
(2,2,2,6,8,'2026-10-25 11:00:00','2026-11-18',NULL,NULL,NULL,NULL),
(3,4,4,9,8,'2026-10-01 09:30:00','2026-10-20','2026-10-21','Good','Routine check completed',7),
(4,5,5,18,8,'2026-10-04 10:00:00','2026-10-18',NULL,NULL,NULL,NULL);
INSERT INTO return_records(loan_id,returned_by,returned_at,condition_label,notes) VALUES(3,7,'2026-10-21','Good','Routine check completed');
INSERT INTO activity_logs(user_id,action,entity_type,entity_id,created_at) VALUES
(7,'Approved loan request','request',6,'2026-11-04 09:00:00'),(8,'Registered borrower','borrower',6,'2026-11-03 15:00:00'),(7,'Processed equipment return','loan',3,'2026-10-21 10:00:00'),(8,'Issued equipment','request',2,'2026-10-25 11:00:00');
