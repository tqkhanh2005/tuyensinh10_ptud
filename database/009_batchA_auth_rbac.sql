USE `tuyen_sinh_lop10`;

-- Batch A: foundation/auth/RBAC.
-- This migration is intentionally additive and idempotent.
-- It does not remove the legacy users.role column; that column remains only for compatibility.

INSERT INTO `permissions` (`code`, `name`)
VALUES ('school.manage', 'Quản lý trường THCS, THPT')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- UC17-19 specify Sở GD&ĐT as the primary actor.
-- Do not grant this permission to other roles without an explicit specification.
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.id, p.id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.code = 'so_gdtd'
  AND p.code = 'school.manage'
ON DUPLICATE KEY UPDATE `role_id` = VALUES(`role_id`);

-- Backfill the normalized RBAC mapping for existing users whose legacy role code
-- matches an active role. This preserves existing accounts while moving authorization
-- to user_roles -> roles -> role_permissions.
INSERT INTO `user_roles` (`user_id`, `role_id`)
SELECT u.id, r.id
FROM `users` u
INNER JOIN `roles` r ON r.code = u.role AND r.active = 1
WHERE NOT EXISTS (
    SELECT 1
    FROM `user_roles` existing_ur
    WHERE existing_ur.user_id = u.id
);

-- Rollback (manual, if required):
-- DELETE rp FROM role_permissions rp
-- INNER JOIN permissions p ON p.id = rp.permission_id
-- WHERE p.code = 'school.manage';
-- DELETE FROM permissions WHERE code = 'school.manage';
