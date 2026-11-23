<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$newsList = getNews();
?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
    <meta name="referrer" content="no-referrer">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle(__('page_title', 'news')) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/news.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="main-content">
        <div class="container">
            <h1 class="section-title" style="margin-bottom: 3rem;"><?= __('list_title', 'news') ?></h1>
            <?php if (empty($newsList)): ?>
                <p class="loading"><?= __('no_news', 'news') ?></p>
            <?php else: ?>
                <?php LabPress_Hooks::doAction('before_news_list'); ?>
                <?php foreach ($newsList as $index => $item): ?>
                    <?php
                    $id         = (int)$item['id'];
                    // 标题过滤
                    $rawTitle   = $item['title'];
                    $title      = htmlspecialchars(LabPress_Hooks::applyFilters('news_list_title', $rawTitle, $item));
                    // 摘要过滤
                    $rawSummary = $item['summary'];
                    $summary    = nl2br(htmlspecialchars(LabPress_Hooks::applyFilters('news_list_summary', $rawSummary, $item)));
                    $image      = htmlspecialchars($item['image'] ?? '');
                    $author     = htmlspecialchars($item['author'] ?? 'HUI-MY.LAB');
                    $date       = date(__('date_format', 'news'), strtotime($item['date']));
                    $detailLink = 'news-detail.php?id=' . $id;
                    ?>
                    <?php LabPress_Hooks::doAction('before_news_item', $item); ?>
                    <div class="news-item">
                        <div class="row">
                            <?php if ($image): ?>
                            <div class="news-image">
                                <a href="<?= $detailLink ?>">
                                    <img src="<?= $image ?>" alt="<?= $title ?>">
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="news-content">
                                <h3 class="news-title">
                                    <a href="<?= $detailLink ?>"><?= $title ?></a>
                                </h3>
                                <div class="news-meta">
                                    <span><i class="far fa-calendar-alt"></i> <?= $date ?></span>
                                    <span><i class="far fa-user"></i> <?= $author ?></span>
                                </div>
                                <div class="news-summary"><?= $summary ?></div>
                                <a href="<?= $detailLink ?>" class="read-more"><?= __('read_details', 'news') ?> <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                    <?php LabPress_Hooks::doAction('after_news_item', $item); ?>
                    <?php if ($index < count($newsList) - 1): ?>
                        <hr>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php LabPress_Hooks::doAction('after_news_list'); ?>
            <?php endif; ?>
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