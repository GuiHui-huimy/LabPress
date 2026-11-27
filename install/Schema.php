<?php
declare(strict_types=1);

namespace LabPress\Install;

if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}

/**
 * LabPress 1.2.0 全新安装基础表结构。
 *
 * 说明：
 * - 只建核心表；FooterInfo / Dynamic Multi-Language 等插件表由插件自行创建。
 *   footer_info 与 ml_translations 提前建好，便于官方插件首次激活和演示数据落地。
 * - 不使用外键，兼容共享主机与 MySQL/MariaDB 的常见权限。
 * - 统一使用 utf8mb4_unicode_ci，避免 MySQL 8 的 0900 排序规则在 MariaDB 上不可用。
 */
final class Schema
{
    public const VERSION = '1.2.0';

    /** @return array<int,string> */
    public static function statements(): array
    {
        return [
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `permissions` JSON NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员用户'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `site_config` (
  `config_key` VARCHAR(100) NOT NULL,
  `config_value` TEXT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='站点基础配置'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `nav_menu` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(100) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `target` VARCHAR(20) DEFAULT '_self',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `location` VARCHAR(20) NOT NULL DEFAULT 'header',
  `parent_id` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='导航菜单'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `summary` TEXT NOT NULL,
  `content` TEXT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `author` VARCHAR(100) DEFAULT 'HUI-MY.LAB',
  `date` DATE NOT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='科研动态'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `project_categories` (
  `slug` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `summary` TEXT NOT NULL,
  `content` TEXT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `category_slug` VARCHAR(50) NOT NULL,
  `featured` TINYINT(1) DEFAULT 1,
  `github_url` VARCHAR(255) DEFAULT NULL,
  `doc_url` VARCHAR(255) DEFAULT NULL,
  `external_url` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_slug` (`category_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `publications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(500) NOT NULL,
  `authors` TEXT NOT NULL,
  `journal` VARCHAR(300) NOT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='最新成果/论文'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `slides` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `image` VARCHAR(255) NOT NULL,
  `title` VARCHAR(300) NOT NULL,
  `description` TEXT NOT NULL,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='首页轮播图'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `tools` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(20) NOT NULL DEFAULT '软件',
  `icon` VARCHAR(100) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `features` TEXT NULL,
  `github` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `detail_link` VARCHAR(255) NOT NULL,
  `sort_order` INT DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工具列表'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `tool_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_name` VARCHAR(100) NOT NULL,
  `short_description` TEXT NULL,
  `detailed_description` TEXT NULL,
  `version` VARCHAR(30) DEFAULT NULL,
  `language` VARCHAR(50) DEFAULT NULL,
  `license` VARCHAR(50) DEFAULT NULL,
  `update_date` DATE DEFAULT NULL,
  `github` VARCHAR(255) DEFAULT NULL,
  `document` VARCHAR(255) DEFAULT NULL,
  `homepage` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `screenshot` VARCHAR(255) DEFAULT NULL,
  `citation_title` VARCHAR(500) DEFAULT NULL,
  `citation_url` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tool_name` (`tool_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工具详细信息'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `tool_versions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tool_name` VARCHAR(100) NOT NULL,
  `version` VARCHAR(30) NOT NULL,
  `date` DATE NOT NULL,
  `changes` JSON NULL,
  `downloads` JSON NULL,
  PRIMARY KEY (`id`),
  KEY `tool_name` (`tool_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工具版本历史'
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `plugins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `version` VARCHAR(50) DEFAULT NULL,
  `author` VARCHAR(255) DEFAULT NULL,
  `status` TINYINT(1) NOT NULL DEFAULT 0,
  `priority` SMALLINT NOT NULL DEFAULT 10,
  `requires_core` VARCHAR(50) NULL,
  `settings_json` JSON NULL,
  `manifest_json` JSON NULL,
  `data_policy` VARCHAR(20) NULL,
  `last_synced_at` DATETIME NULL,
  `installed_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `ml_translations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `table_name` VARCHAR(50) NOT NULL,
  `entity` VARCHAR(50) NOT NULL DEFAULT '',
  `record_id` VARCHAR(255) NOT NULL,
  `entity_id` VARCHAR(255) NOT NULL DEFAULT '',
  `field_name` VARCHAR(100) NOT NULL,
  `field` VARCHAR(100) NOT NULL DEFAULT '',
  `locale` VARCHAR(10) NOT NULL,
  `translated_value` TEXT NULL,
  `source_locale` VARCHAR(10) NULL,
  `source_hash` CHAR(64) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'current',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_translation` (`table_name`,`record_id`,`field_name`,`locale`),
  UNIQUE KEY `unique_translation_v2` (`entity`,`entity_id`,`field`,`locale`),
  KEY `idx_ml_entity_locale` (`entity`,`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
            <<<'SQL'
CREATE TABLE IF NOT EXISTS `footer_info` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `content` TEXT NOT NULL,
  `align` VARCHAR(20) DEFAULT 'center',
  `font_size` VARCHAR(10) DEFAULT '0.9rem',
  `color` VARCHAR(20) DEFAULT '#666666',
  `padding` VARCHAR(20) DEFAULT '0 20px',
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL,
        ];
    }
}
