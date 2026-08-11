<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$project = getProjectById($id);
if (!$project) {
    header("HTTP/1.0 404 Not Found");
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>' . __('project_not_found', 'projects') . '</title></head><body><p style="text-align:center;margin-top:4rem;">' . __('project_not_found', 'projects') . '</p></body></html>';
    exit;
}

// 获取分类名称
$categoryName = '';
if (!empty($project['category_slug'])) {
    $stmt = $pdo->prepare("SELECT name FROM project_categories WHERE slug = ?");
    $stmt->execute([$project['category_slug']]);
    $cat = $stmt->fetch();
    $categoryName = $cat ? $cat['name'] : $project['category_slug'];
}

// 原始标题
$rawTitle = $project['title'];
// 标题过滤（允许插件修改，传递类型 'project'）
$title = htmlspecialchars(LabPress_Hooks::applyFilters('page_title', $rawTitle, 'project'));

$rawContent = !empty($project['content']) ? $project['content'] : $project['summary'];
$contentHtml = markdownToHtml($rawContent);
// 内容过滤（允许插件修改，传递类型 'project'）
$content = LabPress_Hooks::applyFilters('the_content', $contentHtml, 'project');

$image     = htmlspecialchars($project['image'] ?? '');
$git       = htmlspecialchars($project['github_url'] ?? '');
$doc       = htmlspecialchars($project['doc_url'] ?? '');
$ext       = htmlspecialchars($project['external_url'] ?? '');
$catUrl    = 'projects.php?category=' . urlencode($project['category_slug']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle($title) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/project-detail.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php
    $isHomePage = false;
    include 'includes/header.php';
    ?>

    <main class="main-content" style="margin-top:80px; padding:3rem 0; min-height:70vh;">
        <article class="container">
            <?php LabPress_Hooks::doAction('before_content'); ?>

            <!-- 项目头部 -->
            <header class="article-header">
                <?php if ($categoryName): ?>
                    <a href="<?= $catUrl ?>" class="project-cat-tag"><?= htmlspecialchars($categoryName) ?></a>
                <?php endif; ?>
                <h1 class="article-title"><?= $title ?></h1>
            </header>

            <!-- 特色图片 -->
            <?php if ($image): ?>
                <div class="article-featured-image">
                    <img src="<?= $image ?>" alt="<?= $title ?>">
                </div>
            <?php endif; ?>

            <!-- 正文区域 -->
            <div class="article-content">
                <?= $content ?>
            </div>

            <?php LabPress_Hooks::doAction('before_sidebar'); ?>
            <!-- 侧边栏位置（当前无侧边栏，预留钩子） -->
            <?php LabPress_Hooks::doAction('after_sidebar'); ?>

            <!-- 操作按钮 -->
            <div class="btn-row">
                <a href="projects.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> <?= __('back_to_list', 'projects') ?>
                </a>
                <?php if ($git): ?>
                    <a href="<?= $git ?>" target="_blank" class="btn btn-github">
                        <i class="fab fa-github"></i> <?= __('github', 'projects') ?>
                    </a>
                <?php endif; ?>
                <?php if ($doc): ?>
                    <a href="<?= $doc ?>" target="_blank" class="btn btn-doc">
                        <i class="fas fa-book"></i> <?= __('document', 'projects') ?>
                    </a>
                <?php endif; ?>
                <?php if ($ext): ?>
                    <a href="<?= $ext ?>" target="_blank" class="btn btn-external">
                        <i class="fas fa-external-link-alt"></i> <?= __('external_link', 'projects') ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php LabPress_Hooks::doAction('comments_area'); ?>

            <?php LabPress_Hooks::doAction('after_content'); ?>
        </article>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>