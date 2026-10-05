-- Batch D: UC04 + UC08-UC11
-- Database: tuyen_sinh_lop10
-- Dependencies: 001-011
-- No score-publication trigger changes.

USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `councils` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `council_type` ENUM('invigilation','grading','appeal','question') NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `exam_site_id` INT UNSIGNED NULL,
  `start_at` DATETIME NULL,
  `end_at` DATETIME NULL,
  `location` VARCHAR(255) NULL,
  `status` ENUM('draft','established','cancelled') NOT NULL DEFAULT 'established',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_council_period_type_name` (`admission_period_id`,`council_type`,`name`),
  KEY `idx_council_period_type` (`admission_period_id`,`council_type`),
  KEY `idx_council_site` (`exam_site_id`),
  CONSTRAINT `fk_council_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_council_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `council_subjects` (
  `council_id` BIGINT UNSIGNED NOT NULL,
  `exam_subject_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`council_id`,`exam_subject_id`),
  KEY `idx_council_subject_subject` (`exam_subject_id`),
  CONSTRAINT `fk_council_subject_council` FOREIGN KEY (`council_id`) REFERENCES `councils` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_council_subject_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `council_members` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `council_id` BIGINT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `role_name` VARCHAR(100) NULL,
  `task_name` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_council_member` (`council_id`,`staff_id`),
  KEY `idx_council_member_staff` (`staff_id`),
  CONSTRAINT `fk_council_member_council` FOREIGN KEY (`council_id`) REFERENCES `councils` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_council_member_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_invigilator_assignment_history` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `assignment_id` BIGINT UNSIGNED NOT NULL,
  `exam_room_id` INT UNSIGNED NOT NULL,
  `exam_subject_id` INT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `position` ENUM('invigilator_1','invigilator_2') NOT NULL,
  `event_type` ENUM('assigned','replaced') NOT NULL,
  `recorded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_invigilator_history_assignment` (`assignment_id`),
  KEY `idx_invigilator_history_staff` (`staff_id`),
  CONSTRAINT `fk_invigilator_history_assignment` FOREIGN KEY (`assignment_id`) REFERENCES `exam_invigilator_assignments` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_invigilator_history_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_invigilator_history_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_invigilator_history_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`code`,`name`) VALUES
('council.manage','Thành lập và quản lý các hội đồng kỳ thi')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id
FROM roles r CROSS JOIN permissions p
WHERE r.code IN ('admin','so_gdtd') AND p.code='council.manage'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);
