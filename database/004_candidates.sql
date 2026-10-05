-- Batch 4: candidates, profile adjustments, aspirations
USE `tuyen_sinh_lop10`;

CREATE TABLE
  IF NOT EXISTS `candidates` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL,
    `candidate_code` VARCHAR(30) NULL,
    `national_id` VARCHAR(30) NOT NULL,
    `full_name` VARCHAR(150) NOT NULL,
    `date_of_birth` DATE NULL,
    `gender` VARCHAR(30) NULL,
    `address` VARCHAR(255) NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(190) NULL,
    `middle_school_id` INT UNSIGNED NULL,
    `eligible` TINYINT (1) NOT NULL DEFAULT 1,
    `status` ENUM ('active', 'inactive') NOT NULL DEFAULT 'active',
    `exam_number` VARCHAR(30) NULL,
    `exam_site_name` VARCHAR(150) NULL,
    `exam_room_name` VARCHAR(100) NULL,
    `exam_schedule` VARCHAR(150) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_candidates_national_id` (`national_id`),
    UNIQUE KEY `uq_candidates_candidate_code` (`candidate_code`),
    UNIQUE KEY `uq_candidates_user_id` (`user_id`),
    KEY `idx_candidates_school` (`middle_school_id`),
    KEY `idx_candidates_status` (`status`),
    CONSTRAINT `fk_candidates_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT `fk_candidates_school` FOREIGN KEY (`middle_school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  IF NOT EXISTS `adjustment_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `submitted_by_user_id` BIGINT UNSIGNED NOT NULL,
    `old_data` JSON NOT NULL,
    `new_data` JSON NOT NULL,
    `reason` TEXT NOT NULL,
    `evidence_path` VARCHAR(500) NULL,
    `status` ENUM ('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `reviewed_by_user_id` BIGINT UNSIGNED NULL,
    `review_reason` TEXT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_adjustment_candidate_status` (`candidate_id`, `status`),
    CONSTRAINT `fk_adjustment_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_adjustment_submitter` FOREIGN KEY (`submitted_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_adjustment_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  IF NOT EXISTS `aspirations` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `admission_period_id` INT UNSIGNED NOT NULL,
    `school_id` INT UNSIGNED NOT NULL,
    `priority_order` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_aspiration_candidate_period_priority` (
      `candidate_id`,
      `admission_period_id`,
      `priority_order`
    ),
    UNIQUE KEY `uq_aspiration_candidate_period_school` (
      `candidate_id`,
      `admission_period_id`,
      `school_id`
    ),
    KEY `idx_aspiration_school` (`school_id`),
    CONSTRAINT `fk_aspiration_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_aspiration_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_aspiration_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `roles` (`code`, `name`)
VALUES
  ('candidate', 'Thí sinh/Học sinh') ON DUPLICATE KEY
UPDATE `name` =
VALUES
  (`name`);

INSERT INTO
  `permissions` (`code`, `name`)
VALUES
  (
    'candidate.manage',
    'Quản lý danh sách thí sinh/học sinh'
  ),
  ('candidate.create', 'Thêm thí sinh/học sinh'),
  ('candidate.update', 'Sửa thí sinh/học sinh'),
  ('candidate.delete', 'Xóa thí sinh/học sinh'),
  (
    'adjustment.submit',
    'Gửi yêu cầu điều chỉnh hồ sơ'
  ),
  (
    'adjustment.review',
    'Duyệt yêu cầu điều chỉnh hồ sơ'
  ),
  ('aspiration.manage', 'Đăng ký nguyện vọng'),
  ('exam_card.view', 'Xem thẻ dự thi') ON DUPLICATE KEY
UPDATE `name` =
VALUES
  (`name`);

INSERT INTO
  `role_permissions` (`role_id`, `permission_id`)
SELECT
  r.id,
  p.id
FROM
  roles r
  CROSS JOIN permissions p
WHERE
  r.code IN ('admin', 'so_gdtd')
  AND p.code IN (
    'candidate.manage',
    'candidate.create',
    'candidate.update',
    'candidate.delete',
    'adjustment.review',
    'exam_card.view'
  ) ON DUPLICATE KEY
UPDATE role_id =
VALUES
  (role_id);

INSERT INTO
  `role_permissions` (`role_id`, `permission_id`)
SELECT
  r.id,
  p.id
FROM
  roles r
  CROSS JOIN permissions p
WHERE
  r.code = 'candidate'
  AND p.code IN (
    'adjustment.submit',
    'aspiration.manage',
    'exam_card.view'
  ) ON DUPLICATE KEY
UPDATE role_id =
VALUES
  (role_id);

-- [MISSING SPECIFICATION] Exact candidate fields, default account credential,
-- evidence file types/size, maximum aspiration count, and exam-card scheduling rules
-- are not fully specified in the source documents.