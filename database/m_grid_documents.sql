-- M-GRID Document Management + Admin Verification module
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS document_types (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(80) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_document_types_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  m_id VARCHAR(32) NOT NULL,
  document_type_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  original_file_name VARCHAR(255) NOT NULL,
  stored_file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(500) NOT NULL,
  file_extension VARCHAR(10) NOT NULL,
  file_size INT UNSIGNED NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  version_number INT UNSIGNED NOT NULL DEFAULT 1,
  parent_document_id BIGINT UNSIGNED NULL,
  status ENUM('pending','verified','rejected','resubmission_requested') NOT NULL DEFAULT 'pending',
  admin_remark TEXT NULL,
  reviewed_by INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_user_documents_user (user_id),
  KEY idx_user_documents_status (status),
  KEY idx_user_documents_uploaded (uploaded_at),
  KEY idx_user_documents_type (document_type_id),
  KEY idx_user_documents_mid (m_id),
  CONSTRAINT fk_user_documents_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_documents_type FOREIGN KEY (document_type_id) REFERENCES document_types (id)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_user_documents_parent FOREIGN KEY (parent_document_id) REFERENCES user_documents (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_user_documents_reviewer FOREIGN KEY (reviewed_by) REFERENCES admins (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_verification_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  document_id BIGINT UNSIGNED NOT NULL,
  admin_id INT UNSIGNED NULL,
  action ENUM('uploaded','verified','rejected','resubmission_requested','reuploaded') NOT NULL,
  remark TEXT NULL,
  action_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_doc_logs_document (document_id),
  KEY idx_doc_logs_admin (admin_id),
  KEY idx_doc_logs_action_at (action_at),
  CONSTRAINT fk_doc_logs_document FOREIGN KEY (document_id) REFERENCES user_documents (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_doc_logs_admin FOREIGN KEY (admin_id) REFERENCES admins (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO document_types (name, slug, is_active) VALUES
('National ID', 'national_id', 1),
('Business License', 'business_license', 1),
('BRELA Certificate', 'brela_certificate', 1),
('TRA/TIN Certificate', 'tra_tin_certificate', 1),
('Bank Statement', 'bank_statement', 1),
('Training Certificate', 'training_certificate', 1),
('Other Supporting Document', 'other_supporting', 1)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  is_active = VALUES(is_active);
