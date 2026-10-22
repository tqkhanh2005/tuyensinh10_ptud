CREATE DATABASE IF NOT EXISTS `tuyen_sinh_lop10` CHARACTER
SET
    utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `tuyen_sinh_lop10`;

CREATE TABLE
    IF NOT EXISTS `users` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(100) NOT NULL,
        `password_hash` VARCHAR(255) NOT NULL,
        `full_name` VARCHAR(150) NULL,
        `role` VARCHAR(100) NOT NULL,
        `email` VARCHAR(190) NULL,
        `phone` VARCHAR(30) NULL,
        `active` TINYINT (1) NOT NULL DEFAULT 1,
        `last_login_at` DATETIME NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_users_username` (`username`),
        KEY `idx_users_role_active` (`role`, `active`)
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS `roles` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(100) NOT NULL,
        `name` VARCHAR(150) NOT NULL,
        `active` TINYINT (1) NOT NULL DEFAULT 1,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_roles_code` (`code`)
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS `permissions` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(150) NOT NULL,
        `name` VARCHAR(200) NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_permissions_code` (`code`)
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS `role_permissions` (
        `role_id` BIGINT UNSIGNED NOT NULL,
        `permission_id` BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (`role_id`, `permission_id`),
        CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE = InnoDB;

CREATE TABLE
    IF NOT EXISTS `user_roles` (
        `user_id` BIGINT UNSIGNED NOT NULL,
        `role_id` BIGINT UNSIGNED NOT NULL,
        PRIMARY KEY (`user_id`, `role_id`),
        CONSTRAINT `fk_user_roles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT `fk_user_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE = InnoDB;