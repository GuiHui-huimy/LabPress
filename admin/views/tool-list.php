<?php
if (!hasPermission('tools')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$tools = getTools();
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('tool_list', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showToolForm()"><?= __('tool_add', 'admin') ?></button>
    </div>
    <?php if (empty($tools)): ?>
        <p><?= __('no_data', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('tool_name', 'admin') ?></th>
                    <th><?= __('tool_type', 'admin') ?></th>
                    <th><?= __('tool_desc', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tools as $tool): ?>
                <tr>
                    <td><?= htmlspecialchars($tool['name']) ?></td>
                    <td><?= htmlspecialchars($tool['type']) ?></td>
                    <td><?= htmlspecialchars(mb_substr($tool['description'], 0, 50)) ?>...</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick='editTool(<?= json_encode($tool, JSON_UNESCAPED_UNICODE) ?>)'><?= __('edit', 'admin') ?></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteTool('<?= htmlspecialchars($tool['name']) ?>')"><?= __('delete', 'admin') ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="toolModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
    <div style="background:white; max-width:600px; margin:5% auto; padding:2rem; border-radius:8px;">
        <h3 id="toolModalTitle"><?= __('tool_add', 'admin') ?></h3>
        <form id="toolForm">
            <input type="hidden" name="old_name" value="">
            <div class="form-group">
                <label><?= __('tool_name', 'admin') ?></label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('tool_type', 'admin') ?></label>
                <select name="type" class="form-control">
                    <option value="软件"><?= __('tool_type_software', 'admin') ?></option>
                    <option value="数据库"><?= __('tool_type_database', 'admin') ?></option>
                </select>
            </div>
            <div class="form-group">
                <label><?= __('tool_icon', 'admin') ?></label>
                <input type="text" name="icon" class="form-control" placeholder="fas fa-cut">
            </div>
            <div class="form-group">
                <label><?= __('tool_desc', 'admin') ?></label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label><?= __('tool_features', 'admin') ?></label>
                <input type="text" name="features" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_github', 'admin') ?></label>
                <input type="url" name="github" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_website', 'admin') ?></label>
                <input type="url" name="website" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_detail_link', 'admin') ?></label>
                <input type="text" name="detail_link" class="form-control" value="#">
            </div>
            <div class="form-group">
                <label><?= __('tool_sort', 'admin') ?></label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closeToolModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__TOOL_LIST_LANG = {
    add_title: '<?= __('tool_add', 'admin') ?>',
    edit_title: '<?= __('tool_edit', 'admin') ?>',
    delete_confirm: '<?= __('tool_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>'
};
</script>
<script src="assets/js/tool-list.js"></script>