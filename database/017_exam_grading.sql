CREATE TABLE IF NOT EXISTS exam_grading_states (
 admission_period_id INT UNSIGNED NOT NULL PRIMARY KEY,
 reviewed_at DATETIME NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 CONSTRAINT fk_grading_period FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE RESTRICT,
 CONSTRAINT fk_grading_actor FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
