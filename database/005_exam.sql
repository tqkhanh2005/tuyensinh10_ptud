-- Batch 5: Exam
-- Dependency: Batch 1 + Batch 2 + Batch 3 + Batch 4
USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `exam_sites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `school_id` INT UNSIGNED NULL,
  `site_code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `address` VARCHAR(255) NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft','ready','assigned','completed') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_site_period_code` (`admission_period_id`,`site_code`),
  KEY `idx_exam_site_period` (`admission_period_id`),
  CONSTRAINT `fk_exam_site_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_site_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_rooms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_site_id` INT UNSIGNED NOT NULL,
  `room_code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('draft','ready','assigned','completed') NOT NULL DEFAULT 'draft',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_room_site_code` (`exam_site_id`,`room_code`),
  CONSTRAINT `fk_exam_room_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `subject_code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `exam_date` DATE NULL,
  `start_time` TIME NULL,
  `end_time` TIME NULL,
  `max_score` DECIMAL(5,2) NULL,
  `component_count` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('draft','scheduled','completed') NOT NULL DEFAULT 'draft',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_subject_period_code` (`admission_period_id`,`subject_code`),
  CONSTRAINT `fk_exam_subject_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_allocations` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `exam_site_id` INT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_allocation_candidate_period` (`admission_period_id`,`candidate_id`),
  KEY `idx_exam_allocation_site` (`exam_site_id`),
  CONSTRAINT `fk_exam_allocation_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_allocation_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_allocation_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `exam_site_id` INT UNSIGNED NOT NULL,
  `exam_room_id` INT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_room_assignment_candidate_period` (`admission_period_id`,`candidate_id`),
  KEY `idx_room_assignment_room` (`exam_room_id`),
  CONSTRAINT `fk_room_assignment_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_room_assignment_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_room_assignment_site` FOREIGN KEY (`exam_site_id`) REFERENCES `exam_sites` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_room_assignment_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_invigilator_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_room_id` INT UNSIGNED NOT NULL,
  `exam_subject_id` INT UNSIGNED NOT NULL,
  `staff_id` BIGINT UNSIGNED NOT NULL,
  `position` ENUM('invigilator_1','invigilator_2') NOT NULL,
  `status` ENUM('assigned','replaced','cancelled') NOT NULL DEFAULT 'assigned',
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invigilator_room_subject_position` (`exam_room_id`,`exam_subject_id`,`position`),
  KEY `idx_invigilator_staff` (`staff_id`),
  CONSTRAINT `fk_invigilator_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_invigilator_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_invigilator_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_papers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `candidate_id` BIGINT UNSIGNED NOT NULL,
  `exam_subject_id` INT UNSIGNED NOT NULL,
  `exam_room_id` INT UNSIGNED NOT NULL,
  `attendance_status` ENUM('present','present_penalty','absent') NULL,
  `penalty_percent` TINYINT UNSIGNED NULL,
  `anonymous_code` VARCHAR(50) NULL,
  `paper_status` ENUM('waiting','coded','scored','matched') NOT NULL DEFAULT 'waiting',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_paper_candidate_subject` (`admission_period_id`,`candidate_id`,`exam_subject_id`),
  UNIQUE KEY `uq_exam_paper_period_code` (`admission_period_id`,`anonymous_code`),
  KEY `idx_exam_paper_subject_room` (`exam_subject_id`,`exam_room_id`),
  CONSTRAINT `fk_exam_paper_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_paper_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_paper_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_paper_room` FOREIGN KEY (`exam_room_id`) REFERENCES `exam_rooms` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scores` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_paper_id` BIGINT UNSIGNED NOT NULL,
  `anonymous_code` VARCHAR(50) NOT NULL,
  `score_1` DECIMAL(5,2) NULL,
  `score_2` DECIMAL(5,2) NULL,
  `total_score` DECIMAL(5,2) NOT NULL,
  `entered_by` BIGINT UNSIGNED NOT NULL,
  `entered_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_score_paper` (`exam_paper_id`),
  UNIQUE KEY `uq_score_code` (`anonymous_code`),
  CONSTRAINT `fk_score_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_score_user` FOREIGN KEY (`entered_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_import_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `exam_subject_id` INT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `valid_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `invalid_rows` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('previewed','saved','failed') NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_exam_import_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_import_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`code`,`name`) VALUES
('exam.manage','Quản lý điểm thi/phòng thi/môn thi'),
('exam.allocate','Phân bố và sắp xếp thí sinh'),
('invigilation.assign','Phân công cán bộ coi thi'),
('paper.manage','Quản lý phách'),
('score.enter','Nhập điểm')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.code IN ('admin','so_gdtd')
AND p.code IN ('exam.manage','exam.allocate','invigilation.assign','paper.manage','score.enter')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

-- Rollback:
-- DROP TABLE IF EXISTS exam_import_logs;
-- DROP TABLE IF EXISTS scores;
-- DROP TABLE IF EXISTS exam_papers;
-- DROP TABLE IF EXISTS exam_invigilator_assignments;
-- DROP TABLE IF EXISTS room_assignments;
-- DROP TABLE IF EXISTS exam_allocations;
-- DROP TABLE IF EXISTS exam_subjects;
-- DROP TABLE IF EXISTS exam_rooms;
-- DROP TABLE IF EXISTS exam_sites;
