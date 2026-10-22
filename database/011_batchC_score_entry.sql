-- Batch C: UC01 + UC02 foundation / import staging
-- Depends on 001..010. Does not modify score publication triggers.
USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `exam_score_import_batches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `exam_subject_id` INT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `valid_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('previewed','saved','failed') NOT NULL DEFAULT 'previewed',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_score_import_batch_scope` (`admission_period_id`,`exam_subject_id`,`user_id`),
  CONSTRAINT `fk_score_import_batch_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_score_import_batch_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_score_import_batch_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_score_import_rows` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id` BIGINT UNSIGNED NOT NULL,
  `row_number` INT UNSIGNED NOT NULL,
  `anonymous_code` VARCHAR(50) NULL,
  `score_1` DECIMAL(5,2) NULL,
  `score_2` DECIMAL(5,2) NULL,
  `total_score` DECIMAL(5,2) NULL,
  `error_message` TEXT NULL,
  `is_valid` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_score_import_batch_row` (`batch_id`,`row_number`),
  KEY `idx_score_import_row_valid` (`batch_id`,`is_valid`),
  CONSTRAINT `fk_score_import_row_batch` FOREIGN KEY (`batch_id`) REFERENCES `exam_score_import_batches` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Existing exam_papers unique keys already provide the required duplicate protection.

-- [MISSING SPECIFICATION]
-- The requirements do not define one official XLSX/XLS MIME/extension/template format.
-- The application therefore validates the actual parsed structure instead of trusting filename alone.
-- No score-publication trigger is changed here.
