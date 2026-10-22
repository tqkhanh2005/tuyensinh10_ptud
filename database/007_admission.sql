-- Batch 7: Admission calculation, ranking, quota, aspirations, results, enrollment confirmation
-- Dependency: Batch 1 + 3 + 4 + 5 + 6
USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `candidate_priority_points` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `priority_group` VARCHAR(100) NULL,
  `priority_points` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_priority_candidate_period` (`admission_period_id`,`candidate_id`),
  CONSTRAINT `fk_priority_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_priority_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admission_runs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `status` ENUM('draft','calculated','blocked','finalized') NOT NULL DEFAULT 'draft',
  `started_by_user_id` BIGINT UNSIGNED NOT NULL,
  `calculated_at` DATETIME NULL,
  `finalized_at` DATETIME NULL,
  `blocked_reason` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admission_run_period` (`admission_period_id`,`status`),
  CONSTRAINT `fk_admission_run_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_admission_run_user` FOREIGN KEY (`started_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admission_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_run_id` BIGINT UNSIGNED NOT NULL,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `aspiration_id` BIGINT UNSIGNED NULL,
  `school_id` INT UNSIGNED NULL,
  `priority_order` INT UNSIGNED NULL,
  `score_total` DECIMAL(8,2) NOT NULL,
  `priority_points` DECIMAL(6,2) NOT NULL DEFAULT 0,
  `admission_score` DECIMAL(8,2) NOT NULL,
  `rank_no` INT UNSIGNED NULL,
  `result_status` ENUM('admitted','not_admitted','unprocessed') NOT NULL DEFAULT 'unprocessed',
  `enrollment_status` ENUM('pending','confirmed') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admission_result_run_candidate_aspiration` (`admission_run_id`,`candidate_id`,`aspiration_id`),
  KEY `idx_admission_result_school` (`admission_run_id`,`school_id`,`priority_order`,`admission_score`),
  KEY `idx_admission_result_candidate_period` (`admission_period_id`,`candidate_id`),
  CONSTRAINT `fk_admission_result_run` FOREIGN KEY (`admission_run_id`) REFERENCES `admission_runs` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_admission_result_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_admission_result_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_admission_result_aspiration` FOREIGN KEY (`aspiration_id`) REFERENCES `aspirations` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_admission_result_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enrollment_confirmations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_result_id` BIGINT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `school_id` INT UNSIGNED NOT NULL,
  `confirmed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_enrollment_result` (`admission_result_id`),
  UNIQUE KEY `uq_enrollment_candidate` (`candidate_id`),
  CONSTRAINT `fk_enrollment_result` FOREIGN KEY (`admission_result_id`) REFERENCES `admission_results` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_enrollment_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_enrollment_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`code`,`name`) VALUES
('admission.manage','Xét tuyển thí sinh'),
('admission.view','Xem kết quả xét tuyển'),
('enrollment.confirm','Xác nhận nhập học')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.code IN ('admin','so_gdtd') AND p.code='admission.manage'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.code='candidate' AND p.code IN ('admission.view','enrollment.confirm')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

ALTER TABLE `admission_periods`
  ADD COLUMN `enrollment_start` DATETIME NULL,
  ADD COLUMN `enrollment_end` DATETIME NULL;

ALTER TABLE `candidates`
  ADD COLUMN `admission_status` ENUM('not_processed','admitted','enrolled') NOT NULL DEFAULT 'not_processed';

-- [MISSING SPECIFICATION]
-- 1) Exact priority-group definitions/points are not supplied; this migration stores
--    the configured value but does not invent groups or point values.
-- 2) Tie-breaking at the quota boundary is not specified. The service blocks a run
--    when an unresolved tie would change the admitted set.
-- 3) The source does not define an explicit publication/lock state for admission;
--    publication remains Batch 8.
-- 4) The source mentions a confirmation period but the existing admission_periods
--    schema has only registration dates. Batch 7 therefore requires explicit
--    enrollment_start/enrollment_end columns to be configured before confirmation.

-- Rollback:
-- DROP TABLE IF EXISTS enrollment_confirmations;
-- DROP TABLE IF EXISTS admission_results;
-- DROP TABLE IF EXISTS admission_runs;
-- DROP TABLE IF EXISTS candidate_priority_points;
