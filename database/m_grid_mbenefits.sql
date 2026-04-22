-- M-GRID M-BENEFITS module schema + seed
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS benefit_claim_counters (
  year SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mbenefits_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mbenefits_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 10,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_benefit_cat_slug (slug),
  KEY idx_benefit_cat_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_providers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  contact_email VARCHAR(255) NULL,
  website_url VARCHAR(500) NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_benefit_provider_slug (slug),
  KEY idx_benefit_provider_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_offers (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  provider_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  short_description VARCHAR(500) NOT NULL,
  full_description TEXT NULL,
  terms_and_conditions TEXT NULL,
  benefit_type ENUM('discount','credit','voucher','service','other') NOT NULL DEFAULT 'discount',
  value_label VARCHAR(120) NOT NULL,
  value_numeric DECIMAL(12,2) NULL,
  min_mscore DECIMAL(6,2) NOT NULL DEFAULT 0,
  eligible_tier VARCHAR(64) NULL,
  requires_verified_documents TINYINT(1) NOT NULL DEFAULT 0,
  requires_profile_complete_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  allow_repeat_claims TINYINT(1) NOT NULL DEFAULT 0,
  redemption_method TEXT NULL,
  valid_from DATE NOT NULL,
  valid_to DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_benefit_offer_slug (slug),
  KEY idx_benefit_offer_active_dates (is_active, valid_from, valid_to),
  KEY idx_benefit_offer_category (category_id),
  KEY idx_benefit_offer_provider (provider_id),
  CONSTRAINT fk_benefit_offer_category FOREIGN KEY (category_id) REFERENCES benefit_categories (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_benefit_offer_provider FOREIGN KEY (provider_id) REFERENCES benefit_providers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_claims (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  benefit_offer_id INT UNSIGNED NOT NULL,
  claim_reference VARCHAR(40) NOT NULL,
  status ENUM('pending','approved','rejected','redeemed','cancelled') NOT NULL DEFAULT 'pending',
  admin_remarks TEXT NULL,
  user_notes VARCHAR(500) NULL,
  claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_benefit_claims_user (user_id),
  KEY idx_benefit_claims_offer (benefit_offer_id),
  KEY idx_benefit_claims_status (status),
  UNIQUE KEY uq_benefit_claim_ref (claim_reference),
  CONSTRAINT fk_benefit_claims_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_benefit_claims_offer FOREIGN KEY (benefit_offer_id) REFERENCES benefit_offers (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS benefit_claim_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  claim_id BIGINT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  old_status VARCHAR(40) NULL,
  new_status VARCHAR(40) NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_benefit_claim_logs_claim (claim_id),
  KEY idx_benefit_claim_logs_created (created_at),
  CONSTRAINT fk_benefit_claim_logs_claim FOREIGN KEY (claim_id) REFERENCES benefit_claims (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_benefit_claim_logs_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_benefit_claim_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO benefit_claim_counters (year, last_number) VALUES (YEAR(CURDATE()), 0)
ON DUPLICATE KEY UPDATE last_number = last_number;

INSERT INTO mbenefits_settings (setting_key, setting_value, description) VALUES
('default_duplicate_policy', 'one_active_per_offer', 'one_active_per_offer | allow_repeat_if_offer_flag'),
('claim_auto_pending', '1', '1=new claims start as pending')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

INSERT INTO benefit_categories (name, slug, sort_order, is_active) VALUES
('Health & Wellness', 'health_wellness', 10, 1),
('Skills & Learning', 'skills_learning', 20, 1),
('Retail & Services', 'retail_services', 30, 1),
('Financial Perks', 'financial_perks', 40, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

INSERT INTO benefit_providers (name, slug, contact_email, website_url, description, is_active) VALUES
('Malkia Wellness Network', 'malkia_wellness', 'wellness@example.org', 'https://example.org', 'Partner network for member wellness perks.', 1),
('Clouds Learning Hub', 'clouds_learning', 'learning@example.org', 'https://example.org', 'Training and capacity-building partner.', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active);

INSERT INTO benefit_offers (
  category_id, provider_id, title, slug, short_description, full_description, terms_and_conditions,
  benefit_type, value_label, value_numeric, min_mscore, eligible_tier, requires_verified_documents,
  requires_profile_complete_percent, allow_repeat_claims, redemption_method, valid_from, valid_to, is_active
) VALUES
(
  (SELECT id FROM benefit_categories WHERE slug = 'health_wellness' LIMIT 1),
  (SELECT id FROM benefit_providers WHERE slug = 'malkia_wellness' LIMIT 1),
  'Annual Health Screening Voucher',
  'annual-health-screening-voucher',
  'Complimentary basic screening for members with Growth tier or higher.',
  'Full offer description: one screening session per member per calendar year at participating clinics.',
  'Valid ID and M-ID required at redemption. Non-transferable.',
  'voucher',
  '100% covered session',
  NULL,
  50.00,
  'growth',
  1,
  60,
  0,
  'Present your claim reference at partner clinic reception.',
  CURDATE(),
  DATE_ADD(CURDATE(), INTERVAL 365 DAY),
  1
),
(
  (SELECT id FROM benefit_categories WHERE slug = 'skills_learning' LIMIT 1),
  (SELECT id FROM benefit_providers WHERE slug = 'clouds_learning' LIMIT 1),
  'Online Course Access — Leadership Basics',
  'online-course-leadership-basics',
  'Discounted access to a curated leadership course for Emerging+ members.',
  'Self-paced modules with certificate upon completion.',
  'Access expires 90 days after approval. One claim per member.',
  'discount',
  '40% off standard price',
  NULL,
  25.00,
  NULL,
  0,
  40,
  0,
  'You will receive an enrolment link after claim approval.',
  CURDATE(),
  DATE_ADD(CURDATE(), INTERVAL 180 DAY),
  1
)
ON DUPLICATE KEY UPDATE title = VALUES(title), is_active = VALUES(is_active);
