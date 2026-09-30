-- ==============================================================================
-- Database Schema for Carouselfy - Instagram Carousel Automation Studio
-- Target: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+ (Hostinger Compatible)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ------------------------------------------------------------------------------
-- 1. Users Table (Authentication & Multi-tenant support)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL DEFAULT 'Creator',
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 2. Brand Kits Table (Saved Brand Presets, Colors, Handles, Logos)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `brand_kits` (
  `id` VARCHAR(64) PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `name` VARCHAR(100) NOT NULL DEFAULT 'Default Brand',
  `handle` VARCHAR(100) NOT NULL DEFAULT '@carouselfy',
  `profile_url` VARCHAR(255) DEFAULT 'https://carouselfy.app',
  `logos_json` LONGTEXT NULL,
  `primary_color` VARCHAR(32) NOT NULL DEFAULT '#6366f1',
  `secondary_color` VARCHAR(32) NOT NULL DEFAULT '#22d3ee',
  `accent_color` VARCHAR(32) NOT NULL DEFAULT '#f472b6',
  `canvas_color` VARCHAR(32) NOT NULL DEFAULT '#0b1020',
  `font_heading` VARCHAR(100) NOT NULL DEFAULT "'Space Grotesk', sans-serif",
  `font_body` VARCHAR(100) NOT NULL DEFAULT "'Inter', sans-serif",
  `active_logo_index` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_brand_user` (`user_id`),
  CONSTRAINT `fk_brand_kits_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 3. Projects Table (Carousel Projects, Slides & Archetype Data)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
  `id` VARCHAR(64) PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `topic` VARCHAR(255) NOT NULL DEFAULT '',
  `category` VARCHAR(100) DEFAULT 'CAROUSEL',
  `aspect_ratio` ENUM('4:5', '1:1', '9:16') NOT NULL DEFAULT '4:5',
  `template_id` VARCHAR(100) NOT NULL DEFAULT 'stacked-minimal-midnight',
  `brand_kit_id` VARCHAR(64) NULL,
  `show_watermark` TINYINT(1) NOT NULL DEFAULT 1,
  `show_progress` TINYINT(1) NOT NULL DEFAULT 1,
  `header_scale` FLOAT NOT NULL DEFAULT 1.0,
  `footer_scale` FLOAT NOT NULL DEFAULT 1.0,
  `slides_json` LONGTEXT NOT NULL,
  `thumbnail_url` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_projects_user` (`user_id`),
  INDEX `idx_projects_updated` (`updated_at`),
  CONSTRAINT `fk_projects_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 4. Instagram Accounts Table (Connected Meta Pages & IG Business Accounts)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `instagram_accounts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `facebook_user_id` VARCHAR(100) NULL,
  `page_id` VARCHAR(100) NOT NULL,
  `page_name` VARCHAR(150) NOT NULL DEFAULT '',
  `instagram_business_id` VARCHAR(100) NOT NULL,
  `instagram_username` VARCHAR(100) NOT NULL,
  `access_token` TEXT NOT NULL,
  `token_expires_at` DATETIME NULL,
  `profile_picture_url` TEXT NULL,
  `followers_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ig_user` (`user_id`),
  INDEX `idx_ig_business_id` (`instagram_business_id`),
  CONSTRAINT `fk_ig_accounts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 5. Scheduled Posts Table (Automation Queue & History)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `scheduled_posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `instagram_account_id` INT UNSIGNED NOT NULL,
  `project_id` VARCHAR(64) NULL,
  `post_type` ENUM('carousel', 'image', 'video') NOT NULL DEFAULT 'carousel',
  `media_urls_json` LONGTEXT NOT NULL,
  `caption` TEXT NULL,
  `hashtags` TEXT NULL,
  `scheduled_at` DATETIME NOT NULL,
  `published_at` DATETIME NULL,
  `status` ENUM('scheduled', 'publishing', 'published', 'failed') NOT NULL DEFAULT 'scheduled',
  `instagram_media_id` VARCHAR(100) NULL,
  `error_message` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_scheduled_status_time` (`status`, `scheduled_at`),
  INDEX `idx_scheduled_account` (`instagram_account_id`),
  CONSTRAINT `fk_scheduled_account` FOREIGN KEY (`instagram_account_id`) REFERENCES `instagram_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scheduled_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 6. Activity Logs Table (Audit, Errors, Publishing History)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_logs_action` (`action`),
  INDEX `idx_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- 7. App Settings Key-Value Store
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `key_name` VARCHAR(100) PRIMARY KEY,
  `value` LONGTEXT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------------
-- Default Seed Data: Default Admin User (Password: admin123)
-- ------------------------------------------------------------------------------
INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password_hash`, `role`) VALUES
(1, 'Admin', 'admin@carouselfy.local', '$2y$10$wN16lq7v5O6hJgUeKx7qfeZJ7eF9N7sA18kC9v8T0lC8k.qF7K6S6', 'admin');

-- Default Brand Kit
INSERT IGNORE INTO `brand_kits` (`id`, `user_id`, `name`, `handle`, `profile_url`, `logos_json`, `primary_color`, `secondary_color`, `accent_color`, `canvas_color`, `font_heading`, `font_body`, `active_logo_index`) VALUES
('bk_default', 1, 'Carouselfy', '@carouselfy', 'https://carouselfy.app', '[]', '#6366f1', '#22d3ee', '#f472b6', '#0b1020', "'Space Grotesk', sans-serif", "'Inter', sans-serif", 0);

SET FOREIGN_KEY_CHECKS = 1;
