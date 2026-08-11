<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('Location: /');
    exit;
}

// 获取分类信息
$stmt = $pdo->prepare("SELECT name FROM project_categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch();
if (!$category) {
    header("HTTP/1.0 404 Not Found");
    echo '<!DOCTYPE html><html><head><title>' . __('category_not_found', 'projects') . '</title></head><body><p style="text-align:center;margin-top:4rem;">' . __('category_not_found', 'projects') . '</p></body></html>';
    exit;
}

$catName = $category['name'];

// 获取该分类下所有项目
$stmt = $pdo->prepare("SELECT * FROM projects WHERE category_slug = ? ORDER BY sort_order ASC, created_at DESC");
$stmt->execute([$slug]);
$projects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle($catName) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/index.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content" style="margin-top:80px; padding:3rem 0;">
        <div class="container">
            <h1 class="section-title"><?= htmlspecialchars($catName) ?></h1>
            <p class="section-subtitle"><?= sprintf(__('project_count', 'projects'), count($projects)) ?></p>

            <?php if (empty($projects)): ?>
                <p style="text-align:center; color:var(--text-light);"><?= __('no_projects', 'projects') ?></p>
            <?php else: ?>
                <div class="project-list">
                    <?php foreach ($projects as $proj): ?>
                        <?php
                        $img     = htmlspecialchars($proj['image'] ?? '');
                        $title   = htmlspecialchars($proj['title']);
                        $summary = htmlspecialchars($proj['summary']);
                        $git     = htmlspecialchars($proj['github_url'] ?? '');
                        $doc     = htmlspecialchars($proj['doc_url'] ?? '');
                        $ext     = htmlspecialchars($proj['external_url'] ?? '');
                        $detail  = 'project-detail.php?id=' . $proj['id'];
                        ?>
                        <div class="project-row">
                            <?php if ($img): ?>
                            <div class="project-row-img">
                                <a href="<?= $detail ?>">
                                    <img src="<?= $img ?>" alt="<?= $title ?>">
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="project-row-content">
                                <h3 class="project-row-title">
                                    <a href="<?= $detail ?>"><?= $title ?></a>
                                </h3>
                                <p class="project-row-summary"><?= $summary ?></p>
                                <div class="project-row-links">
                                    <a href="<?= $detail ?>" class="link-detail"><?= __('view_details', 'projects') ?> <i class="fas fa-arrow-right"></i></a>
                                    <?php if ($git): ?><a href="<?= $git ?>" target="_blank" class="link-icon" title="GitHub"><i class="fab fa-github"></i></a><?php endif; ?>
                                    <?php if ($doc): ?><a href="<?= $doc ?>" target="_blank" class="link-icon" title="文档"><i class="fas fa-book"></i></a><?php endif; ?>
                                    <?php if ($ext): ?><a href="<?= $ext ?>" target="_blank" class="link-icon" title="外部链接"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>
</body>
</html>