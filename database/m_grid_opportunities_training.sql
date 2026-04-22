-- M-GRID Opportunities & Training module
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS opportunity_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 10,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_opp_cat_slug (slug),
  KEY idx_opp_cat_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opportunities (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  opportunity_type ENUM(
    'grant','job','internship','fellowship','accelerator','tender','webinar','workshop','training_program','other'
  ) NOT NULL DEFAULT 'other',
  provider_name VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  requirements TEXT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  deadline DATE NULL,
  location VARCHAR(240) NULL,
  format ENUM('physical','online','hybrid','unspecified') NOT NULL DEFAULT 'unspecified',
  external_link VARCHAR(500) NULL,
  application_method TEXT NULL,
  apply_internal TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_opportunities_slug (slug),
  KEY idx_opp_type (opportunity_type),
  KEY idx_opp_deadline (deadline),
  KEY idx_opp_active_arch (is_active, is_archived),
  CONSTRAINT fk_opportunities_category FOREIGN KEY (category_id) REFERENCES opportunity_categories (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_programs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(220) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  training_type ENUM('course','workshop','webinar','cohort','mentorship','certification','other') NOT NULL DEFAULT 'course',
  provider_name VARCHAR(200) NOT NULL,
  trainer_name VARCHAR(200) NULL,
  description TEXT NOT NULL,
  eligibility TEXT NULL,
  schedule_start DATETIME NULL,
  schedule_end DATETIME NULL,
  duration_label VARCHAR(120) NULL,
  location VARCHAR(240) NULL,
  format ENUM('physical','online','hybrid','unspecified') NOT NULL DEFAULT 'online',
  external_link VARCHAR(500) NULL,
  register_internal TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_training_programs_slug (slug),
  KEY idx_training_schedule (schedule_start, schedule_end),
  KEY idx_training_active_arch (is_active, is_archived)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opportunity_applications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  opportunity_id INT UNSIGNED NOT NULL,
  status ENUM('submitted','under_review','shortlisted','accepted','rejected','withdrawn') NOT NULL DEFAULT 'submitted',
  completion_status ENUM('n_a','in_progress','completed','cancelled') NOT NULL DEFAULT 'n_a',
  certificate_status ENUM('n_a','none','issued','verified') NOT NULL DEFAULT 'n_a',
  user_message TEXT NULL,
  admin_notes TEXT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_opp_app_user (user_id),
  KEY idx_opp_app_opp (opportunity_id),
  KEY idx_opp_app_status (status),
  CONSTRAINT fk_opp_app_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_opp_app_opp FOREIGN KEY (opportunity_id) REFERENCES opportunities (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS training_registrations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  training_program_id INT UNSIGNED NOT NULL,
  status ENUM('pending','approved','rejected','waitlisted','cancelled') NOT NULL DEFAULT 'pending',
  participation_status ENUM('registered','attended','completed','no_show','excused') NOT NULL DEFAULT 'registered',
  certificate_status ENUM('none','issued','pending_verification','verified','rejected') NOT NULL DEFAULT 'none',
  certificate_document_id BIGINT UNSIGNED NULL,
  user_training_record_id BIGINT UNSIGNED NULL,
  user_message TEXT NULL,
  admin_notes TEXT NULL,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_train_reg_user (user_id),
  KEY idx_train_reg_prog (training_program_id),
  KEY idx_train_reg_status (status, participation_status),
  CONSTRAINT fk_train_reg_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_train_reg_prog FOREIGN KEY (training_program_id) REFERENCES training_programs (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- certificate_document_id / user_training_record_id are logical links to user_documents / user_training_records
-- (no FK so this module can load before document/M-SCORE tables if needed).

CREATE TABLE IF NOT EXISTS training_completion_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  training_registration_id BIGINT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  participation_from VARCHAR(40) NULL,
  participation_to VARCHAR(40) NULL,
  certificate_from VARCHAR(40) NULL,
  certificate_to VARCHAR(40) NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_train_completion_reg (training_registration_id),
  KEY idx_train_completion_created (created_at),
  CONSTRAINT fk_train_completion_reg FOREIGN KEY (training_registration_id) REFERENCES training_registrations (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_train_completion_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO opportunity_categories (name, slug, sort_order, is_active) VALUES
('Grants & Funding', 'grants_funding', 10, 1),
('Employment', 'employment', 20, 1),
('Learning & Events', 'learning_events', 30, 1),
('Enterprise & Tenders', 'enterprise_tenders', 40, 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order), is_active = VALUES(is_active);

INSERT INTO opportunities (
  category_id, title, slug, opportunity_type, provider_name, description, requirements,
  start_date, end_date, deadline, location, format, external_link, application_method, apply_internal, is_active, is_archived
) VALUES
(
  (SELECT id FROM opportunity_categories WHERE slug = 'grants_funding' LIMIT 1),
  'Women SME Innovation Grant (sample)',
  'women-sme-innovation-grant-sample',
  'grant',
  'Malkia Grid Partners',
  'Illustrative listing for women-led SMEs. Replace with live programme copy.',
  'Registered business, pitch deck, M-ID in good standing.',
  CURDATE(),
  DATE_ADD(CURDATE(), INTERVAL 90 DAY),
  DATE_ADD(CURDATE(), INTERVAL 45 DAY),
  'Dar es Salaam / hybrid',
  'hybrid',
  NULL,
  'Apply through M-GRID using the Apply button. Shortlisted applicants may be invited to interview.',
  1,
  1,
  0
),
(
  (SELECT id FROM opportunity_categories WHERE slug = 'employment' LIMIT 1),
  'Remote Customer Success Internship',
  'remote-customer-success-internship-sample',
  'internship',
  'Example Tech Ltd',
  'Sample internship listing for platform testing.',
  'Fluent English or Swahili, stable internet.',
  NULL,
  NULL,
  DATE_ADD(CURDATE(), INTERVAL 30 DAY),
  'Remote',
  'online',
  'https://example.org/apply',
  'External application link — use Apply to open the partner site.',
  0,
  1,
  0
)
ON DUPLICATE KEY UPDATE title = VALUES(title), is_active = VALUES(is_active);

INSERT INTO training_programs (
  title, slug, training_type, provider_name, trainer_name, description, eligibility,
  schedule_start, schedule_end, duration_label, location, format, register_internal, is_active, is_archived
) VALUES
(
  'Financial Literacy for Women Entrepreneurs',
  'financial-literacy-women-entrepreneurs-sample',
  'workshop',
  'Malkia Grid Academy',
  'Facilitator TBD',
  'Sample cohort covering budgeting, cash flow, and basic compliance.',
  'Open to all active M-GRID members.',
  DATE_ADD(NOW(), INTERVAL 14 DAY),
  DATE_ADD(DATE_ADD(NOW(), INTERVAL 14 DAY), INTERVAL 5 HOUR),
  '1 day',
  'Online',
  'online',
  1,
  1,
  0
)
ON DUPLICATE KEY UPDATE title = VALUES(title), is_active = VALUES(is_active);
