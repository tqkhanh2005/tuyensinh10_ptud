CREATE TABLE IF NOT EXISTS admission_bonus_reviews (
 admission_period_id INT UNSIGNED NOT NULL,
 candidate_id BIGINT UNSIGNED NOT NULL,
 priority_group VARCHAR(30) NOT NULL,
 award_group VARCHAR(30) NOT NULL,
 evidence TEXT NOT NULL,
 reviewed_by BIGINT UNSIGNED NOT NULL,
 reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(admission_period_id,candidate_id),
 FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE RESTRICT,
 FOREIGN KEY(candidate_id) REFERENCES candidates(id) ON DELETE RESTRICT,
 FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admission_run_snapshots (
 admission_run_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 input_hash CHAR(64) NOT NULL,
 inputs LONGTEXT NOT NULL,
 report LONGTEXT NOT NULL,
 reviewed_by BIGINT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 FOREIGN KEY(admission_run_id) REFERENCES admission_runs(id) ON DELETE RESTRICT,
 FOREIGN KEY(reviewed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admission_tie_resolutions (
 admission_period_id INT UNSIGNED NOT NULL,
 tie_key CHAR(64) NOT NULL,
 chosen_candidates LONGTEXT NOT NULL,
 evidence TEXT NOT NULL,
 resolved_by BIGINT UNSIGNED NOT NULL,
 resolved_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(admission_period_id,tie_key),
 FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE RESTRICT,
 FOREIGN KEY(resolved_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
