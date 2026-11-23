<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
define('IS_ADMIN', true);
// 登录验证
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$view = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($_GET['view'] ?? 'dashboard'));
if ($view === '') {
    $view = 'dashboard';
}

// 可访问视图及对应的权限（用于侧边栏菜单判断）
$menu = [
    'dashboard'    => ['title' => __('dashboard', 'admin'), 'perm' => null],
    'publications' => ['title' => __('publications_mgr', 'admin'), 'perm' => 'publications'],
    'slides'       => ['title' => __('slides_mgr', 'admin'), 'perm' => 'slides'],
    // 工具管理 - 二级菜单
    'tools' => [
        'title' => __('tools_mgr', 'admin'),
        'perm'  => 'tools',
        'children' => [
            'tool-list'     => ['title' => __('tool_list', 'admin'), 'perm' => 'tools'],
            'tool-detail'   => ['title' => __('tool_detail', 'admin'), 'perm' => 'tools'],
            'tool-versions' => ['title' => __('tool_versions', 'admin'), 'perm' => 'tools'],
        ]
    ],
    // 项目管理 - 二级菜单
    'projects' => [
        'title' => __('projects_mgr', 'admin'),
        'perm'  => 'tools',
        'children' => [
            'project-categories' => ['title' => __('project_categories', 'admin'), 'perm' => 'tools'],
            'projects-list'      => ['title' => __('project_list', 'admin'), 'perm' => 'tools', 'view' => 'projects'],
        ]
    ],
    'news'  => ['title' => __('news_mgr', 'admin'), 'perm' => 'publications'],
    'users' => ['title' => __('users_mgr', 'admin'), 'perm' => 'users'],

    'settings' => [
        'title' => __('settings', 'admin'),
        'perm'  => 'all',
        'children' => [
            'site-config' => ['title' => __('site_config', 'admin'), 'perm' => 'all'],
            'nav-menus'   => ['title' => __('nav_menus', 'admin'), 'perm' => 'all'],
        ]
    ],
    'plugins' => [
        'title' => __('plugins_mgr', 'admin'),
        'perm'  => 'all',
        'children' => [
            'plugins-list'   => ['title' => __('plugins_list', 'admin'), 'perm' => 'all', 'view' => 'plugins'],
            'plugins-install'=> ['title' => __('plugins_install', 'admin'), 'perm' => 'all'],
            'plugins-market' => ['title' => __('plugins_market', 'admin'), 'perm' => 'all'],
        ]
    ],
];

// 合并插件注册的菜单
$pluginPages = LabPress::getAdminPages();
foreach ($pluginPages as $slug => $page) {
    $menu[$slug] = ['title' => $page['title'], 'perm' => $page['permission']];
}

// 允许插件修改整个后台菜单
$menu = LabPress_Hooks::applyFilters('admin_menu', $menu);

// 视图白名单：只允许菜单声明过的视图，防止任意文件包含
$allowedViews = [];
foreach ($menu as $key => $item) {
    if (!empty($item['children']) && is_array($item['children'])) {
        foreach ($item['children'] as $childKey => $child) {
            $allowedViews[] = $child['view'] ?? $childKey;
        }
    } else {
        $allowedViews[] = $key;
    }
}
$allowedViews = array_values(array_unique(array_map('strval', $allowedViews)));
$viewIsAllowed = in_array($view, $allowedViews, true);

// 当前视图标题（顶栏面包屑 / 页面大标题）
$viewTitle = '';
foreach ($menu as $key => $item) {
    if (!empty($item['children']) && is_array($item['children'])) {
        foreach ($item['children'] as $childKey => $child) {
            if (($child['view'] ?? $childKey) === $view) {
                $viewTitle = $child['title'] ?? $childKey;
                break 2;
            }
        }
    } elseif ($key === $view) {
        $viewTitle = $item['title'] ?? $key;
        break;
    }
}
if ($viewTitle === '') {
    $viewTitle = $pluginPages[$view]['title'] ?? __('admin_panel', 'admin');
}
$currentUser = $_SESSION['user'] ?? '';

?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(lp_csrf_token()) ?>">
    <title><?= getFullTitle(__('admin_panel', 'admin')) ?></title>
    <link rel="preload" href="assets/fonts/roboto-mono/roboto-mono-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/css/fonts.css">
    <link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- EasyMDE 编辑器 -->
    <link rel="stylesheet" href="/admin/assets/css/easymde.min.css">
    <script src="/admin/assets/js/easymde.min.js"></script>
    <?php LabPress_Hooks::doAction('admin_before_head_end'); ?>
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="打开导航菜单">
                <i class="fas fa-bars"></i>
            </button>
            <div class="topbar-crumb">
                <span class="dot-live" aria-hidden="true"></span>
                <span>Console</span>
                <span class="sep">/</span>
                <span class="current"><?= e($viewTitle) ?></span>
            </div>
            <div class="topbar-meta">
                <span id="adminClock">--:--:--</span>
                <span class="sep hide-sm">/</span>
                <span class="hide-sm">v<?= e(LABPRESS_VERSION) ?></span>
                <span class="sep hide-sm">/</span>
                <span class="user-chip"><i class="fas fa-user"></i><?= e($currentUser) ?></span>
                <a class="topbar-link" href="/" target="_blank" rel="noopener">
                    <i class="fas fa-external-link-alt"></i> 站点
                </a>
            </div>
        </header>

        <?php if ($view !== 'dashboard'): ?>
        <section class="page-head">
            <div>
                <div class="eyebrow">LabPress Console</div>
                <h1><?= e($viewTitle) ?></h1>
            </div>
        </section>
        <?php endif; ?>

        <?php LabPress_Hooks::doAction('admin_before_page_content', $view); ?>
        <?php
        // 加载对应视图（白名单 + 插件页权限统一校验）
        $viewFile = 'views/' . $view . '.php';
        if ($viewIsAllowed && file_exists($viewFile)) {
            include $viewFile;
        } elseif ($viewIsAllowed && isset($pluginPages[$view])) {
            $pagePerm = $pluginPages[$view]['permission'] ?? 'all';
            if (!hasPermission($pagePerm)) {
                echo '<div class="card"><p>' . e(__('no_permission', 'admin')) . '</p></div>';
            } else {
                call_user_func($pluginPages[$view]['callback']);
            }
        } else {
            echo '<p class="p-4">' . e(__('page_not_found', 'admin')) . '</p>';
        }
        ?>
        <?php LabPress_Hooks::doAction('admin_after_page_content', $view); ?>

        <?php include 'includes/admin-footer.php'; ?>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <script src="assets/js/admin.js"></script>
</body>
</html>
