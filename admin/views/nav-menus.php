<?php
if (!hasPermission('all')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}

// 获取头部菜单
$headerMenus = $pdo->query("SELECT * FROM nav_menu WHERE location = 'header' ORDER BY sort_order ASC")->fetchAll();
// 获取页脚菜单
$footerMenus = $pdo->query("SELECT * FROM nav_menu WHERE location = 'footer' ORDER BY sort_order ASC")->fetchAll();
?>

<!-- 头部导航菜单管理 -->
<div class="card">
    <div class="card-header">
        <h3><?= __('nav_header', 'admin') ?></h3>
        <button class="btn btn-primary add-menu-btn" data-location="header"><?= __('nav_add_header', 'admin') ?></button>
    </div>
    <table>
        <thead>
            <tr>
                <th><?= __('nav_title', 'admin') ?></th>
                <th><?= __('nav_url', 'admin') ?></th>
                <th><?= __('nav_target', 'admin') ?></th>
                <th><?= __('nav_sort', 'admin') ?></th>
                <th><?= __('nav_active', 'admin') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($headerMenus as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['title']) ?></td>
                <td><?= htmlspecialchars($m['url']) ?></td>
                <td><?= $m['target'] ?></td>
                <td><?= $m['sort_order'] ?></td>
                <td><?= $m['is_active'] ? __('yes', 'admin') : __('no', 'admin') ?></td>
                <td>
                    <button class="btn btn-sm btn-primary edit-menu-btn" data-menu='<?= json_encode($m, JSON_UNESCAPED_UNICODE) ?>'><?= __('edit', 'admin') ?></button>
                    <button class="btn btn-sm btn-danger delete-menu-btn" data-id="<?= $m['id'] ?>"><?= __('delete', 'admin') ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 页脚链接管理 -->
<div class="card mt-4">
    <div class="card-header">
        <h3><?= __('nav_footer', 'admin') ?></h3>
        <button class="btn btn-primary add-menu-btn" data-location="footer"><?= __('nav_add_footer', 'admin') ?></button>
    </div>
    <table>
        <thead>
            <tr>
                <th><?= __('nav_title', 'admin') ?></th>
                <th><?= __('nav_url', 'admin') ?></th>
                <th><?= __('nav_target', 'admin') ?></th>
                <th><?= __('nav_sort', 'admin') ?></th>
                <th><?= __('nav_active', 'admin') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($footerMenus as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['title']) ?></td>
                <td><?= htmlspecialchars($m['url']) ?></td>
                <td><?= $m['target'] ?></td>
                <td><?= $m['sort_order'] ?></td>
                <td><?= $m['is_active'] ? __('yes', 'admin') : __('no', 'admin') ?></td>
                <td>
                    <button class="btn btn-sm btn-primary edit-menu-btn" data-menu='<?= json_encode($m, JSON_UNESCAPED_UNICODE) ?>'><?= __('edit', 'admin') ?></button>
                    <button class="btn btn-sm btn-danger delete-menu-btn" data-id="<?= $m['id'] ?>"><?= __('delete', 'admin') ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 弹窗（共用） -->
<div id="menuModal" class="modal">
    <div class="modal-content">
        <h3 id="menuModalTitle"><?= __('nav_add_header', 'admin') ?></h3>
        <form id="menuForm">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="location" value="header" id="menuLocation">
            <div class="form-group">
                <label><?= __('nav_title', 'admin') ?></label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('nav_url', 'admin') ?></label>
                <input type="text" name="url" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('nav_target', 'admin') ?></label>
                <select name="target" class="form-control">
                    <option value="_self"><?= __('nav_self', 'admin') ?></option>
                    <option value="_blank"><?= __('nav_blank', 'admin') ?></option>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('nav_sort', 'admin') ?></label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label><?= __('nav_active', 'admin') ?></label>
                <select name="is_active" class="form-control">
                    <option value="1"><?= __('yes', 'admin') ?></option>
                    <option value="0"><?= __('no', 'admin') ?></option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="document.getElementById('menuModal').style.display='none'"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__NAV_LANG = {
    add_header: '<?= __('nav_add_header', 'admin') ?>',
    add_footer: '<?= __('nav_add_footer', 'admin') ?>',
    edit_title: '<?= __('edit', 'admin') ?>',
    delete_confirm: '<?= __('confirm_delete', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_success: '<?= __('success', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>',
    network_error: '<?= __('network_error', 'admin') ?>'
};
</script>
<script src="assets/js/nav-menus.js"></script>

