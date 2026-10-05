-- Batch 3: School + Admission setup
-- Dependency: Batch 1 foundation + Batch 2 staff/RBAC.
-- Database: tuyên_sinh_lop10 (project database is tuyên_sinh_lop10 / tuyensinh_lop10 depending on Batch 1 naming).
-- This migration assumes database `tuyen_sinh_lop10`.

USE `tuyen_sinh_lop10`;

CREATE TABLE IF NOT EXISTS `schools` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_code` VARCHAR(30) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `level` ENUM('THCS','THPT') NOT NULL,
  `address` VARCHAR(255) NULL,
  `district` VARCHAR(100) NULL,
  `admission_area` VARCHAR(100) NULL,
  `phone` VARCHAR(30) NULL,
  `email` VARCHAR(150) NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_schools_code` (`school_code`),
  KEY `idx_schools_level` (`level`),
  KEY `idx_schools_area` (`admission_area`),
  KEY `idx_schools_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admission_periods` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `admission_method` VARCHAR(100) NULL,
  `plan_content` TEXT NULL,
  `registration_start` DATETIME NULL,
  `registration_end` DATETIME NULL,
  `status` ENUM('draft','published','closed') NOT NULL DEFAULT 'draft',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admission_periods_year` (`academic_year`),
  KEY `idx_admission_periods_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admission_quotas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admission_period_id` INT UNSIGNED NOT NULL,
  `school_id` INT UNSIGNED NOT NULL,
  `quota` INT UNSIGNED NOT NULL,
  `status` ENUM('not_set','set') NOT NULL DEFAULT 'not_set',
  `published_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quota_period_school` (`admission_period_id`,`school_id`),
  KEY `idx_quota_school` (`school_id`),
  CONSTRAINT `fk_quota_period` FOREIGN KEY (`admission_period_id`) REFERENCES `admission_periods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_quota_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
