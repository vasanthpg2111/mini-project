-- College Feedback System (MySQL)
-- Create DB (optional):
--   CREATE DATABASE college_feedback CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE college_feedback;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS admins (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_admin_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subjects (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_subject_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faculty (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  department VARCHAR(120) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feedback (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_name VARCHAR(120) NOT NULL,
  student_id VARCHAR(40) NOT NULL,
  student_email VARCHAR(190) NULL,
  department VARCHAR(120) NULL,
  year_of_study VARCHAR(40) NULL,
  target_type ENUM('subject','faculty') NOT NULL,
  subject_id INT UNSIGNED NULL,
  faculty_id INT UNSIGNED NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comments TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_feedback_created_at (created_at),
  KEY idx_feedback_target_type (target_type),
  KEY idx_feedback_subject (subject_id),
  KEY idx_feedback_faculty (faculty_id),
  CONSTRAINT fk_feedback_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_feedback_faculty FOREIGN KEY (faculty_id) REFERENCES faculty(id) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_feedback_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed minimal lookup data
INSERT IGNORE INTO subjects (name) VALUES
  ('Mathematics'),
  ('Computer Networks'),
  ('Database Management Systems'),
  ('Operating Systems');

INSERT INTO faculty (name, department) VALUES
  ('Dr. A. Sharma', 'Computer Science'),
  ('Prof. R. Iyer', 'Information Technology')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Default admin:
-- username: admin
-- password: admin123
-- Hash generated via PHP password_hash('admin123', PASSWORD_DEFAULT)
INSERT IGNORE INTO admins (username, password_hash) VALUES
  ('admin', '$2y$10$eqG7fHFiuJrDYSdufkGfMuMB5xvT7CkkLPDvtokVqEiqG2Gg422gG');


