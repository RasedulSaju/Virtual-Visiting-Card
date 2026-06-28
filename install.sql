-- ============================================================
-- Virtual Visiting Card — Complete Database Installer
-- Merges schema.sql + migrations 001-006 into a single file.
-- Charset : utf8mb4 / utf8mb4_unicode_ci | Engine : InnoDB
--
-- INSTALL ORDER:
--   1. Import this file into your MySQL database.
--   2. Open setup.php in your browser to create the admin account.
--   3. Delete setup.php from the server after first use.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ────────────────────────────────────────────────────────────
-- Drop tables in reverse dependency order
-- ────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `user_field_values`;
DROP TABLE IF EXISTS `profile_fields`;
DROP TABLE IF EXISTS `invitations`;
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- ────────────────────────────────────────────────────────────
-- TABLE: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`                INT UNSIGNED                    NOT NULL AUTO_INCREMENT,
  `username`          VARCHAR(50)                     NOT NULL,
  `email`             VARCHAR(150)                    NOT NULL,
  `password_hash`     VARCHAR(255)                    NOT NULL,
  `role`              ENUM('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `account_status`    ENUM('active','resigned')       NOT NULL DEFAULT 'active',
  `can_edit_profile`  TINYINT(1)                      NOT NULL DEFAULT 1,
  `meta_robots`       VARCHAR(20)                     NOT NULL DEFAULT 'index,follow',
  `show_in_directory` TINYINT(1)                      NOT NULL DEFAULT 1,
  `profile_image`     VARCHAR(255)                    NOT NULL DEFAULT 'default-avatar.png',
  `bio`               TEXT,
  `reset_token`       VARCHAR(64)                         NULL DEFAULT NULL,
  `reset_expires`     DATETIME                            NULL DEFAULT NULL,
  `created_at`        DATETIME                        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLE: pages
--   show_in_nav / nav_order control the public navigation menu.
--   meta_robots controls per-page SEO visibility.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `pages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(200) NOT NULL,
  `title`       VARCHAR(300) NOT NULL,
  `content`     LONGTEXT,
  `show_in_nav` TINYINT(1)   NOT NULL DEFAULT 0,
  `nav_order`   INT          NOT NULL DEFAULT 0,
  `meta_robots` VARCHAR(20)  NOT NULL DEFAULT 'index,follow',
  `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                             ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLE: profile_fields
--   Admin-defined custom fields that appear on every user profile.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `profile_fields` (
  `id`          INT UNSIGNED                          NOT NULL AUTO_INCREMENT,
  `field_name`  VARCHAR(100)                          NOT NULL,
  `field_label` VARCHAR(150)                          NOT NULL,
  `field_type`  ENUM('text','url','textarea','date')  NOT NULL DEFAULT 'text',
  `field_icon`  VARCHAR(100)                          NOT NULL DEFAULT 'fas fa-tag',
  `sort_order`  INT                                   NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)                            NOT NULL DEFAULT 1,
  `is_public`   TINYINT(1)                            NOT NULL DEFAULT 1,
  `created_at`  DATETIME                              NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_field_name` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLE: user_field_values
--   Stores each user's answer for each profile field.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `user_field_values` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `field_id`    INT UNSIGNED NOT NULL,
  `field_value` TEXT,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_field` (`user_id`, `field_id`),
  CONSTRAINT `fk_ufv_user`  FOREIGN KEY (`user_id`)
      REFERENCES `users`(`id`)          ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ufv_field` FOREIGN KEY (`field_id`)
      REFERENCES `profile_fields`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLE: settings
--   Key/value store for all admin-configurable options.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `settings` (
  `skey`       VARCHAR(100) NOT NULL,
  `value`      TEXT         NOT NULL DEFAULT '',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
               ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLE: invitations
--   Admin-issued signed registration links.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `invitations` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(150) NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `invited_by` INT UNSIGNED NOT NULL,
  `used`       TINYINT(1)   NOT NULL DEFAULT 0,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token`),
  CONSTRAINT `fk_inv_admin` FOREIGN KEY (`invited_by`)
      REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Initial Users
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `account_status`, `can_edit_profile`, `meta_robots`, `show_in_directory`, `profile_image`, `bio`, `reset_token`, `reset_expires`, `created_at`) VALUES
(1, 'Rasedul', 'me@rasedulsaju.com', '$2y$12$cBKitkQLdmQHKmySesqoBOGv74gXVFbZM4Capmp2ozGCfQ8HMc9RO', 'superadmin', 'active', 1, 'index,follow', 1, 'default-avatar.png', NULL, NULL, NULL, '2026-06-20 18:25:00'),
(2, 'RasedulSaju', 'rasedulsaju@gmail.com', '$2y$12$cBKitkQLdmQHKmySesqoBOGv74gXVFbZM4Capmp2ozGCfQ8HMc9RO', 'admin', 'active', 1, 'index,follow', 1, 'default-avatar.png', NULL, NULL, NULL, '2026-06-20 18:25:08');

-- Sample page (visible in navigation)
INSERT INTO `pages` (`slug`, `title`, `content`, `show_in_nav`, `nav_order`) VALUES
('about-us', 'About Us',
 '<h4>Welcome to our platform</h4><p>This is a sample <strong>About Us</strong> page. Edit it from the Admin Panel under <em>Pages</em>.</p>',
 1, 1);

-- Default profile fields
INSERT INTO `profile_fields`
  (`field_name`, `field_label`, `field_type`, `field_icon`, `sort_order`) VALUES
  ('website',   'Website',        'url',      'fas fa-globe',          1),
  ('twitter',   'Twitter',        'text',     'fab fa-twitter',        2),
  ('linkedin',  'LinkedIn',       'url',      'fab fa-linkedin-in',    3),
  ('github',    'GitHub',         'url',      'fab fa-github',         4),
  ('location',  'Location',       'text',     'fas fa-map-marker-alt', 5);

-- Registration / general settings
INSERT INTO `settings` (`skey`, `value`) VALUES
  ('registration_open', '1'),
  ('upload_limit_mb',   '2'),

  -- SMTP settings
  ('smtp_host',       ''),
  ('smtp_port',       '587'),
  ('smtp_username',   ''),
  ('smtp_password',   ''),
  ('smtp_encryption', 'tls'),
  ('smtp_from_email', ''),
  ('smtp_from_name',  ''),

  -- Site identity
  ('site_name',        'Virtual Visiting Card'),
  ('site_description', 'Create and share your digital visiting card.'),

  -- Theme / appearance
  ('theme_primary_color',    '#4f46e5'),
  ('theme_accent_color',     '#7c3aed'),
  ('theme_text_color',       '#374151'),
  ('theme_heading_color',    '#0f172a'),
  ('theme_bg_color',         '#f8fafc'),
  ('theme_surface_color',    '#ffffff'),
  ('theme_border_radius',    '12'),
  ('theme_font_heading',     'Space Grotesk'),
  ('theme_font_body',        'system-ui'),
  ('theme_enable_animations','1'),

  -- SEO
  ('seo_global_noindex', '0'),
  ('robots_txt_custom',  '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- NOTE: The admin user is created by setup.php, not here.
-- Run setup.php in your browser immediately after importing this file.
