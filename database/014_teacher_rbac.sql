-- Migration 014: Teacher Portal RBAC & School Linkage
-- Idempotent migration for role 'teacher' and staff.school_id foreign key
USE `tuyen_sinh_lop10`;

-- 1. Bổ sung cột school_id vào bảng staff nếu chưa có
DROP PROCEDURE IF EXISTS `add_staff_school_link`;
DELIMITER $$
CREATE PROCEDURE `add_staff_school_link`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND column_name = 'school_id'
  ) THEN
    ALTER TABLE `staff`
      ADD COLUMN `school_id` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `user_id`;
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE() AND table_name = 'staff' AND index_name = 'idx_staff_school_id'
  ) THEN
    ALTER TABLE `staff` ADD KEY `idx_staff_school_id` (`school_id`);
  END IF;

  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE table_schema = DATABASE() AND table_name = 'staff'
      AND constraint_name = 'fk_staff_school' AND constraint_type = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `staff`
      ADD CONSTRAINT `fk_staff_school`
      FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
      ON UPDATE CASCADE ON DELETE SET NULL;
  END IF;
END$$
DELIMITER ;
CALL `add_staff_school_link`();
DROP PROCEDURE IF EXISTS `add_staff_school_link`;

-- 2. Thêm role 'teacher' (Giáo viên) nếu chưa tồn tại
INSERT INTO `roles` (`code`, `name`, `active`)
VALUES ('teacher', 'Giáo viên', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `active` = 1;

-- 3. Gán các quyền tối thiểu và an toàn cho role 'teacher'
-- results.view, admitted.view, quota.view, statistics.view, adjustment.submit, appeal.view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.code = 'teacher'
  AND p.code IN (
      'results.view',
      'admitted.view',
      'quota.view',
      'statistics.view',
      'adjustment.submit',
      'appeal.view'
  )
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);
