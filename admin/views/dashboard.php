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
?>

<div class="card">
    <h3><?= __('system_overview', 'admin') ?></h3>
</div>

<?php LabPress_Hooks::doAction('admin_before_dashboard_stats'); ?>

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; margin-top:1.5rem;">
    <div class="card">
        <h4><?= __('stat_publications', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['publications'] ?></p>
    </div>
    <div class="card">
        <h4><?= __('stat_slides', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['slides'] ?></p>
    </div>
    <div class="card">
        <h4><?= __('stat_tools', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['tools'] ?></p>
    </div>
    <div class="card">
        <h4><?= __('stat_news', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['news'] ?></p>
    </div>
    <div class="card">
        <h4><?= __('stat_projects', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['projects'] ?></p>
    </div>
    <div class="card">
        <h4><?= __('stat_categories', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['categories'] ?></p>
    </div>
    <div class="card">
        <h4><?= __('stat_users', 'admin') ?></h4>
        <p style="font-size:2rem; font-weight:bold;"><?= $counts['users'] ?></p>
    </div>
</div>

<?php LabPress_Hooks::doAction('admin_after_dashboard_stats'); ?>