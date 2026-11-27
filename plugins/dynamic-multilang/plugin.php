<?php
/**
 * Plugin Name: Dynamic Multi-Language
 * Description: 为导航菜单、页脚链接、轮播图、关于我们、研究方向提供动态多语言翻译管理。
 * Version: 3.0.0
 * Author: LabPress Community
 * Requires LabPress: >=1.3.0
 */

use LabPress\I18n\EntityRegistry;
use LabPress\I18n\Repository;

// ============================================================
// 后台独立语言（插件接管，核心没有插件时自动回退站点默认语言）
// ============================================================

/**
 * admin_locale 过滤器回调。
 * 优先级：当前请求 lang > session > cookie > 核心传入的站点默认语言。
 */
function ml_admin_locale($locale, $available = []) {
    if (!defined('IS_ADMIN') && strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') === false) {
        return $locale;
    }

    $available = is_array($available) ? $available : array_column(getAvailableLanguages(), 'locale');
    $requested = isset($_GET['lang']) ? (string)$_GET['lang'] : '';

    if ($requested !== '' && in_array($requested, $available, true)) {
        $_SESSION['ml_admin_locale'] = $requested;
        if (!headers_sent()) {
            setcookie('ml_admin_locale', $requested, [
                'expires'  => time() + 86400 * 30,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']),
            ]);
        }
        return $requested;
    }

    $sessionLocale = $_SESSION['ml_admin_locale'] ?? '';
    if ($sessionLocale !== '' && in_array($sessionLocale, $available, true)) {
        return $sessionLocale;
    }

    $cookieLocale = $_COOKIE['ml_admin_locale'] ?? '';
    if ($cookieLocale !== '' && in_array($cookieLocale, $available, true)) {
        return $cookieLocale;
    }

    return $locale;
}
LabPress_Hooks::addFilter('admin_locale', 'ml_admin_locale', 10, 2);

/**
 * 渲染后台 / 登录页的语言切换入口。
 */
function ml_render_admin_language_switcher($context = 'admin') {
    $languages = getAvailableLanguages();
    if (count($languages) < 2) {
        return;
    }

    $current = getLocale();
    $class = $context === 'login' ? 'ml-login-lang' : 'ml-admin-lang';

    echo '<div class="' . $class . '" role="group" aria-label="Language">';
    $first = true;
    foreach ($languages as $language) {
        $localeCode = (string)($language['locale'] ?? '');
        if ($localeCode === '') {
            continue;
        }
        if (!$first) {
            echo '<span class="ml-lang-sep" aria-hidden="true">/</span>';
        }
        $first = false;
        $label = (string)($language['name'] ?? $localeCode);
        $active = $localeCode === $current ? ' is-active' : '';
        echo '<a class="ml-lang-link' . $active . '" '
            . 'href="' . e(lp_url_for_lang($localeCode)) . '" '
            . 'hreflang="' . e(str_replace('_', '-', $localeCode)) . '">'
            . e($label) . '</a>';
    }
    echo '</div>';
}

LabPress_Hooks::addAction('admin_topbar_actions', function() {
    ml_render_admin_language_switcher('admin');
});
LabPress_Hooks::addAction('login_after_form', function() {
    ml_render_admin_language_switcher('login');
});

/**
 * 只在后台 / 登录页加载插件后台样式。
 */
function ml_admin_assets() {
    if (!defined('IS_ADMIN') && strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') === false) {
        return;
    }
    LabPress::enqueueStyle('../plugins/dynamic-multilang/assets/dm-admin.css', [], '3.0.0');
}
LabPress_Hooks::addAction('before_head_end', 'ml_admin_assets');
LabPress_Hooks::addAction('admin_before_head_end', 'ml_admin_assets');

// ============================================================
// v2 entity registry + repository compatibility wrappers
// ============================================================

EntityRegistry::register('nav_menu', [
    'label' => 'Navigation menus',
    'table' => 'nav_menu',
    'id_field' => 'id',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text'],
    ],
]);
EntityRegistry::register('slides', [
    'label' => 'Slides',
    'table' => 'slides',
    'id_field' => 'id',
    'fields' => [
        'title' => ['label' => 'Title', 'type' => 'text'],
        'description' => ['label' => 'Description', 'type' => 'textarea'],
    ],
]);
EntityRegistry::register('site_config', [
    'label' => 'Site copy',
    'table' => 'site_config',
    'id_field' => 'config_key',
    'fields' => [
        'config_value' => ['label' => 'Text', 'type' => 'textarea'],
    ],
]);
EntityRegistry::register('__custom', [
    'label' => 'Dynamic content',
    'table' => '',
    'id_field' => 'id',
    'dynamic' => true,
    'fields' => [
        'text' => ['label' => 'Text', 'type' => 'text'],
    ],
]);

// 源语言配置
function ml_get_source_locale() {
    return Repository::sourceLocale();
}
function ml_set_source_locale($locale) {
    if (class_exists('LabPress') && \LabPress\SiteConfig::isAllowed('ml_source_locale')) {
        LabPress::setConfig('ml_source_locale', (string)$locale);
        Repository::invalidate();
    }
}

// 兼容旧插件代码/文档的翻译包装。
function ml_get_translation($table, $recordId, $field, $locale = null) {
    return Repository::get((string)$table, (string)$recordId, (string)$field, $locale);
}
function ml_set_translation($table, $recordId, $field, $locale, $value) {
    return Repository::set((string)$table, (string)$recordId, (string)$field, (string)$locale, (string)$value);
}

// 安装：确保翻译表存在（正常安装已由 schema/migration 创建）。
LabPress_Hooks::addAction('plugin_activation', function($slug) {
    if ($slug === 'dynamic-multilang') {
        $db = LabPress::db();
        $db->exec("CREATE TABLE IF NOT EXISTS `ml_translations` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
});

// ========== 前台过滤器（优先注册） ==========
LabPress_Hooks::addAction('init', function() {
    // 1. 导航菜单翻译：一次预取，避免每个菜单项一条查询。
    LabPress_Hooks::addFilter('nav_menu_item', function($html, $menu) {
        $id = $menu['id'] ?? null;
        if (!$id) return $html;
        Repository::prefetch('nav_menu', null, ['title']);
        $newTitle = Repository::get('nav_menu', (string)$id, 'title');
        if ($newTitle === null || $newTitle === '') return $html;
        return sprintf('<a href="%s">%s</a>', htmlspecialchars((string)$menu['url']), htmlspecialchars($newTitle));
    }, 10, 2);

    // 2. 轮播图翻译：按当前页的 slide id 预取。
    LabPress_Hooks::addFilter('slides_data', function($slides) {
        if (!is_array($slides)) return $slides;
        $ids = [];
        foreach ($slides as $slide) {
            if (isset($slide['id'])) $ids[] = (string)$slide['id'];
        }
        Repository::prefetch('slides', $ids, ['title', 'description']);
        foreach ($slides as &$slide) {
            $id = $slide['id'] ?? null;
            if (!$id) continue;
            $title = Repository::get('slides', (string)$id, 'title');
            $desc = Repository::get('slides', (string)$id, 'description');
            if ($title !== null && $title !== '') $slide['title'] = $title;
            if ($desc !== null && $desc !== '') $slide['description'] = $desc;
        }
        unset($slide);
        return $slides;
    });

    // 3. 静态文字翻译（支持 site_config 和自定义键）
    LabPress_Hooks::addFilter('translate', function($value, $key, $locale = null, $module = null) {
        // 后台页面不翻译，保持原文
        if (defined('IS_ADMIN') || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) {
            return $value;
        }
        $locale = $locale ?: getLocale();

        Repository::prefetch('__custom', null, ['text'], $locale);
        Repository::prefetch('site_config', null, ['config_value'], $locale);

        // 优先查自定义键
        $trans = Repository::get('__custom', (string)$key, 'text', $locale);
        if ($trans !== null && $trans !== '') return $trans;

        // site_config 字段映射
        $siteMap = [
            'about_us' => 'about_us',
            'about_us_content' => 'about_us',
            'research_title' => 'research_title',
            'research_heading' => 'research_title',
            'research_subtitle' => 'research_subtitle',
            'research_subheading' => 'research_subtitle',
        ];
        if (isset($siteMap[$key])) {
            $trans = Repository::get('site_config', $siteMap[$key], 'config_value', $locale);
            if ($trans !== null && $trans !== '') return $trans;
        }
        return $value;
    }, 10, 4);
});

// ========== 后台管理页面（独立文件，不影响前台） ==========
LabPress::registerAdminPage('dynamic-multilang', __('ml_plugin_name', 'plugin:dynamic-multilang'), function() {
    require_once __DIR__ . '/admin-page.php';
});

// 卸载时保留翻译数据，避免 v2 迁移期间误删历史内容。
LabPress_Hooks::addAction('plugin_uninstalled', function($slug) {
    if ($slug === 'dynamic-multilang') {
        LabPress\I18n\Repository::invalidate();
    }
});
