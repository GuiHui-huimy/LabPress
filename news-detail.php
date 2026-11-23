<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$news = getNewsById($id);
if (!$news) {
    header("HTTP/1.0 404 Not Found");
    echo '<!DOCTYPE html><html <?= lp_html_lang_attr() ?>><head><meta charset="UTF-8"><title>' . __('not_found_title', 'news') . '</title></head><body><p style="text-align:center;margin-top:4rem;">' . __('not_found_text', 'news') . '</p></body></html>';
    exit;
}

$rawTitle = $news['title'];
$title = htmlspecialchars(LabPress_Hooks::applyFilters('page_title', $rawTitle, 'news'));

$rawContent = !empty($news['content']) ? $news['content'] : $news['summary'];
$contentHtml = markdownToHtml($rawContent);
$content = LabPress_Hooks::applyFilters('the_content', $contentHtml, 'news');

$image      = htmlspecialchars($news['image'] ?? '');
$author     = htmlspecialchars($news['author'] ?? __('author_default', 'news'));
$date       = date(__('date_format', 'news'), strtotime($news['date']));
?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle($title) ?></title>
    <link rel="stylesheet" href="assets/fontawesome/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/news-detail.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <article>
                <?php LabPress_Hooks::doAction('before_content'); ?>

                <header class="article-header">
                    <h1 class="article-title"><?= $title ?></h1>
                    <div class="article-meta">
                        <span><i class="far fa-calendar-alt"></i> <?= $date ?></span>
                        <span><i class="far fa-user"></i> <?= $author ?></span>
                    </div>
                </header>

                <?php if ($image): ?>
                <div class="article-featured-image">
                    <img src="<?= $image ?>" alt="<?= $title ?>">
                </div>
                <?php endif; ?>

                <div class="article-content">
                    <?= $content ?>
                </div>

                <?php LabPress_Hooks::doAction('before_sidebar'); ?>
                <?php LabPress_Hooks::doAction('after_sidebar'); ?>

                <a href="news.php" class="back-link"><i class="fas fa-arrow-left"></i> <?= __('back_to_list', 'news') ?></a>

                <?php LabPress_Hooks::doAction('comments_area'); ?>

                <?php LabPress_Hooks::doAction('after_content'); ?>
            </article>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script>
        document.getElementById('menu-toggle').addEventListener('click', function() {
            document.getElementById('main-nav').classList.toggle('active');
        });
    </script>
</body>
</html>