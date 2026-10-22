-- Batch I Phase 1: UC03 appeal grading assignment + initial grader linkage
-- Depends on 001..012. Does not modify score-publication triggers.
USE `tuyen_sinh_lop10`;

-- Existing staff records need an explicit account mapping. NULL is intentional:
-- existing rows cannot be guessed/mapped automatically.
DROP PROCEDURE IF EXISTS `batchI_add_staff_user_link`;
DELIMITER $$
CREATE PROCEDURE `batchI_add_staff_user_link`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'user_id'
  ) THEN
    ALTER TABLE `staff`
      ADD COLUMN `user_id` BIGINT UNSIGNED NULL AFTER `id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND index_name = 'uq_staff_user_id'
  ) THEN
    ALTER TABLE `staff` ADD UNIQUE KEY `uq_staff_user_id` (`user_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE table_schema = DATABASE() AND table_name = 'staff'
      AND constraint_name = 'fk_staff_user' AND constraint_type = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `staff`
      ADD CONSTRAINT `fk_staff_user`
      FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
      ON UPDATE CASCADE ON DELETE SET NULL;
  END IF;
END$$
DELIMITER ;
CALL `batchI_add_staff_user_link`();
DROP PROCEDURE IF EXISTS `batchI_add_staff_user_link`;

CREATE TABLE IF NOT EXISTS `appeal_grader_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `appeal_request_id` BIGINT UNSIGNED NOT NULL,
  `council_member_id` BIGINT UNSIGNED NOT NULL,
  `assigned_by_user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appeal_grader_assignment_request` (`appeal_request_id`),
  KEY `idx_appeal_grader_assignment_member` (`council_member_id`),
  CONSTRAINT `fk_appeal_grader_assignment_request`
    FOREIGN KEY (`appeal_request_id`) REFERENCES `appeal_requests` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_appeal_grader_assignment_member`
    FOREIGN KEY (`council_member_id`) REFERENCES `council_members` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_appeal_grader_assignment_user`
    FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_paper_grader_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `exam_paper_id` BIGINT UNSIGNED NOT NULL,
  `council_member_id` BIGINT UNSIGNED NOT NULL,
  `assigned_by_user_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exam_paper_grader_assignment_paper` (`exam_paper_id`),
  KEY `idx_exam_paper_grader_assignment_member` (`council_member_id`),
  CONSTRAINT `fk_exam_paper_grader_assignment_paper`
    FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_paper_grader_assignment_member`
    FOREIGN KEY (`council_member_id`) REFERENCES `council_members` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_exam_paper_grader_assignment_user`
    FOREIGN KEY (`assigned_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rollback strategy: remove the two new tables, then remove the staff-user FK/index/column
-- only if the project has no dependent data. Protected score-publication triggers are untouched.
