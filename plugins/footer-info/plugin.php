<?php
/**
 * Plugin Name: Footer Info
 * Description: 可编辑页脚信息，支持多行文字、占位符、自定义样式（对齐、大小、颜色、边距），设置由 Plugin Settings API 管理。
 * Version: 3.0.0
 * Author: LabPress Community
 * Requires LabPress: >=1.3.0
 */

use LabPress\Plugin\Settings;

// 1) 用 Settings API 注册表单。页面由 Registry 统一注册，插件不再自查权限。
$footerInfoSchema = [
    ['key' => 'content', 'type' => 'textarea', 'label' => 'footer_info_content', 'label_module' => 'plugin:footer-info', 'default' => "© {year} {site_name}. All rights reserved.\nPowered by LabPress\nContact: admin@example.com", 'required' => true, 'maxlength' => 2000],
    ['key' => 'align', 'type' => 'select', 'label' => 'footer_info_align', 'label_module' => 'plugin:footer-info', 'options' => ['left' => 'footer_info_align_left', 'center' => 'footer_info_align_center', 'right' => 'footer_info_align_right'], 'options_module' => 'plugin:footer-info', 'default' => 'center'],
    ['key' => 'font_size', 'type' => 'select', 'label' => 'footer_info_font_size', 'label_module' => 'plugin:footer-info', 'options' => ['0.75rem' => 'footer_info_size_small', '0.9rem' => 'footer_info_size_medium', '1.1rem' => 'footer_info_size_large'], 'options_module' => 'plugin:footer-info', 'default' => '0.9rem'],
    ['key' => 'color', 'type' => 'color', 'label' => 'footer_info_color', 'label_module' => 'plugin:footer-info', 'default' => '#666666'],
    ['key' => 'padding', 'type' => 'select', 'label' => 'footer_info_padding', 'label_module' => 'plugin:footer-info', 'options' => ['0 10px' => '10px', '0 20px' => '20px', '0 30px' => '30px', '0 40px' => '40px'], 'default' => '0 20px'],
];
Settings::register('footer-info', $footerInfoSchema, __('footer_info_title', 'plugin:footer-info'), 'all', 'footer-info-settings');

/**
 * 旧 footer_info 表读取。仅用于 v2 -> v3 的一次性迁移和回退。
 *
 * @return array<string,string>
 */
function footer_info_legacy(): array
{
    try {
        $db = LabPress::db();
        $stmt = $db->query('SELECT content, align, font_size, color, padding FROM footer_info WHERE id = 1 LIMIT 1');
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        return is_array($row) ? $row : [];
    } catch (Throwable) {
        return [];
    }
}

/**
 * 迁移旧表到 plugins.settings_json。幂等：settings 已有 content 时不再覆盖。
 */
function footer_info_migrate_legacy(): void
{
    $settings = Settings::saved('footer-info');
    if (trim((string)($settings['content'] ?? '')) !== '') {
        return;
    }
    $legacy = footer_info_legacy();
    if (trim((string)($legacy['content'] ?? '')) === '') {
        return;
    }
    Settings::save('footer-info', [
        'content' => (string)$legacy['content'],
        'align' => (string)($legacy['align'] ?? 'center'),
        'font_size' => (string)($legacy['font_size'] ?? '0.9rem'),
        'color' => (string)($legacy['color'] ?? '#666666'),
        'padding' => (string)($legacy['padding'] ?? '0 20px'),
    ]);
}

footer_info_migrate_legacy();

// 2) 兼容旧 slug 的激活逻辑；不再创建表，也不再写入旧表。
LabPress_Hooks::addAction('plugin_activation', function($slug) {
    if (in_array(strtolower((string)$slug), ['footerinfo', 'footer-info'], true)) {
        footer_info_migrate_legacy();
    }
});

// 3) 前端输出：优先 settings_json；旧表仅在迁移期间兜底。
LabPress_Hooks::addAction('footer_scripts', function() {
    $saved = Settings::saved('footer-info');
    $settings = Settings::all('footer-info');
    if (trim((string)($saved['content'] ?? '')) === '') {
        $legacy = footer_info_legacy();
        if (trim((string)($legacy['content'] ?? '')) !== '') {
            $settings = array_replace($settings, $legacy);
        }
        $content = (string)($settings['content'] ?? '');
        if (trim($content) === '') {
            return;
        }
    } else {
        $content = (string)($saved['content'] ?? '');
    }

    $align = (string)($settings['align'] ?? 'center');
    $fontSize = (string)($settings['font_size'] ?? '0.9rem');
    $color = (string)($settings['color'] ?? '#666666');
    $padding = (string)($settings['padding'] ?? '0 20px');

    $content = str_replace(['{year}', '{site_name}'], [date('Y'), (string)LabPress::config('site_name', 'LabPress')], $content);
    $lines = array_filter(array_map('trim', explode("\n", $content)), static fn(string $line): bool => $line !== '');

    echo '<div style="text-align:' . htmlspecialchars($align) . '; ' .
        'font-size:' . htmlspecialchars($fontSize) . '; ' .
        'color:' . htmlspecialchars($color) . '; ' .
        'padding:' . htmlspecialchars($padding) . '; margin-top:20px;">';
    foreach ($lines as $line) {
        echo '<div>' . $line . '</div>';
    }
    echo '</div>';
});

// 卸载保留旧表与 settings_json，避免迁移窗口丢数据。
LabPress_Hooks::addAction('plugin_uninstalled', function($slug) {
    if (in_array(strtolower((string)$slug), ['footerinfo', 'footer-info'], true)) {
        if (class_exists(\LabPress\Support\Logger::class)) {
            \LabPress\Support\Logger::info('footer-info uninstalled; legacy data retained', ['plugin' => 'footer-info']);
        }
    }
});
