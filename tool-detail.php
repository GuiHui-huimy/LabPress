<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 获取工具名称
$toolName = $_GET['name'] ?? 'ProteinTrim';

// 从数据库获取详情和版本
$detail = getToolDetail($toolName);
$versions = getToolVersions($toolName);

// 如果详情不存在，设置默认值，避免未定义键报错
if (!$detail) {
    $detail = [];
}
// 设置所有用到的字段默认值（数据库字段名为 tool_name 而非 name）
$toolDisplayName = $detail['tool_name'] ?? $toolName;
$shortDesc        = $detail['short_description'] ?? '';
$description      = $detail['description'] ?? __('tool_no_detail', 'tools');
$detailedDesc     = $detail['detailed_description'] ?? __('tool_no_detail_html', 'tools');
$version          = $detail['version'] ?? '';
$language         = $detail['language'] ?? '';
$license          = $detail['license'] ?? '';
$updateDate       = $detail['update_date'] ?? '';
$github           = $detail['github'] ?? '';
$document         = $detail['document'] ?? '';
$homepage         = $detail['homepage'] ?? '';
$image            = $detail['image'] ?? '';
$screenshot       = $detail['screenshot'] ?? '';
$citation         = $detail['citation'] ?? null;

// 标题过滤（允许插件修改，传递类型 'tool'）
$rawTitle = $toolDisplayName;
$title = htmlspecialchars(LabPress_Hooks::applyFilters('page_title', $rawTitle, 'tool'));

// 内容过滤（允许插件修改，传递类型 'tool'）
$content = LabPress_Hooks::applyFilters('the_content', $detailedDesc, 'tool');
?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle($toolDisplayName) ?></title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/tool_detail.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main-content">
        <?php LabPress_Hooks::doAction('before_content'); ?>

        <!-- 顶部大区域 -->
        <div class="hero-section">
            <div class="container">
                <div class="hero-grid">
                    <div class="hero-left">
                        <h1 class="hero-title"><?= $title ?></h1>
                        <p class="hero-description"><?= htmlspecialchars($shortDesc ?: $description) ?></p>
                        <?php if ($version): ?>
                            <a href="#versions" class="hero-button"><i class="fas fa-download"></i> <?= __('download', 'tools') ?> <?= htmlspecialchars($version) ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="hero-right">
                        <?php if ($image): ?>
                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= $title ?>" class="hero-image">
                        <?php else: ?>
                            <i class="fas fa-cube hero-icon"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <!-- 详细信息 -->
            <div class="info-section">
                <div class="info-grid">
                    <div class="detail-text">
                        <?= $content ?>
                        <?php if (!empty($citation['title'])): ?>
                            <div class="citation-box">
                                <h3><?= __('citation', 'tools') ?></h3>
                                <p><a href="<?= htmlspecialchars($citation['url'] ?? '#') ?>" target="_blank"><?= htmlspecialchars($citation['title']) ?></a></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($screenshot): ?>
                            <div class="screenshot-box">
                                <img src="<?= htmlspecialchars($screenshot) ?>" alt="<?= $title ?> 截图">
                                <div class="screenshot-caption"><?= __('screenshot_caption', 'tools') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="meta-panel">
                        <?php if ($version): ?>
                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-tag"></i></div>
                                <div class="meta-content">
                                    <div class="meta-label"><?= __('latest_version', 'tools') ?></div>
                                    <div class="meta-value"><?= htmlspecialchars($version) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($language): ?>
                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-code"></i></div>
                                <div class="meta-content">
                                    <div class="meta-label"><?= __('dev_language', 'tools') ?></div>
                                    <div class="meta-value"><?= htmlspecialchars($language) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($license): ?>
                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-scale-balanced"></i></div>
                                <div class="meta-content">
                                    <div class="meta-label"><?= __('license', 'tools') ?></div>
                                    <div class="meta-value"><?= htmlspecialchars($license) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($updateDate): ?>
                            <div class="meta-item">
                                <div class="meta-icon"><i class="fas fa-calendar-alt"></i></div>
                                <div class="meta-content">
                                    <div class="meta-label"><?= __('updated_on', 'tools') ?></div>
                                    <div class="meta-value"><?= htmlspecialchars($updateDate) ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <div class="button-group">
                            <?php if ($github): ?>
                                <a href="<?= htmlspecialchars($github) ?>" target="_blank" class="btn-outline"><i class="fab fa-github"></i> <?= __('github', 'tools') ?></a>
                            <?php endif; ?>
                            <?php if ($document): ?>
                                <a href="<?= htmlspecialchars($document) ?>" target="_blank" class="btn-outline"><i class="fas fa-book"></i> <?= __('documentation', 'tools') ?></a>
                            <?php endif; ?>
                            <?php if ($homepage): ?>
                                <a href="<?= htmlspecialchars($homepage) ?>" target="_blank" class="btn-outline"><i class="fas fa-globe"></i> <?= __('forum', 'tools') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php LabPress_Hooks::doAction('before_sidebar'); ?>
            <?php LabPress_Hooks::doAction('after_sidebar'); ?>

            <!-- 版本历史 -->
            <?php if (!empty($versions)): ?>
            <div class="version-section" id="versions">
                <h2 class="version-title"><?= __('version_history', 'tools') ?></h2>
                <?php foreach ($versions as $ver): ?>
                <div class="version-card">
                    <div class="version-header">
                        <span class="version-number"><?= htmlspecialchars($ver['version']) ?></span>
                        <span class="version-date"><?= htmlspecialchars($ver['date']) ?></span>
                    </div>
                    <?php if (!empty($ver['changes'])): ?>
                        <ul class="version-changes">
                            <?php foreach ($ver['changes'] as $change): ?>
                                <li><?= htmlspecialchars($change) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <?php if (!empty($ver['downloads'])): ?>
                        <div class="download-list">
                            <?php foreach ($ver['downloads'] as $dl): ?>
                                <a href="<?= htmlspecialchars($dl['url'] ?? '#') ?>" class="download-item" target="_blank">
                                    <i class="<?= htmlspecialchars($dl['icon'] ?? 'fas fa-download') ?>"></i>
                                    <span><?= htmlspecialchars($dl['name']) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="no-download"><?= __('no_downloads', 'tools') ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php LabPress_Hooks::doAction('comments_area'); ?>
        </div>

        <?php LabPress_Hooks::doAction('after_content'); ?>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>