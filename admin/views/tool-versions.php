<?php
if (!hasPermission('tools')) { echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>'; return; }
$tools = getTools();
?>
<div class="card">
    <h3><?= __('tool_versions', 'admin') ?></h3>
    <div class="form-group">
        <label><?= __('version_select', 'admin') ?></label>
        <select id="versionToolSelect" class="form-control" onchange="loadVersions(this.value)">
            <option value=""><?= __('version_select', 'admin') ?></option>
            <?php foreach ($tools as $t): ?>
                <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="versionsContainer"></div>
</div>

<!-- 新增/编辑版本模态窗口 -->
<div id="versionModal" class="modal">
    <div class="modal-content wide">
        <h3 id="versionModalTitle"><?= __('version_add', 'admin') ?></h3>
        <form id="versionForm">
            <input type="hidden" name="id" value="">
            <input type="hidden" name="tool_name" id="versionToolName" value="">
            <div class="form-group">
                <label><?= __('version_number', 'admin') ?></label>
                <input type="text" name="version" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('version_date', 'admin') ?></label>
                <input type="date" name="date" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('version_changes', 'admin') ?></label>
                <div id="changesContainer"></div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addChangeField()"><?= __('version_add_change', 'admin') ?></button>
            </div>
            <div class="form-group">
                <label><?= __('version_downloads', 'admin') ?></label>
                <div id="downloadsContainer"></div>
                <button type="button" class="btn btn-sm btn-primary" onclick="addDownloadField()"><?= __('version_add_download', 'admin') ?></button>
            </div>
            <div style="margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
                <button type="button" class="btn" onclick="closeVersionModal()"><?= __('cancel', 'admin') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__VERSION_LANG = {
    version_list: '<?= __('version_list', 'admin') ?>',
    version_number: '<?= __('version_number', 'admin') ?>',
    version_date: '<?= __('version_date', 'admin') ?>',
    version_changes: '<?= __('version_changes', 'admin') ?>',
    version_downloads: '<?= __('version_downloads', 'admin') ?>',
    version_add: '<?= __('version_add', 'admin') ?>',
    version_edit: '<?= __('version_edit', 'admin') ?>',
    no_version_record: '<?= __('no_version_record', 'admin') ?>',
    edit: '<?= __('edit', 'admin') ?>',
    delete: '<?= __('delete', 'admin') ?>',
    delete_confirm: '<?= __('version_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>',
    count_unit: '<?= __('count_unit', 'admin') ?>',
    none: '<?= __('none', 'admin') ?>',
    change_placeholder: '<?= __('version_change_placeholder', 'admin') ?>',
    download_name: '<?= __('version_download_name', 'admin') ?>',
    download_url: '<?= __('version_download_url', 'admin') ?>',
    download_icon: '<?= __('version_download_icon', 'admin') ?>'
};
</script>
<script src="assets/js/tool-versions.js"></script>

