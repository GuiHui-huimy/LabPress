<?php
if (!hasPermission('tools')) { // 当前使用 tools 权限，以后可改为独立权限
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$categories = getCategories(); // 该函数已在 functions.php 中
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('project_categories', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showCatForm()"><?= __('cat_add', 'admin') ?></button>
    </div>
    <?php if (empty($categories)): ?>
        <p><?= __('no_data', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('cat_slug', 'admin') ?></th>
                    <th><?= __('cat_name', 'admin') ?></th>
                    <th><?= __('cat_desc', 'admin') ?></th>
                    <th><?= __('pub_sort', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                    <td><?= htmlspecialchars($cat['name']) ?></td>
                    <td><?= htmlspecialchars($cat['description']) ?></td>
                    <td><?= $cat['sort_order'] ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick='editCat(<?= json_encode($cat, JSON_UNESCAPED_UNICODE) ?>)'><?= __('edit', 'admin') ?></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteCat('<?= htmlspecialchars($cat['slug']) ?>')"><?= __('delete', 'admin') ?></button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- 新增/编辑弹窗 -->
<div id="catModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
    <div style="background:white; max-width:500px; margin:5% auto; padding:2rem; border-radius:8px;">
        <h3 id="catModalTitle"><?= __('cat_add', 'admin') ?></h3>
        <form id="catForm">
            <input type="hidden" name="old_slug" value="">
            <div class="form-group">
                <label><?= __('cat_slug', 'admin') ?></label>
                <input type="text" name="slug" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('cat_name', 'admin') ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('cat_desc', 'admin') ?></label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label><?= __('pub_sort', 'admin') ?></label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closeCatModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__CAT_LANG = {
    add_title: '<?= __('cat_add', 'admin') ?>',
    edit_title: '<?= __('cat_edit', 'admin') ?>',
    delete_confirm: '<?= __('cat_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>'
};
</script>
<script src="assets/js/project-categories.js"></script>