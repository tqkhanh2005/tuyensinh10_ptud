USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `staff` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(150) NOT NULL,
    `workplace` VARCHAR(200) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(190) NULL,
    `staff_role` VARCHAR(150) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_staff_active` (`active`),
    KEY `idx_staff_workplace` (`workplace`),
    KEY `idx_staff_email` (`email`),
    KEY `idx_staff_phone` (`phone`)
) ENGINE=InnoDB;

INSERT INTO `permissions` (`code`, `name`)
VALUES
    ('staff.manage', 'Quản lý danh sách cán bộ - giáo viên'),
    ('staff.create', 'Thêm thông tin cán bộ - giáo viên'),
    ('staff.update', 'Sửa thông tin cán bộ - giáo viên'),
    ('staff.delete', 'Xóa thông tin cán bộ - giáo viên')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `roles` (`code`, `name`)
VALUES
    ('so_gdtd', 'Sở Giáo dục & Đào tạo'),
    ('admin', 'Quản trị viên')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.code IN ('so_gdtd', 'admin')
  AND p.code IN ('staff.manage', 'staff.create', 'staff.update', 'staff.delete')
ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

-- Rollback:
-- DROP TABLE IF EXISTS staff;
-- DELETE FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE code LIKE 'staff.%');
-- DELETE FROM permissions WHERE code LIKE 'staff.%';
-- DELETE FROM roles WHERE code IN ('so_gdtd', 'admin');
