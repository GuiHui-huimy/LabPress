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

// 图表数据：内容构成 + 项目分类 + 最近 14 天新增趋势
$chartData = getDashboardChartData();
$chartData['content'] = [
    ['name' => __('stat_publications', 'admin'), 'value' => (int)$counts['publications']],
    ['name' => __('stat_projects', 'admin'), 'value' => (int)$counts['projects']],
    ['name' => __('stat_tools', 'admin'), 'value' => (int)$counts['tools']],
    ['name' => __('stat_news', 'admin'), 'value' => (int)$counts['news']],
    ['name' => __('stat_slides', 'admin'), 'value' => (int)$counts['slides']],
    ['name' => __('stat_users', 'admin'), 'value' => (int)$counts['users']],
];
$chartData['labels'] = [
    'empty' => __('chart_empty', 'admin'),
    'news' => __('chart_news', 'admin'),
    'projects' => __('chart_projects', 'admin'),
    'users' => __('chart_users', 'admin'),
];
$chartData = LabPress_Hooks::applyFilters('admin_dashboard_chart_data', $chartData);
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

<section class="section-head chart-section-head">
    <h3><?= e(__('dashboard_charts', 'admin')) ?></h3>
    <span class="text-muted"><?= e(__('dashboard_charts_note', 'admin')) ?></span>
</section>

<div class="dashboard-charts">
    <article class="chart-card">
        <div class="chart-card-head">
            <h4><?= e(__('chart_content', 'admin')) ?></h4>
            <span><?= e(__('chart_content_note', 'admin')) ?></span>
        </div>
        <div id="lpChartContent" class="chart-canvas" role="img" aria-label="<?= e(__('chart_content', 'admin')) ?>"></div>
    </article>
    <article class="chart-card">
        <div class="chart-card-head">
            <h4><?= e(__('chart_categories', 'admin')) ?></h4>
            <span><?= e(__('chart_categories_note', 'admin')) ?></span>
        </div>
        <div id="lpChartCategories" class="chart-canvas" role="img" aria-label="<?= e(__('chart_categories', 'admin')) ?>"></div>
    </article>
    <article class="chart-card chart-card-wide">
        <div class="chart-card-head">
            <h4><?= e(__('chart_trend', 'admin')) ?></h4>
            <span><?= e(__('chart_trend_note', 'admin')) ?></span>
        </div>
        <div id="lpChartTrend" class="chart-canvas chart-canvas-tall" role="img" aria-label="<?= e(__('chart_trend', 'admin')) ?>"></div>
    </article>
</div>

<script type="application/json" id="lpDashboardChartData"><?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
