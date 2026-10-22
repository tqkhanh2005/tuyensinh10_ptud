CREATE TABLE IF NOT EXISTS admission_period_configs (
 admission_period_id INT UNSIGNED NOT NULL PRIMARY KEY,
 settings LONGTEXT NOT NULL,
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 updated_by BIGINT UNSIGNED NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_period_config_period FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE CASCADE,
 CONSTRAINT fk_period_config_actor FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admission_period_events (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 admission_period_id INT UNSIGNED NOT NULL,
 actor_user_id BIGINT UNSIGNED NULL,
 action VARCHAR(30) NOT NULL,
 detail LONGTEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_period_event_period FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE CASCADE,
 CONSTRAINT fk_period_event_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
