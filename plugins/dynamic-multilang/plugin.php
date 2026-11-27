<?php
/**
 * Plugin Name: Dynamic Multi-Language
 * Description: 为导航菜单、页脚链接、轮播图、关于我们、研究方向提供动态多语言翻译管理。
 * Version: 2.0.2
 * Author: LabPress Community
 * Requires LabPress: >=1.2.0
 * version: 2.0.2 change 2026-11-24
 *      - add plugin-owned admin locale filter, login/admin switchers, and isolated plugin language loading.
 * version: 2.0.1 change 2026-08-14
 *      - revise the language key to 'ml_' prefix ,to avoid conflict with core language key.
 */

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
    LabPress::enqueueStyle('../plugins/dynamic-multilang/assets/dm-admin.css', [], '2.0.2');
}
LabPress_Hooks::addAction('before_head_end', 'ml_admin_assets');
LabPress_Hooks::addAction('admin_before_head_end', 'ml_admin_assets');

// 安装：创建翻译表
LabPress_Hooks::addAction('plugin_activation', function($slug) {
    if ($slug === 'dynamic-multilang') {
        $db = LabPress::db();
        $db->exec("CREATE TABLE IF NOT EXISTS `ml_translations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `table_name` VARCHAR(50) NOT NULL,
            `record_id` VARCHAR(100) NOT NULL,
            `field_name` VARCHAR(100) NOT NULL,
            `locale` VARCHAR(10) NOT NULL,
            `translated_value` TEXT,
            UNIQUE KEY `unique_translation` (`table_name`, `record_id`, `field_name`, `locale`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
});

// 源语言配置
function ml_get_source_locale() {
    return LabPress::config('ml_source_locale', LabPress::config('language', 'zh_CN'));
}
function ml_set_source_locale($locale) { LabPress::setConfig('ml_source_locale', $locale); }

// 翻译工具
function ml_get_translation($table, $recordId, $field, $locale = null) {
    if ($locale === null) $locale = getLocale();
    if ($locale === ml_get_source_locale()) return null;
    $db = LabPress::db();
    $stmt = $db->prepare("SELECT translated_value FROM ml_translations WHERE table_name=? AND record_id=? AND field_name=? AND locale=? LIMIT 1");
    $stmt->execute([$table, $recordId, $field, $locale]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : null;
}

function ml_set_translation($table, $recordId, $field, $locale, $value) {
    $db = LabPress::db();
    if (empty($value)) {
        $stmt = $db->prepare("DELETE FROM ml_translations WHERE table_name=? AND record_id=? AND field_name=? AND locale=?");
        $stmt->execute([$table, $recordId, $field, $locale]);
    } else {
        $stmt = $db->prepare("INSERT INTO ml_translations (table_name, record_id, field_name, locale, translated_value) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE translated_value = VALUES(translated_value)");
        $stmt->execute([$table, $recordId, $field, $locale, $value]);
    }
}

// ========== 前台过滤器（优先注册） ==========
LabPress_Hooks::addAction('init', function() {
    // 1. 导航菜单翻译
    LabPress_Hooks::addFilter('nav_menu_item', function($html, $menu) {
        $id = $menu['id'] ?? null;
        if (!$id) return $html;
        $newTitle = ml_get_translation('nav_menu', $id, 'title');
        if ($newTitle === null) return $html;
        return sprintf('<a href="%s">%s</a>', htmlspecialchars($menu['url']), htmlspecialchars($newTitle));
    }, 10, 2);

    // 2. 轮播图翻译
    LabPress_Hooks::addFilter('slides_data', function($slides) {
        foreach ($slides as &$slide) {
            $id = $slide['id'] ?? null;
            if ($id) {
                $title = ml_get_translation('slides', $id, 'title');
                $desc = ml_get_translation('slides', $id, 'description');
                if ($title) $slide['title'] = $title;
                if ($desc) $slide['description'] = $desc;
            }
        }
        return $slides;
    });

    // 3. 静态文字翻译（支持 site_config 和自定义键）
    LabPress_Hooks::addFilter('translate', function($value, $key) {
            // 后台页面不翻译，保持原文
        if (defined('IS_ADMIN') || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
            return $value;
        }

        // 优先查自定义键
        $trans = ml_get_translation('__custom', $key, 'text');
        if ($trans) return $trans;

        // site_config 字段映射
        $siteMap = [
            'about_us'          => 'about_us',
            'about_us_content'  => 'about_us',
            'research_title'    => 'research_title',
            'research_heading'  => 'research_title',
            'research_subtitle' => 'research_subtitle',
            'research_subheading'=> 'research_subtitle',
        ];
        if (isset($siteMap[$key])) {
            $trans = ml_get_translation('site_config', $siteMap[$key], 'config_value');
            if ($trans) return $trans;
        }
        return $value;
    }, 10, 2);

    // 4. 插件自身界面翻译
    LabPress_Hooks::addFilter('translate', function($value, $key, $locale) {
        $trans = ml_get_translation('plugin_dynamic-multilang', $key, 'plugin_text', $locale);
        if ($trans !== null) return $trans;
        return $value;
    }, 10, 3);
});

// ========== 后台管理页面（独立文件，不影响前台） ==========
LabPress::registerAdminPage('dynamic-multilang', __('ml_plugin_name', 'plugin:dynamic-multilang'), function() {
    require_once __DIR__ . '/admin-page.php';
});

// 卸载钩子：删除翻译表
LabPress_Hooks::addAction('plugin_uninstalled', function($slug) {
    if ($slug === 'dynamic-multilang') {
        $db = LabPress::db();
        $db->exec("DROP TABLE IF EXISTS `ml_translations`");
    }
});