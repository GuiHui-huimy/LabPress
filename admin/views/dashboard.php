<?php
$counts = [
    'publications' => count(getPublications()),
    'slides'       => count(getSlides()),
    'tools'        => count(getTools()),
    'news'         => count(getNews()),
    'users'        => count(getUsers()),
    'projects'     => count(getProjects()),
    'categories'   => count(getCategories())
];

// 允许插件修改仪表盘统计数据
$counts = LabPress_Hooks::applyFilters('admin_dashboard_counts', $counts);

// 统计卡片：图标 + 文案 + 跳转视图 + 所需权限
$statCards = [
    ['key' => 'publications', 'view' => 'publications',        'icon' => 'fa-book',              'label' => __('stat_publications', 'admin'), 'perm' => 'publications'],
    ['key' => 'projects',     'view' => 'projects',            'icon' => 'fa-project-diagram',   'label' => __('stat_projects', 'admin'),     'perm' => 'tools'],
    ['key' => 'tools',        'view' => 'tool-list',           'icon' => 'fa-tools',             'label' => __('stat_tools', 'admin'),        'perm' => 'tools'],
    ['key' => 'news',         'view' => 'news',                'icon' => 'fa-newspaper',         'label' => __('stat_news', 'admin'),         'perm' => 'publications'],
    ['key' => 'slides',       'view' => 'slides',              'icon' => 'fa-images',            'label' => __('stat_slides', 'admin'),       'perm' => 'slides'],
    ['key' => 'categories',   'view' => 'project-categories',  'icon' => 'fa-folder-open',       'label' => __('stat_categories', 'admin'),   'perm' => 'tools'],
    ['key' => 'users',        'view' => 'users',               'icon' => 'fa-users',             'label' => __('stat_users', 'admin'),        'perm' => 'users'],
];
$statCards = LabPress_Hooks::applyFilters('admin_dashboard_cards', $statCards);
$siteName = LabPress::config('site_name', 'HUI-MY.LAB');
?>

<section class="hero">
    <div>
        <div class="eyebrow"><?= e(__('console', 'admin')) ?></div>
        <h2><?= e($siteName) ?></h2>
        <p><?= e(__('dashboard_intro', 'admin')) ?></p>
    </div>
    <div class="hero-meta">
        <span class="dot-live" aria-hidden="true"></span>
        <span><?= e(__('systems_nominal', 'admin')) ?></span>
        <span class="sep">/</span>
        <span>v<?= e(LABPRESS_VERSION) ?></span>
    </div>
</section>

<?php LabPress_Hooks::doAction('admin_before_dashboard_stats'); ?>

<section class="section-head">
    <h3><?= e(__('system_overview', 'admin')) ?></h3>
    <span class="text-muted"><?= date('Y-m-d') ?></span>
</section>

<div class="stat-grid">
    <?php foreach ($statCards as $card): ?>
        <?php
        if (!isset($counts[$card['key']])) continue;
        $cardPerm = $card['perm'] ?? null;
        if ($cardPerm !== null && !hasPermission($cardPerm)) continue;
        ?>
        <a class="stat-card" href="?view=<?= e($card['view']) ?>">
            <div class="stat-icon"><i class="fas <?= e($card['icon']) ?>"></i></div>
            <div class="stat-label"><?= e($card['label']) ?></div>
            <div class="stat-value"><?= (int)$counts[$card['key']] ?></div>
            <div class="stat-link"><?= e(__('view_details', 'admin')) ?></div>
        </a>
    <?php endforeach; ?>
</div>

<?php LabPress_Hooks::doAction('admin_after_dashboard_stats'); ?>
