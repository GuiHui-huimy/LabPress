<?php
LabPress_Hooks::doAction('after_body_open');

if (!isset($isHomePage)) {
    $isHomePage = false;
}

$navMenus = getNavMenus();

$currentLocale = getLocale();
$languages = getAvailableLanguages();
$currentLang = array_values(array_filter($languages, fn($l) => $l['locale'] === $currentLocale));
$currentLang = !empty($currentLang) ? $currentLang[0] : null;
$navUserName = (string)($_SESSION['user'] ?? '');
?>
<link rel="stylesheet" href="assets/css/header.css">
<header class="site-header <?= $isHomePage ? '' : 'scrolled' ?>" id="site-header">
    <div class="container header-inner">
        <div class="logo">
            <a href="/"><span>LAB</span>.HUI-MY</a>
        </div>
        <div class="menu-toggle" id="menu-toggle"><i class="fas fa-bars"></i></div>
        <?php LabPress_Hooks::doAction('before_nav'); ?>
        <nav class="main-nav" id="main-nav">
            <ul>
                <?php foreach ($navMenus as $menu): ?>
                    <?php
                    $target = ($menu['target'] === '_blank') ? ' target="_blank" rel="noopener"' : '';
                    $activeClass = '';
                    if ($isHomePage && ($menu['url'] === '/index.php' || $menu['url'] === '/')) {
                        $activeClass = ' class="active"';
                    }
                    $menuItemHtml = sprintf(
                        '<a href="%s"%s%s>%s</a>',
                        htmlspecialchars($menu['url']),
                        $target,
                        $activeClass,
                        htmlspecialchars($menu['title'])
                    );
                    $menuItemHtml = LabPress_Hooks::applyFilters('nav_menu_item', $menuItemHtml, $menu);
                    ?>
                    <li><?= $menuItemHtml ?></li>
                <?php endforeach; ?>

                <?php if (!empty($languages) && count($languages) > 1): ?>
                <li class="lang-switcher">
                    <a href="#" id="langToggle" class="nav-lang-toggle">
                        <?php if ($currentLang): ?>
                            <?= htmlspecialchars($currentLang['flag'] ?? '🌐') ?> <?= htmlspecialchars($currentLang['name']) ?>
                        <?php else: ?>
                            🌐 <?= __('language_label') ?>
                        <?php endif; ?>
                        <i class="fas fa-caret-down"></i>
                    </a>
                    <ul class="lang-dropdown" id="langDropdown" style="display: none;">
                        <?php foreach ($languages as $lang): ?>
                            <li>
                                <a href="<?= e(lp_url_for_lang($lang['locale'])) ?>" class="lang-item">
                                    <?= htmlspecialchars($lang['flag'] ?? '🌐') ?> <?= htmlspecialchars($lang['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-auth">
                    <?php if ($navUserName !== ''): ?>
                        <div class="nav-account-menu">
                            <a class="nav-account" href="admin/index.php" title="<?= e(__('nav_admin')) ?>">
                                <i class="fas fa-user" aria-hidden="true"></i>
                                <span><?= e($navUserName) ?></span>
                                <i class="fas fa-chevron-down nav-account-caret" aria-hidden="true"></i>
                            </a>
                            <div class="account-dropdown">
                                <ul>
                                    <li><a href="admin/index.php"><?= e(__('nav_admin_dashboard')) ?></a></li>
                                    <?php if (hasPermission('publications')): ?>
                                        <li><a href="admin/index.php?view=publications"><?= e(__('nav_admin_publications')) ?></a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('tools')): ?>
                                        <li><a href="admin/index.php?view=projects-list"><?= e(__('nav_admin_projects')) ?></a></li>
                                        <li><a href="admin/index.php?view=tool-list"><?= e(__('nav_admin_tools')) ?></a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('publications')): ?>
                                        <li><a href="admin/index.php?view=news"><?= e(__('nav_admin_news')) ?></a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a class="nav-login" href="admin/login.php?redirect=home">
                            <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                            <span><?= e(__('nav_login')) ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
        <?php LabPress_Hooks::doAction('after_nav'); ?>
    </div>
</header>

<script>
    document.getElementById('menu-toggle').addEventListener('click', function() {
        document.getElementById('main-nav').classList.toggle('active');
    });

    <?php if ($isHomePage): ?>
    window.addEventListener('scroll', function() {
        const header = document.getElementById('site-header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
    <?php endif; ?>

    (function() {
        const toggle = document.getElementById('langToggle');
        const dropdown = document.getElementById('langDropdown');
        if (!toggle || !dropdown) return;

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.lang-switcher')) {
                dropdown.style.display = 'none';
            }
        });
    })();
</script>