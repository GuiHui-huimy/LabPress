<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
define('IS_ADMIN', true);
// 登录验证
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$view = $_GET['view'] ?? 'dashboard';

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

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getFullTitle(__('admin_panel', 'admin')) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <!-- EasyMDE 编辑器 -->
    <link rel="stylesheet" href="/admin/assets/css/easymde.min.css">
    <script src="/admin/assets/js/easymde.min.js"></script>
    <?php LabPress_Hooks::doAction('admin_before_head_end'); ?>
</head>
<body>
    <?php include 'includes/admin-header.php'; ?>

    <div class="admin-main">
        <?php LabPress_Hooks::doAction('admin_before_page_content', $view); ?>
        <?php
        // 加载对应视图
        $viewFile = 'views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } elseif (isset($pluginPages[$view])) {
            // 插件注册的页面，执行回调
            call_user_func($pluginPages[$view]['callback']);
        } else {
            echo '<p class="p-4">' . __('page_not_found', 'admin') . '</p>';
        }
        ?>
        <?php LabPress_Hooks::doAction('admin_after_page_content', $view); ?>
    </div>

    <?php include 'includes/admin-footer.php'; ?>
    <script src="assets/js/admin.js"></script>
</body>
</html>