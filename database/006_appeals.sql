-- Batch 6: Appeals / Phúc khảo
-- Dependency: Batch 1 + Batch 4 + Batch 5
USE `tuyen_sinh_lop10`;

CREATE TABLE
  IF NOT EXISTS `appeal_windows` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_period_id` INT UNSIGNED NOT NULL,
    `start_at` DATETIME NOT NULL,
    `end_at` DATETIME NOT NULL,
    `fee_amount` DECIMAL(12, 2) NULL,
    `status` ENUM ('draft', 'open', 'closed') NOT NULL DEFAULT 'draft',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appeal_window_period` (`admission_period_id`),
    CONSTRAINT `fk_appeal_window_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  IF NOT EXISTS `appeal_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admission_period_id` INT UNSIGNED NOT NULL,
    `candidate_id` BIGINT UNSIGNED NOT NULL,
    `exam_paper_id` BIGINT UNSIGNED NOT NULL,
    `exam_subject_id` INT UNSIGNED NOT NULL,
    `original_score` DECIMAL(5, 2) NOT NULL,
    `reason` TEXT NOT NULL,
    `fee_amount` DECIMAL(12, 2) NULL,
    `payment_status` ENUM ('not_required', 'pending', 'paid', 'failed') NOT NULL DEFAULT 'not_required',
    `status` ENUM ('pending', 'grading', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    `submitted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appeal_candidate_subject_period` (
      `admission_period_id`,
      `candidate_id`,
      `exam_subject_id`
    ),
    UNIQUE KEY `uq_appeal_exam_paper` (`exam_paper_id`),
    KEY `idx_appeal_subject_status` (`exam_subject_id`, `status`),
    CONSTRAINT `fk_appeal_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_appeal_candidate` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_appeal_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_appeal_subject` FOREIGN KEY (`exam_subject_id`) REFERENCES `exam_subjects` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  IF NOT EXISTS `appeal_results` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `appeal_request_id` BIGINT UNSIGNED NOT NULL,
    `old_score` DECIMAL(5, 2) NOT NULL,
    `new_score` DECIMAL(5, 2) NOT NULL,
    `grader_user_id` BIGINT UNSIGNED NULL,
    `change_reason` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_appeal_result_request` (`appeal_request_id`),
    CONSTRAINT `fk_appeal_result_request` FOREIGN KEY (`appeal_request_id`) REFERENCES `appeal_requests` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_appeal_result_grader` FOREIGN KEY (`grader_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  IF NOT EXISTS `score_change_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exam_paper_id` BIGINT UNSIGNED NOT NULL,
    `appeal_request_id` BIGINT UNSIGNED NOT NULL,
    `old_score` DECIMAL(5, 2) NOT NULL,
    `new_score` DECIMAL(5, 2) NOT NULL,
    `changed_by_user_id` BIGINT UNSIGNED NOT NULL,
    `reason` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_score_log_paper` (`exam_paper_id`),
    KEY `idx_score_log_appeal` (`appeal_request_id`),
    CONSTRAINT `fk_score_log_paper` FOREIGN KEY (`exam_paper_id`) REFERENCES `exam_papers` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_score_log_appeal` FOREIGN KEY (`appeal_request_id`) REFERENCES `appeal_requests` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT `fk_score_log_user` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

CREATE TABLE
  IF NOT EXISTS `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `title` VARCHAR(190) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_user` (`user_id`, `created_at`),
    CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
  ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

INSERT INTO
  `permissions` (`code`, `name`)
VALUES
  ('appeal.submit', 'Đăng ký phúc khảo'),
  ('appeal.view', 'Theo dõi trạng thái phúc khảo'),
  ('appeal.manage', 'Cập nhật kết quả phúc khảo') ON DUPLICATE KEY
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
  r.code = 'candidate'
  AND p.code IN ('appeal.submit', 'appeal.view') ON DUPLICATE KEY
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
  r.code IN ('admin', 'so_gdtd')
  AND p.code = 'appeal.manage' ON DUPLICATE KEY
UPDATE role_id =
VALUES
  (role_id);

-- Rollback:
-- DROP TABLE IF EXISTS notifications;
-- DROP TABLE IF EXISTS score_change_logs;
-- DROP TABLE IF EXISTS appeal_results;
-- DROP TABLE IF EXISTS appeal_requests;
-- DROP TABLE IF EXISTS appeal_windows;