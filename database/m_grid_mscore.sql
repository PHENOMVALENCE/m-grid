-- M-GRID M-SCORE module schema + seed data
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS mscore_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_key VARCHAR(64) NOT NULL,
  category_name VARCHAR(160) NOT NULL,
  max_points DECIMAL(6,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 10,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mscore_settings_key (category_key),
  KEY idx_mscore_settings_active_order (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mscore_score_history (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  m_id VARCHAR(32) NOT NULL,
  total_score DECIMAL(6,2) NOT NULL,
  tier_label VARCHAR(64) NOT NULL,
  breakdown_json JSON NOT NULL,
  calculation_notes TEXT NULL,
  calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mscore_history_user (user_id),
  KEY idx_mscore_history_calculated (calculated_at),
  CONSTRAINT fk_mscore_history_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mscore_category_results (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category_key VARCHAR(64) NOT NULL,
  points_awarded DECIMAL(6,2) NOT NULL,
  max_points DECIMAL(6,2) NOT NULL,
  details_json JSON NULL,
  calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_mscore_cat_user (user_id),
  KEY idx_mscore_cat_key (category_key),
  KEY idx_mscore_cat_calculated (calculated_at),
  CONSTRAINT fk_mscore_cat_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mscore_current_scores (
  user_id INT UNSIGNED NOT NULL PRIMARY KEY,
  m_id VARCHAR(32) NOT NULL,
  total_score DECIMAL(6,2) NOT NULL,
  tier_label VARCHAR(64) NOT NULL,
  readiness_label VARCHAR(120) NOT NULL,
  breakdown_json JSON NOT NULL,
  recommendations_json JSON NOT NULL,
  calculated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_mscore_current_tier (tier_label),
  KEY idx_mscore_current_score (total_score),
  CONSTRAINT fk_mscore_current_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional helper table for banking readiness (if not already existing in your project)
CREATE TABLE IF NOT EXISTS user_financial_profiles (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  bank_name VARCHAR(160) NULL,
  account_name VARCHAR(180) NULL,
  account_number VARCHAR(80) NULL,
  mobile_money_provider VARCHAR(80) NULL,
  mobile_money_number VARCHAR(40) NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_financial_user (user_id),
  CONSTRAINT fk_financial_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional helper table for training/capacity records (if not already existing)
CREATE TABLE IF NOT EXISTS user_training_records (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  training_title VARCHAR(200) NOT NULL,
  provider_name VARCHAR(160) NULL,
  completion_status ENUM('registered','in_progress','completed') NOT NULL DEFAULT 'registered',
  verified_status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  certificate_document_id BIGINT UNSIGNED NULL,
  completed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_training_user (user_id),
  KEY idx_training_status (completion_status, verified_status),
  CONSTRAINT fk_training_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_training_doc FOREIGN KEY (certificate_document_id) REFERENCES user_documents (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mscore_settings (category_key, category_name, max_points, is_active, sort_order) VALUES
('profile_completion', 'Profile Completion', 20.00, 1, 10),
('verified_documents', 'Verified Documents', 30.00, 1, 20),
('banking_readiness', 'Banking / Financial Readiness', 15.00, 1, 30),
('training_capacity', 'Training / Capacity Building', 15.00, 1, 40),
('business_compliance', 'Business Compliance / Formalization', 20.00, 1, 50)
ON DUPLICATE KEY UPDATE
  category_name = VALUES(category_name),
  max_points = VALUES(max_points),
  is_active = VALUES(is_active),
  sort_order = VALUES(sort_order);
