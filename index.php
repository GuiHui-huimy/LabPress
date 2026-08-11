<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php
// 获取站点配置
$siteConfig = getSiteConfig();
$siteName   = $siteConfig['site_name'] ?? 'HUI-MY.LAB';
$aboutUs    = $siteConfig['about_us'] ?? '';
$researchTitle = $siteConfig['research_title'] ?? __('research_title', 'index');
$researchSubtitle = $siteConfig['research_subtitle'] ?? __('research_subtitle', 'index');
$researchCardsRaw = $siteConfig['research_cards'] ?? '[]';
$researchCards = json_decode($researchCardsRaw, true) ?: [];
$statItems = json_decode($siteConfig['stat_items'] ?? '[]', true) ?: [];

// 其他原有数据
$categories = getCategories();
$featuredProjects = getProjects(true, 8);
$catDetails = [];
$catNames = [];
foreach ($categories as $c) {
    $catDetails[$c['slug']] = $c['description'];
    $catNames[$c['slug']] = $c['name'];
}
?>


<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= getFullTitle(__('index_title', 'index')) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    <link rel="stylesheet" href="assets/css/index.css">
    <?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <?php $isHomePage = true; ?>
    <?php include 'includes/header.php'; ?>

    <!-- 轮播图区域 -->
    <?php LabPress_Hooks::doAction('before_hero_slider'); ?>
    <section class="hero-slider" id="home">
        <div class="slider-container" id="slider-container">
            <div class="slide active" style="background-image: url('uploads/slide1.jpg');">
                <div class="slide-content">
                    <h1><?= __('hero_slide1_title', 'index') ?></h1>
                    <p><?= __('hero_slide1_desc', 'index') ?></p>
                </div>
            </div>
            <div class="slide" style="background-image: url('uploads/slide2.jpg');">
                <div class="slide-content">
                    <h1><?= __('hero_slide2_title', 'index') ?></h1>
                    <p><?= __('hero_slide2_desc', 'index') ?></p>
                </div>
            </div>
            <div class="slide" style="background-image: url('uploads/slide3.jpg');">
                <div class="slide-content">
                    <h1><?= __('hero_slide3_title', 'index') ?></h1>
                    <p><?= __('hero_slide3_desc', 'index') ?></p>
                </div>
            </div>
            <div class="slide" style="background-image: url('uploads/slide4.jpg');">
                <div class="slide-content">
                    <h1><?= __('hero_slide4_title', 'index') ?></h1>
                    <p><?= __('hero_slide4_desc', 'index') ?></p>
                </div>
            </div>
        </div>
        <div class="slider-indicators" id="slider-indicators">
            <button class="indicator active" data-index="0"></button>
            <button class="indicator" data-index="1"></button>
            <button class="indicator" data-index="2"></button>
            <button class="indicator" data-index="3"></button>
        </div>
    </section>
    <?php LabPress_Hooks::doAction('after_hero_slider'); ?>

    <!-- 关于我们 -->
    <?php LabPress_Hooks::doAction('before_about_section'); ?>
    <section class="section">
        <div class="container">
            <h2 class="section-title"><?= __('about_us_title', 'index') ?></h2>
            <div class="about-content">
                <div class="about-text">
                    <?= __('about_us_content', 'index') ?: $aboutUs ?>
                </div>
                <div class="about-stats">
                    <?php foreach ($statItems as $stat): 
                        $statValue = LabPress_Hooks::applyFilters('stat_value', $stat['value'], $stat);
                    ?>
                        <div class="stat-item">
                            <?php if (!empty($stat['icon'])): ?>
                                <div class="stat-icon" style="font-size:2rem; color:var(--accent-teal); margin-bottom:0.5rem;">
                                    <i class="<?= htmlspecialchars($stat['icon']) ?>"></i>
                                </div>
                            <?php endif; ?>
                            <div class="stat-number"><?= htmlspecialchars($statValue) ?></div>
                            <div class="stat-label"><?= __($stat['label'], 'index') ?: htmlspecialchars($stat['label']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <?php LabPress_Hooks::doAction('after_about_section'); ?>

    <!-- 研究方向 -->
    <?php LabPress_Hooks::doAction('before_research_section'); ?>
    <section class="section bg-light">
        <div class="container">
            <div class="research-image"></div>
            <h2 class="section-title"><?= __('research_heading', 'index') ?: htmlspecialchars($researchTitle) ?></h2>
            <p class="section-subtitle"><?= __('research_subheading', 'index') ?: htmlspecialchars($researchSubtitle) ?></p>
            <div class="research-grid">
                <?php foreach ($researchCards as $card): ?>
                    <div class="research-card">
                        <div class="research-icon"><i class="fas fa-<?= htmlspecialchars($card['icon'] ?? 'flask') ?>"></i></div>
                        <h3><?= __($card['title'] ?? '', 'index') ?: htmlspecialchars($card['title'] ?? '') ?></h3>
                        <p><?= __($card['desc'] ?? '', 'index') ?: htmlspecialchars($card['desc'] ?? '') ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php LabPress_Hooks::doAction('after_research_section'); ?>

    <!-- 项目与成果 -->
    <?php LabPress_Hooks::doAction('before_projects_section'); ?>
    <section class="section">
        <div class="container">
            <h2 class="section-title"><?= __('projects_title', 'index') ?></h2>
            <p class="section-subtitle"><?= __('projects_subtitle', 'index') ?></p>

            <div class="project-list">
                <?php foreach ($featuredProjects as $proj): ?>
                    <?php
                    $img     = htmlspecialchars($proj['image'] ?? '');
                    $title   = htmlspecialchars($proj['title']);
                    $summary = htmlspecialchars($proj['summary']);
                    $git     = htmlspecialchars($proj['github_url'] ?? '');
                    $doc     = htmlspecialchars($proj['doc_url'] ?? '');
                    $ext     = htmlspecialchars($proj['external_url'] ?? '');
                    $slug    = htmlspecialchars($proj['category_slug']);
                    $catName = htmlspecialchars($catNames[$proj['category_slug']] ?? $slug);
                    $detail  = 'project-detail.php?id=' . $proj['id'];
                    $catUrl  = 'category.php?slug=' . urlencode($slug);
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
                            <a href="<?= $catUrl ?>" class="project-cat-tag"><?= $catName ?></a>
                            <h3 class="project-row-title">
                                <a href="<?= $detail ?>"><?= $title ?></a>
                            </h3>
                            <p class="project-row-summary"><?= $summary ?></p>
                            <div class="project-row-links">
                                <a href="<?= $detail ?>" class="link-detail"><?= __('view_details', 'index') ?> <i class="fas fa-arrow-right"></i></a>
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
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 2.5rem;">
                <a href="projects.php" class="btn-more"><?= __('learn_more', 'index') ?> <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </section>
    <?php LabPress_Hooks::doAction('after_projects_section'); ?>

    <!-- 最新成果 -->
    <?php LabPress_Hooks::doAction('before_publications_section'); ?>
    <section class="section bg-light">
        <div class="container">
            <h2 class="section-title"><?= __('publications_title', 'index') ?></h2>
            <div id="publications-list" class="publications-list">
                <div class="loading"><i class="fas fa-spinner fa-pulse"></i> <?= __('loading_publications', 'index') ?></div>
            </div>
            <div id="publications-more" style="text-align:center; margin-top:1.5rem; display:none;"></div>
        </div>
    </section>
    <?php LabPress_Hooks::doAction('after_publications_section'); ?>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/project.js"></script>
    <script src="assets/js/index.js"></script>
</body>
</html>