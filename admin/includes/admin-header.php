<?php
// 变量 $menu 和 $view 已在 index.php 中定义
if (!isset($menu)) $menu = [];
if (!isset($view)) $view = 'dashboard';

// 读取站点名称
$siteName = LabPress::config('site_name', 'HUI-MY.LAB');
?>
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <a href="/"><span>LAB-Press</span><br><?= htmlspecialchars($siteName) ?></a>
        <small><?= __('admin_panel', 'admin') ?></small>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($menu as $key => $item): ?>
            <?php
            // 权限检查
            $requiredPerm = $item['perm'] ?? null;
            if ($requiredPerm !== null && !hasPermission($requiredPerm)) continue;

            // 判断是否有子菜单
            $hasChildren = isset($item['children']) && is_array($item['children']);
            $isParentActive = false;
            if ($hasChildren) {
                // 如果当前视图属于某个子菜单，则父菜单高亮
                foreach ($item['children'] as $childKey => $child) {
                    $childView = $child['view'] ?? $childKey;
                    if ($view === $childView) {
                        $isParentActive = true;
                        break;
                    }
                }
            }
            ?>
            <?php if ($hasChildren): ?>
                <!-- 父级菜单项（可折叠） -->
                <div class="nav-item nav-parent <?= $isParentActive ? 'active' : '' ?>"
                     data-toggle="submenu"
                     data-target="submenu-<?= $key ?>">
                    <?= $item['title'] ?>
                    <i class="fas fa-chevron-down submenu-arrow"></i>
                </div>
                <div class="submenu" id="submenu-<?= $key ?>" <?= $isParentActive ? 'style="display:block;"' : '' ?>>
                    <?php foreach ($item['children'] as $childKey => $child): ?>
                        <?php
                        $childPerm = $child['perm'] ?? null;
                        if ($childPerm !== null && !hasPermission($childPerm)) continue;
                        $childView = $child['view'] ?? $childKey;
                        $activeClass = ($view === $childView) ? ' active' : '';
                        ?>
                        <a href="?view=<?= $childView ?>" class="nav-item sub-item<?= $activeClass ?>">
                            <?= $child['title'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- 普通一级菜单项 -->
                <a href="?view=<?= $key ?>" class="nav-item<?= ($view === $key) ? ' active' : '' ?>">
                    <?= $item['title'] ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <a href="logout.php" class="nav-item logout-item"><?= __('logout', 'admin') ?></a>
    </nav>
</aside>