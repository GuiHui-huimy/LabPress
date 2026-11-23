<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php
// 获取工具列表
$tools = getTools();

// 应用过滤器，允许插件修改每个工具的数据
$tools = array_map(function($tool) {
    return LabPress_Hooks::applyFilters('tool_list_item', $tool);
}, $tools);

// 准备 JS 语言包
$toolLang = [
    'view_details' => __('view_details', 'tools'),
    'features_label' => __('features_label', 'tools'),
    'no_features' => __('no_features', 'tools'),
];
?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle(__('page_title', 'tools')) ?></title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/tools.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main-content">
        <div class="container">
            <h1 class="page-title"><?= __('heading', 'tools') ?></h1>
            <p style="color: var(--text-light); margin-bottom: 3rem; font-size: 1.1rem;"><?= __('subtitle', 'tools') ?></p>
            <?php LabPress_Hooks::doAction('before_tools_list'); ?>
            <div id="tools-container">
                <div class="loading"><i class="fas fa-spinner fa-pulse"></i> <?= __('loading', 'tools') ?></div>
            </div>
            <?php LabPress_Hooks::doAction('after_tools_list'); ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    <!-- 将过滤后的工具数据输出为 JavaScript 变量 -->
    <script>
        window.__TOOLS_DATA = <?= json_encode($tools, JSON_UNESCAPED_UNICODE) ?>;
        window.__TOOLS_LANG = <?= json_encode($toolLang, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('tools-container');
            const tools = window.__TOOLS_DATA || [];
            const L = window.__TOOLS_LANG || {};
            if (!tools || tools.length === 0) {
                container.innerHTML = '<p class="loading">' + L.no_features || '暂无' + '</p>';
                return;
            }
            let html = '';
            tools.forEach(tool => {
                let linksHtml = '';
                if (tool.github) linksHtml += `<a href="${tool.github}" target="_blank" title="GitHub"><i class="fab fa-github"></i></a>`;
                if (tool.website) linksHtml += `<a href="${tool.website}" target="_blank" title="官方网站"><i class="fas fa-globe"></i></a>`;
                const linksDisplay = linksHtml ? `<div class="tool-links">${linksHtml}</div>` : '';
                html += `
                    <div class="tool-card">
                        <div class="tool-card-body">
                            <div class="tool-header">
                                <div class="tool-title"><span class="tool-badge">${tool.type}</span><h2 class="tool-name">${tool.name}</h2></div>
                                ${linksDisplay}
                            </div>
                            <div class="tool-content">
                                <div class="tool-image"><i class="${tool.icon || 'fas fa-cube'}"></i></div>
                                <div class="tool-description"><p>${tool.description}</p><p><strong>${L.features_label || '主要特性'}：</strong> ${tool.features || (L.no_features || '暂无')}</p></div>
                            </div>
                            <div class="tool-footer">
                                <a href="tool-detail.php?name=${encodeURIComponent(tool.name)}" class="btn-detail">${L.view_details || '查看详情'} <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>`;
            });
            container.innerHTML = html;
        });
    </script>
</body>
</html>