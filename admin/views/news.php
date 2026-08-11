<?php
// 权限检查
if (!hasPermission('publications')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$newsList = getNews();
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('news_mgr', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showNewsForm()"><?= __('news_add', 'admin') ?></button>
    </div>
    <?php if (empty($newsList)): ?>
        <p><?= __('no_data', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('news_title', 'admin') ?></th>
                    <th><?= __('news_date', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($newsList as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= htmlspecialchars($item['date']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editNews(<?= $item['id'] ?>)"><?= __('edit', 'admin') ?></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteNews(<?= $item['id'] ?>)"><?= __('delete', 'admin') ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- 新闻编辑弹窗 -->
<div id="newsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
    <div style="background:white; max-width:700px; margin:5% auto; padding:2rem; border-radius:8px; max-height:80vh; overflow-y:auto;">
        <h3 id="newsModalTitle"><?= __('news_add', 'admin') ?></h3>
        <form id="newsForm">
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label><?= __('news_title', 'admin') ?></label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('news_summary', 'admin') ?></label>
                <textarea name="summary" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label><?= __('news_content', 'admin') ?></label>
                <textarea name="content" id="newsContent" class="form-control" rows="10"></textarea>
            </div>
            <div class="form-group">
                <label><?= __('news_image', 'admin') ?></label>
                <div class="input-group">
                    <input type="text" name="image" class="form-control" placeholder="<?= __('news_image_placeholder', 'admin') ?>" id="newsImage">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('newsImageUpload').click()">
                        <i class="fas fa-upload"></i> <?= __('upload', 'admin') ?>
                    </button>
                    <input type="file" id="newsImageUpload" accept="image/*" style="display:none" onchange="uploadNewsImage()">
                </div>
                <div id="newsImagePreview" style="margin-top:0.5rem;"></div>
            </div>
            <div class="form-group">
                <label><?= __('news_author', 'admin') ?></label>
                <input type="text" name="author" class="form-control" value="<?= __('news_author_default', 'admin') ?>">
            </div>
            <div class="form-group">
                <label><?= __('news_date', 'admin') ?></label>
                <input type="date" name="date" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closeNewsModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__NEWS_LANG = {
    add_title: '<?= __('news_add', 'admin') ?>',
    edit_title: '<?= __('news_edit', 'admin') ?>',
    delete_confirm: '<?= __('news_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_success: '<?= __('success', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>',
    upload_fail: '<?= __('slide_image_upload_fail', 'admin') ?>',
    editor_placeholder: '<?= __('editor_placeholder', 'admin') ?>',
    network_error: '<?= __('network_error', 'admin') ?>',
    upload_btn: '<?= __('upload', 'admin') ?>',
    author_default: '<?= __('news_author_default', 'admin') ?>'
};
</script>
<script src="assets/js/news-manage.js"></script>