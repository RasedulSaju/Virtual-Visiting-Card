-- ============================================================
-- CMS + User Portal — Database Schema
-- Charset : utf8mb4 / utf8mb4_unicode_ci
-- Engine  : InnoDB
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
DROP TABLE IF EXISTS `pages`;
DROP TABLE IF EXISTS `users`;

-- ────────────────────────────────────────────────────────────
-- TABLE: users
-- ────────────────────────────────────────────────────────────
CREATE TABLE `users` (
  `id`               INT UNSIGNED         NOT NULL AUTO_INCREMENT,
  `username`         VARCHAR(50)          NOT NULL,
  `email`            VARCHAR(150)         NOT NULL,
  `password_hash`    VARCHAR(255)         NOT NULL,
  `role`             ENUM('admin','user') NOT NULL DEFAULT 'user',
  `can_edit_profile` TINYINT(1)           NOT NULL DEFAULT 1,
  `profile_image`    VARCHAR(255)         NOT NULL DEFAULT 'default-avatar.png',
  `bio`              TEXT,
  `reset_token`      VARCHAR(64)              NULL DEFAULT NULL,
  `reset_expires`    DATETIME                 NULL DEFAULT NULL,
  `created_at`       DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`),
  UNIQUE KEY `uq_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────
-- TABLE: pages
--   show_in_nav / nav_order control the public navigation menu.
-- ────────────────────────────────────────────────────────────
CREATE TABLE `pages` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`        VARCHAR(200) NOT NULL COMMENT 'URL-safe slug, e.g. about-us',
  `title`       VARCHAR(300) NOT NULL,
  `content`     LONGTEXT,
  `show_in_nav` TINYINT(1)   NOT NULL DEFAULT 0,
  `nav_order`   INT          NOT NULL DEFAULT 0,
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
  `id`          INT UNSIGNED                     NOT NULL AUTO_INCREMENT,
  `field_name`  VARCHAR(100)                     NOT NULL COMMENT 'Machine key, e.g. twitter',
  `field_label` VARCHAR(150)                     NOT NULL COMMENT 'Display label',
  `field_type`  ENUM('text','url','textarea')    NOT NULL DEFAULT 'text',
  `field_icon`  VARCHAR(100)                     NOT NULL DEFAULT 'fas fa-tag'
                  COMMENT 'FontAwesome class, e.g. fab fa-twitter',
  `sort_order`  INT                              NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)                       NOT NULL DEFAULT 1,
  `created_at`  DATETIME                         NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

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

-- NOTE: The admin user is created by setup.php, not here.
-- Run setup.php in your browser immediately after importing this file.

-- ============================================================
-- SMTP Settings
-- ============================================================

INSERT INTO `settings` (`skey`, `value`) VALUES
  ('smtp_host',       ''),
  ('smtp_port',       '587'),
  ('smtp_username',   ''),
  ('smtp_password',   ''),
  ('smtp_encryption', 'tls'),
  ('smtp_from_email', ''),
  ('smtp_from_name',  '')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);


-- ============================================================
-- Site Identity Settings
-- ============================================================

INSERT INTO `settings` (`skey`, `value`) VALUES
  ('site_name',        'Virtual Visiting Card'),
  ('site_description', 'Create and share your digital visiting card.')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);