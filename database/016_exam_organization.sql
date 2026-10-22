CREATE TABLE IF NOT EXISTS exam_organization_states (
 admission_period_id INT UNSIGNED PRIMARY KEY,
 number_prefix VARCHAR(10) NULL,
 released_at DATETIME NULL,
 released_by BIGINT UNSIGNED NULL,
 CONSTRAINT fk_exam_org_period FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_org_actor FOREIGN KEY(released_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS exam_candidate_numbers (
 admission_period_id INT UNSIGNED NOT NULL,
 candidate_id BIGINT UNSIGNED NOT NULL,
 sequence_no INT UNSIGNED NOT NULL,
 exam_number VARCHAR(30) NOT NULL,
 PRIMARY KEY(admission_period_id,candidate_id),
 UNIQUE KEY uq_exam_number_period(admission_period_id,exam_number),
 UNIQUE KEY uq_exam_sequence_period(admission_period_id,sequence_no),
 CONSTRAINT fk_exam_number_period FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE CASCADE,
 CONSTRAINT fk_exam_number_candidate FOREIGN KEY(candidate_id) REFERENCES candidates(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
