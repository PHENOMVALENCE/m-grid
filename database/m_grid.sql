-- Malkia Grid — Phase 1 schema (MySQL 8+ / MariaDB 10.3+)
-- Charset: utf8mb4 for full Unicode support (Swahili, names, etc.)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS admin_logs;
DROP TABLE IF EXISTS m_scores;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS admin_id_counters;
DROP TABLE IF EXISTS m_id_counters;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------------
-- M-ID sequence per calendar year (concurrency-safe allocation in PHP)
-- ---------------------------------------------------------------------------
CREATE TABLE m_id_counters (
  year SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Admin-ID sequence per year
-- ---------------------------------------------------------------------------
CREATE TABLE admin_id_counters (
  year SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Internal admin accounts (separate from member identities / M-ID)
-- ---------------------------------------------------------------------------
CREATE TABLE admins (
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

-- ---------------------------------------------------------------------------
-- Core user account (authentication + M-ID)
-- ---------------------------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  m_id VARCHAR(32) NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  phone VARCHAR(32) NOT NULL,
  email VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  status ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'pending',
  preferred_language VARCHAR(16) NOT NULL DEFAULT 'en',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_m_id (m_id),
  UNIQUE KEY uq_users_phone (phone),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_status (status),
  KEY idx_users_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Extended profile (M-Profile) — separate from auth for cleaner evolution
-- ---------------------------------------------------------------------------
CREATE TABLE user_profiles (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  region VARCHAR(120) NOT NULL DEFAULT '',
  date_of_birth DATE NULL,
  age_range VARCHAR(64) NULL,
  business_status VARCHAR(64) NOT NULL DEFAULT '',
  bio TEXT NULL,
  profile_photo VARCHAR(255) NULL,
  national_id_photo VARCHAR(255) NULL,
  national_id_status ENUM('not_submitted', 'pending', 'approved', 'rejected') NOT NULL DEFAULT 'not_submitted',
  national_id_notes VARCHAR(255) NULL,
  national_id_submitted_at DATETIME NULL,
  national_id_reviewed_at DATETIME NULL,
  national_id_reviewed_by INT UNSIGNED NULL,
  profile_completion TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_profile_user (user_id),
  CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_profile_reviewer FOREIGN KEY (national_id_reviewed_by) REFERENCES admins (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- M-Score placeholder (logic added in a later phase)
-- ---------------------------------------------------------------------------
CREATE TABLE m_scores (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  score DECIMAL(6,2) NULL,
  tier VARCHAR(32) NOT NULL DEFAULT 'pending',
  last_calculated_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_m_scores_user (user_id),
  CONSTRAINT fk_m_scores_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Lightweight admin audit trail (view user, status changes, etc.)
-- ---------------------------------------------------------------------------
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

-- ---------------------------------------------------------------------------
-- Seed: counter for current year (adjust year when deploying)
-- ---------------------------------------------------------------------------
INSERT INTO m_id_counters (year, last_number) VALUES (YEAR(CURDATE()), 0);
INSERT INTO admin_id_counters (year, last_number) VALUES (YEAR(CURDATE()), 0);

-- ---------------------------------------------------------------------------
-- Seed: default administrator (CHANGE EMAIL / PASSWORD IN PRODUCTION)
-- Login: admin@m-grid.local  /  Admin@123
-- Admin ID uses ADM-YYYY-0001 and is separate from public M-ID.
-- ---------------------------------------------------------------------------
INSERT INTO admins (admin_id, full_name, email, password_hash, status)
VALUES (
  CONCAT('ADM-', YEAR(CURDATE()), '-0001'),
  'System Administrator',
  'admin@m-grid.local',
  '$2y$10$1DqrPWYe3NBEANit4ZYd4.4u50bsmy94CfUN99wlwtsmqxmJH6WKS',
  'active'
);
