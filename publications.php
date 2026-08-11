<?php
// publications.php 修改后
require_once 'includes/config.php';
require_once 'includes/functions.php';
$publications = getPublications();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle(__('page_title', 'publications')) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/index.css">
    <style>
        .publication-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .publication-item:last-child { border-bottom: none; }
        .pub-info { flex: 1; }
        .pub-title { font-size: 1.1rem; font-weight: 600; color: var(--primary-dark); text-decoration: none; }
        .pub-title:hover { color: var(--accent-teal); }
        .pub-meta { font-size: 0.9rem; color: var(--text-light); margin-top: 0.3rem; }
        .pub-journal { font-style: italic; }
        .pub-link {
            display: inline-block;
            background: var(--bg-light);
            border: 1px solid var(--border-light);
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .pub-link:hover { background: var(--primary-dark); color: #fff; border-color: var(--primary-dark); }
    </style>
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <main class="main-content" style="margin-top:80px; padding:3rem 0; min-height:60vh;">
        <div class="container">
            <h1 class="section-title"><?= __('page_title', 'publications') ?></h1>
            <p class="section-subtitle"><?= __('page_subtitle', 'publications') ?></p>
            <?php if (empty($publications)): ?>
                <p style="text-align:center; color:var(--text-light);"><?= __('no_publications', 'publications') ?></p>
            <?php else: ?>
                <?php LabPress_Hooks::doAction('before_publications_list'); ?>
                <div class="publications-list">
                    <?php foreach ($publications as $pub): ?>
                        <?php
                        $filteredTitle = LabPress_Hooks::applyFilters('publication_list_title', $pub['title'], $pub);
                        ?>
                        <?php LabPress_Hooks::doAction('before_publication_item', $pub); ?>
                        <div class="publication-item">
                            <div class="pub-info">
                                <a href="<?= htmlspecialchars($pub['link'] ?? '#') ?>" target="_blank" rel="noopener" class="pub-title">
                                    <?= htmlspecialchars($filteredTitle) ?>
                                </a>
                                <div class="pub-meta">
                                    <span><?= htmlspecialchars($pub['authors']) ?></span> &mdash;
                                    <span class="pub-journal"><?= htmlspecialchars($pub['journal']) ?></span>
                                </div>
                            </div>
                            <div>
                                <a href="<?= htmlspecialchars($pub['link'] ?? '#') ?>" target="_blank" rel="noopener" class="pub-link">
                                    <i class="fas fa-external-link-alt"></i> <?= __('read_button', 'publications') ?>
                                </a>
                            </div>
                        </div>
                        <?php LabPress_Hooks::doAction('after_publication_item', $pub); ?>
                    <?php endforeach; ?>
                </div>
                <?php LabPress_Hooks::doAction('after_publications_list'); ?>
            <?php endif; ?>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
</body>
</html>