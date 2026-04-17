-- Migration: separate admin accounts from users (adds admins + admin IDs).
-- Apply this to existing databases that were created before admins separation.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS admin_id_counters (
  year SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id VARCHAR(32) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_admins_admin_id (admin_id),
  UNIQUE KEY uq_admins_email (email),
  KEY idx_admins_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO admin_id_counters (year, last_number) VALUES (YEAR(CURDATE()), 0);

-- Drop old role column from users if it exists (user-only table now).
SET @drop_role := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'role'
    ),
    'ALTER TABLE users DROP COLUMN role',
    'SELECT 1'
  )
);
PREPARE stmt_role FROM @drop_role;
EXECUTE stmt_role;
DEALLOCATE PREPARE stmt_role;

-- Ensure admin review FK in user_profiles points to admins.
SET @drop_old_fk := (
  SELECT IF(
    EXISTS(
      SELECT 1
      FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND CONSTRAINT_NAME = 'fk_profile_reviewer'
        AND TABLE_NAME = 'user_profiles'
    ),
    'ALTER TABLE user_profiles DROP FOREIGN KEY fk_profile_reviewer',
    'SELECT 1'
  )
);
PREPARE stmt_drop_fk FROM @drop_old_fk;
EXECUTE stmt_drop_fk;
DEALLOCATE PREPARE stmt_drop_fk;

ALTER TABLE user_profiles
  ADD CONSTRAINT fk_profile_reviewer FOREIGN KEY (national_id_reviewed_by) REFERENCES admins (id)
  ON DELETE SET NULL ON UPDATE CASCADE;

-- Rebuild admin_logs so FK points to admins.
DROP TABLE IF EXISTS admin_logs;
CREATE TABLE admin_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  target_user_id INT UNSIGNED NULL,
  action_type VARCHAR(64) NOT NULL,
  description TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_admin_logs_admin (admin_id),
  KEY idx_admin_logs_target (target_user_id),
  CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_admin_logs_target FOREIGN KEY (target_user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Move existing admin from users (if present) into admins.
INSERT IGNORE INTO admins (admin_id, full_name, email, password_hash, status)
SELECT CONCAT('ADM-', YEAR(CURDATE()), '-0001'), full_name, email, password_hash, 'active'
FROM users
WHERE email = 'admin@m-grid.local'
LIMIT 1;

-- Ensure at least one admin exists.
INSERT IGNORE INTO admins (admin_id, full_name, email, password_hash, status)
VALUES (
  CONCAT('ADM-', YEAR(CURDATE()), '-0001'),
  'System Administrator',
  'admin@m-grid.local',
  '$2y$10$1DqrPWYe3NBEANit4ZYd4.4u50bsmy94CfUN99wlwtsmqxmJH6WKS',
  'active'
);

SET FOREIGN_KEY_CHECKS = 1;
