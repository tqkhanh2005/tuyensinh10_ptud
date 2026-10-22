-- Batch 8: Publication / Reporting
-- Scope: UC22, UC23, UC24, UC25, UC34, UC35, UC36
USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `publication_events` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `publication_type` ENUM('exam_results','admission_results') NOT NULL,
  `status` ENUM('published','cancelled') NOT NULL DEFAULT 'published',
  `published_by_user_id` BIGINT UNSIGNED NOT NULL,
  `published_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_publication_period_type` (`admission_period_id`,`publication_type`),
  CONSTRAINT `fk_publication_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_publication_user` FOREIGN KEY (`published_by_user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX `idx_publication_period_status` ON `publication_events` (`admission_period_id`,`status`);

INSERT INTO `permissions` (`code`,`name`) VALUES
('publication.manage','Công bố điểm thi và kết quả tuyển sinh'),
('results.view','Xem điểm thi và kết quả xét tuyển'),
('quota.view','Xem chỉ tiêu tuyển sinh'),
('statistics.view','Xem thống kê tuyển sinh'),
('admitted.view','Xem/Tải danh sách học sinh trúng tuyển')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.code IN ('admin','so_gdtd') AND p.code='publication.manage'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.code='candidate' AND p.code='results.view'
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO `role_permissions` (`role_id`,`permission_id`)
SELECT r.id,p.id FROM roles r CROSS JOIN permissions p
WHERE r.code IN ('admin','so_gdtd') AND p.code IN ('results.view','quota.view','statistics.view','admitted.view')
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

-- Ban Giám hiệu / cán bộ tuyển sinh may use statistics/admitted list when their role
-- has been mapped by the deployment. No new role is invented here.

DELIMITER $$
DROP TRIGGER IF EXISTS `trg_scores_lock_after_exam_publication`$$
CREATE TRIGGER `trg_scores_lock_after_exam_publication`
BEFORE INSERT ON `scores`
FOR EACH ROW
BEGIN
  DECLARE v_period_id INT UNSIGNED;
  DECLARE v_published INT DEFAULT 0;
  SELECT ep.admission_period_id INTO v_period_id FROM exam_papers ep WHERE ep.id=NEW.exam_paper_id LIMIT 1;
  SELECT COUNT(*) INTO v_published FROM publication_events pe
    WHERE pe.admission_period_id=v_period_id AND pe.publication_type='exam_results' AND pe.status='published';
  IF v_published > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Điểm thi đã công bố và bị khóa.'; END IF;
END$$

DROP TRIGGER IF EXISTS `trg_scores_update_lock_after_exam_publication`$$
CREATE TRIGGER `trg_scores_update_lock_after_exam_publication`
BEFORE UPDATE ON `scores`
FOR EACH ROW
BEGIN
  DECLARE v_period_id INT UNSIGNED;
  DECLARE v_published INT DEFAULT 0;
  SELECT ep.admission_period_id INTO v_period_id FROM exam_papers ep WHERE ep.id=OLD.exam_paper_id LIMIT 1;
  SELECT COUNT(*) INTO v_published FROM publication_events pe
    WHERE pe.admission_period_id=v_period_id AND pe.publication_type='exam_results' AND pe.status='published';
  IF v_published > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Điểm thi đã công bố và bị khóa.'; END IF;
END$$

DROP TRIGGER IF EXISTS `trg_scores_delete_lock_after_exam_publication`$$
CREATE TRIGGER `trg_scores_delete_lock_after_exam_publication`
BEFORE DELETE ON `scores`
FOR EACH ROW
BEGIN
  DECLARE v_period_id INT UNSIGNED;
  DECLARE v_published INT DEFAULT 0;
  SELECT ep.admission_period_id INTO v_period_id FROM exam_papers ep WHERE ep.id=OLD.exam_paper_id LIMIT 1;
  SELECT COUNT(*) INTO v_published FROM publication_events pe
    WHERE pe.admission_period_id=v_period_id AND pe.publication_type='exam_results' AND pe.status='published';
  IF v_published > 0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Điểm thi đã công bố và bị khóa.'; END IF;
END$$
DELIMITER ;

-- [MISSING SPECIFICATION]
-- 1) The source does not define a cancellation/unpublish flow; publication is therefore one-way.
-- 2) The exact filter fields for the admitted-student list are not enumerated. The UI exposes
--    school and enrollment-status filters as operational filters; no new admission rule is added.
-- 3) The source requests a downloadable file but does not mandate XLSX. Batch 8 exports CSV.
-- 4) Statistics are limited to the three types explicitly specified: hồ sơ, kết quả xét tuyển,
--    kết quả trúng tuyển. No extra dashboard metrics are invented.
