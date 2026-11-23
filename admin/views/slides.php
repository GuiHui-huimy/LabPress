<?php
if (!hasPermission('slides')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$slides = getSlides();
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('slides_mgr', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showSlideForm()"><?= __('slide_add', 'admin') ?></button>
    </div>
    <?php if (empty($slides)): ?>
        <p><?= __('no_data', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('slide_preview', 'admin') ?></th>
                    <th><?= __('slide_title', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $slide): ?>
                <tr>
                    <td><img src="/<?= htmlspecialchars($slide['image']) ?>" style="max-width:100px;"></td>
                    <td><?= htmlspecialchars($slide['title']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick='editSlide(<?= json_encode($slide, JSON_UNESCAPED_UNICODE) ?>)'><?= __('edit', 'admin') ?></button>
                        <button class="btn btn-sm btn-danger" onclick="deleteSlide(<?= $slide['id'] ?? 'null' ?>)"><?= __('delete', 'admin') ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="slideModal" class="modal">
    <div class="modal-content">
        <h3 id="slideModalTitle"><?= __('slide_add', 'admin') ?></h3>
        <form id="slideForm">
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label><?= __('slide_title', 'admin') ?></label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('slide_desc', 'admin') ?></label>
                <textarea name="description" class="form-control" required></textarea>
            </div>
            <div class="form-group">
                <label><?= __('slide_image', 'admin') ?></label>
                <input type="file" id="slideImageInput" accept="image/*">
                <input type="hidden" name="image" value="">
                <div id="imagePreview" style="margin-top:0.5rem;"></div>
            </div>
            <div class="form-group">
                <label><?= __('slide_sort', 'admin') ?></label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closeSlideModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__SLIDES_LANG = {
    add_title: '<?= __('slide_add', 'admin') ?>',
    edit_title: '<?= __('slide_edit', 'admin') ?>',
    delete_confirm: '<?= __('slide_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>',
    upload_fail: '<?= __('slide_image_upload_fail', 'admin') ?>'
};
</script>
<script src="assets/js/slides.js"></script>

