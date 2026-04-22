-- M-GRID Notifications & Communication module
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS announcements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  created_by_admin_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  target_scope ENUM('all','tier','users') NOT NULL DEFAULT 'all',
  target_tier VARCHAR(120) NULL,
  status ENUM('draft','sent','cancelled') NOT NULL DEFAULT 'draft',
  recipient_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_announcements_status (status),
  KEY idx_announcements_created (created_at),
  CONSTRAINT fk_announcements_admin FOREIGN KEY (created_by_admin_id) REFERENCES admins (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS announcement_targets (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  announcement_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_announcement_user (announcement_id, user_id),
  KEY idx_at_ann (announcement_id),
  KEY idx_at_user (user_id),
  CONSTRAINT fk_at_ann FOREIGN KEY (announcement_id) REFERENCES announcements (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_at_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type ENUM('info','success','warning','alert') NOT NULL DEFAULT 'info',
  source_module VARCHAR(64) NOT NULL DEFAULT 'system',
  related_record_id BIGINT UNSIGNED NULL,
  action_url VARCHAR(500) NULL,
  announcement_id INT UNSIGNED NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  read_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notifications_user (user_id, is_read, created_at),
  KEY idx_notifications_source (source_module, related_record_id),
  KEY idx_notifications_ann (announcement_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notifications_ann FOREIGN KEY (announcement_id) REFERENCES announcements (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_delivery_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  announcement_id INT UNSIGNED NULL,
  notification_id BIGINT UNSIGNED NULL,
  user_id INT UNSIGNED NOT NULL,
  channel ENUM('in_app','email','sms') NOT NULL DEFAULT 'in_app',
  meta VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ndl_ann (announcement_id),
  KEY idx_ndl_notif (notification_id),
  KEY idx_ndl_user (user_id),
  CONSTRAINT fk_ndl_ann FOREIGN KEY (announcement_id) REFERENCES announcements (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ndl_notif FOREIGN KEY (notification_id) REFERENCES notifications (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_ndl_user FOREIGN KEY (user_id) REFERENCES users (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
