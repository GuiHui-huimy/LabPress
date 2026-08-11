<?php
if (!hasPermission('tools')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$tools = getTools();
?>
<div class="card">
    <h3><?= __('tool_detail_title', 'admin') ?></h3>
    <div class="form-group">
        <label><?= __('tool_select', 'admin') ?></label>
        <select id="detailToolSelect" class="form-control" onchange="loadDetailForm(this.value)">
            <option value=""><?= __('tool_select', 'admin') ?></option>
            <?php foreach ($tools as $t): ?>
                <option value="<?= htmlspecialchars($t['name']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div id="detailFormContainer" style="display:none;">
        <form id="detailForm">
            <input type="hidden" name="tool_name" value="">
            <div class="form-group">
                <label><?= __('tool_short_desc', 'admin') ?></label>
                <input type="text" name="short_description" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_detailed_desc', 'admin') ?></label>
                <textarea name="detailed_description" class="form-control" rows="6"></textarea>
            </div>
            <div class="form-group">
                <label><?= __('tool_version', 'admin') ?></label>
                <input type="text" name="version" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_language', 'admin') ?></label>
                <input type="text" name="language" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_license', 'admin') ?></label>
                <input type="text" name="license" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_update_date', 'admin') ?></label>
                <input type="date" name="update_date" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_github', 'admin') ?></label>
                <input type="url" name="github" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_document', 'admin') ?></label>
                <input type="url" name="document" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_homepage', 'admin') ?></label>
                <input type="url" name="homepage" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_image', 'admin') ?></label>
                <input type="text" name="image" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_screenshot', 'admin') ?></label>
                <input type="text" name="screenshot" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_citation_title', 'admin') ?></label>
                <input type="text" name="citation_title" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('tool_citation_url', 'admin') ?></label>
                <input type="url" name="citation_url" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__TOOL_DETAIL_LANG = {
    error: '<?= __('error', 'admin') ?>',
    save_success: '<?= __('success', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>'
};
</script>
<script src="assets/js/tool-detail.js"></script>