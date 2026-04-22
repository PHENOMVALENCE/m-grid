-- M-GRID M-FUND module schema + seed
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS mfund_settings (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_mfund_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_reference_counters (
  year SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
  last_number INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_applications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  m_id VARCHAR(32) NOT NULL,
  reference_number VARCHAR(40) NOT NULL,
  application_type ENUM('loan','grant','support') NOT NULL DEFAULT 'loan',
  requested_amount DECIMAL(14,2) NOT NULL,
  purpose VARCHAR(255) NOT NULL,
  business_name VARCHAR(200) NOT NULL,
  business_sector VARCHAR(120) NOT NULL,
  monthly_revenue_estimate DECIMAL(14,2) NULL,
  repayment_capacity DECIMAL(14,2) NULL,
  proposed_repayment_period INT UNSIGNED NULL,
  business_description TEXT NULL,
  request_reason TEXT NULL,
  supporting_notes TEXT NULL,
  supporting_document_path VARCHAR(500) NULL,
  supporting_document_name VARCHAR(255) NULL,
  status ENUM('draft','submitted','under_review','more_info_requested','approved','rejected','disbursed','active_repayment','completed','defaulted','cancelled') NOT NULL DEFAULT 'submitted',
  current_admin_remark TEXT NULL,
  submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_funding_apps_user (user_id),
  KEY idx_funding_apps_status (status),
  KEY idx_funding_apps_submitted (submitted_at),
  KEY idx_funding_apps_type (application_type),
  UNIQUE KEY uq_funding_ref (reference_number),
  CONSTRAINT fk_funding_apps_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  application_id BIGINT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(80) NOT NULL,
  previous_status VARCHAR(80) NULL,
  new_status VARCHAR(80) NULL,
  remarks TEXT NULL,
  approved_amount DECIMAL(14,2) NULL,
  interest_rate DECIMAL(8,4) NULL,
  repayment_duration_months INT UNSIGNED NULL,
  repayment_start_date DATE NULL,
  funding_partner_name VARCHAR(200) NULL,
  action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_funding_reviews_app (application_id),
  KEY idx_funding_reviews_admin (admin_id),
  CONSTRAINT fk_funding_reviews_app FOREIGN KEY (application_id) REFERENCES funding_applications (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_funding_reviews_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_disbursements (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  application_id BIGINT UNSIGNED NOT NULL,
  disbursed_amount DECIMAL(14,2) NOT NULL,
  disbursement_date DATE NOT NULL,
  disbursement_method VARCHAR(120) NOT NULL,
  reference_note VARCHAR(255) NULL,
  recorded_by_admin_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_funding_disb_app (application_id),
  CONSTRAINT fk_funding_disb_app FOREIGN KEY (application_id) REFERENCES funding_applications (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_funding_disb_admin FOREIGN KEY (recorded_by_admin_id) REFERENCES admins (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_repayment_schedules (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  application_id BIGINT UNSIGNED NOT NULL,
  installment_number INT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  expected_amount DECIMAL(14,2) NOT NULL,
  paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  status ENUM('pending','paid','overdue','partial') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_funding_repay_sched_app (application_id),
  KEY idx_funding_repay_sched_due (due_date),
  UNIQUE KEY uq_funding_sched_installment (application_id, installment_number),
  CONSTRAINT fk_funding_sched_app FOREIGN KEY (application_id) REFERENCES funding_applications (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_repayment_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  application_id BIGINT UNSIGNED NOT NULL,
  schedule_id BIGINT UNSIGNED NULL,
  amount_paid DECIMAL(14,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_method VARCHAR(120) NOT NULL,
  reference_note VARCHAR(255) NULL,
  recorded_by_admin_id INT UNSIGNED NOT NULL,
  remarks TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_funding_repay_logs_app (application_id),
  KEY idx_funding_repay_logs_sched (schedule_id),
  CONSTRAINT fk_funding_repay_logs_app FOREIGN KEY (application_id) REFERENCES funding_applications (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_funding_repay_logs_sched FOREIGN KEY (schedule_id) REFERENCES funding_repayment_schedules (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_funding_repay_logs_admin FOREIGN KEY (recorded_by_admin_id) REFERENCES admins (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS funding_status_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  application_id BIGINT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  user_id INT UNSIGNED NULL,
  old_status VARCHAR(80) NULL,
  new_status VARCHAR(80) NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_funding_status_logs_app (application_id),
  KEY idx_funding_status_logs_created (created_at),
  CONSTRAINT fk_funding_status_logs_app FOREIGN KEY (application_id) REFERENCES funding_applications (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_funding_status_logs_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_funding_status_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO mfund_settings (setting_key, setting_value, description) VALUES
('minimum_mscore', '50', 'Minimum M-SCORE required to apply'),
('minimum_profile_completion', '60', 'Minimum profile completion percentage'),
('allow_multiple_open_applications', '0', '1=yes, 0=no'),
('min_funding_amount', '50000', 'Minimum requested amount'),
('max_funding_amount', '20000000', 'Maximum requested amount'),
('required_verified_docs_count', '2', 'Minimum verified documents required')
ON DUPLICATE KEY UPDATE
  setting_value = VALUES(setting_value),
  description = VALUES(description);

INSERT INTO funding_reference_counters (year, last_number)
VALUES (YEAR(CURDATE()), 0)
ON DUPLICATE KEY UPDATE last_number = last_number;
