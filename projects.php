<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// 获取所有分类
$categories = getCategories();
$catNames = [];
foreach ($categories as $c) {
    $catNames[$c['slug']] = $c['name'];
}

// 当前分类
$currentSlug = $_GET['category'] ?? '';

// 分页参数
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$perPage = 8;

if ($currentSlug !== '') {
    $totalCount = getProjectsCount($currentSlug);
    $projects = getProjectsPaginated($page, $perPage, $currentSlug);
} else {
    $totalCount = getProjectsCount();
    $projects = getProjectsPaginated($page, $perPage);
}

$totalPages = ceil($totalCount / $perPage);

// 页面标题
if ($currentSlug !== '') {
    $pageTitle = sprintf(__('page_title_suffix', 'projects'), htmlspecialchars($catNames[$currentSlug] ?? $currentSlug));
} else {
    $pageTitle = __('all_projects_title', 'projects');
}
?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle($pageTitle) ?></title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/projects.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php
    $isHomePage = false;
    include 'includes/header.php';
    ?>

    <main class="main-content" style="margin-top:80px; padding:3rem 0;">
        <div class="container">
            <h1 class="section-title" style="margin-bottom: 2rem;"><?= $pageTitle ?></h1>

            <?php if (empty($categories)): ?>
                <p style="text-align:center; color:var(--text-light);"><?= __('no_category_data', 'projects') ?></p>
            <?php else: ?>
                <div class="row-flex">
                    <!-- 左侧分类导航 -->
                    <div class="sidebar-col">
                        <div class="sidebar-card">
                            <div class="card-header">
                                <i class="fas fa-tags mr-2"></i><?= __('project_categories_label', 'projects') ?>
                            </div>
                            <ul class="list-group">
                                <a href="projects.php" class="list-group-item <?= $currentSlug === '' ? 'active' : '' ?>">
                                    <?= __('all_projects', 'projects') ?> <span class="badge"><?= getProjectsCount() ?></span>
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                    <a href="projects.php?category=<?= urlencode($cat['slug']) ?>" 
                                       class="list-group-item <?= $currentSlug === $cat['slug'] ? 'active' : '' ?>">
                                        <?= htmlspecialchars($cat['name']) ?> 
                                        <span class="badge"><?= getProjectsCount($cat['slug']) ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- 右侧项目列表 -->
                    <div class="content-col">
                        <?php if (empty($projects)): ?>
                            <div class="empty-state">
                                <i class="fas fa-folder-open fa-3x mb-3" style="opacity:0.3;"></i>
                                <p><?= __('no_projects_in_category', 'projects') ?></p>
                            </div>
                        <?php else: ?>
                            <?php LabPress_Hooks::doAction('before_projects_list'); ?>
                            <div class="project-list">
                                <?php foreach ($projects as $proj): ?>
                                    <?php
                                    $img     = htmlspecialchars($proj['image'] ?? '');
                                    $title   = htmlspecialchars(LabPress_Hooks::applyFilters('project_list_title', $proj['title'], $proj));
                                    $summary = htmlspecialchars(LabPress_Hooks::applyFilters('project_list_summary', $proj['summary'], $proj));
                                    $git     = htmlspecialchars($proj['github_url'] ?? '');
                                    $doc     = htmlspecialchars($proj['doc_url'] ?? '');
                                    $ext     = htmlspecialchars($proj['external_url'] ?? '');
                                    $slug    = htmlspecialchars($proj['category_slug']);
                                    $catName = htmlspecialchars($catNames[$proj['category_slug']] ?? $slug);
                                    $detail  = 'project-detail.php?id=' . $proj['id'];
                                    $catUrl  = 'category.php?slug=' . urlencode($slug);
                                    ?>
                                    <?php LabPress_Hooks::doAction('before_project_item', $proj); ?>
                                    <div class="project-row">
                                        <?php if ($img): ?>
                                        <div class="project-row-img">
                                            <a href="<?= $detail ?>">
                                                <img src="<?= $img ?>" alt="<?= $title ?>">
                                            </a>
                                        </div>
                                        <?php endif; ?>
                                        <div class="project-row-content">
                                            <a href="<?= $catUrl ?>" class="project-cat-tag"><?= $catName ?></a>
                                            <h3 class="project-row-title">
                                                <a href="<?= $detail ?>"><?= $title ?></a>
                                            </h3>
                                            <p class="project-row-summary"><?= $summary ?></p>
                                            <div class="project-row-links">
                                                <a href="<?= $detail ?>" class="link-detail"><?= __('view_details', 'projects') ?> <i class="fas fa-arrow-right"></i></a>
                                                <?php if ($git): ?>
                                                    <a href="<?= $git ?>" target="_blank" class="link-icon" title="GitHub"><i class="fab fa-github"></i></a>
                                                <?php endif; ?>
                                                <?php if ($doc): ?>
                                                    <a href="<?= $doc ?>" target="_blank" class="link-icon" title="文档"><i class="fas fa-book"></i></a>
                                                <?php endif; ?>
                                                <?php if ($ext): ?>
                                                    <a href="<?= $ext ?>" target="_blank" class="link-icon" title="外部链接"><i class="fas fa-external-link-alt"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php LabPress_Hooks::doAction('after_project_item', $proj); ?>
                                <?php endforeach; ?>
                            </div>
                            <?php LabPress_Hooks::doAction('after_projects_list'); ?>

                            <!-- 分页导航 -->
                            <?php if ($totalPages > 1): ?>
                                <nav class="pagination-nav">
                                    <ul class="pagination">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                                                    <i class="fas fa-chevron-left"></i> <?= __('page_prev') ?>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled"><span class="page-link"><i class="fas fa-chevron-left"></i> <?= __('page_prev') ?></span></li>
                                        <?php endif; ?>

                                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $totalPages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                                                    <?= __('page_next') ?> <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php else: ?>
                                            <li class="page-item disabled"><span class="page-link"><?= __('page_next') ?> <i class="fas fa-chevron-right"></i></span></li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>