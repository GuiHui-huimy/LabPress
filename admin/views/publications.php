<?php
if (!hasPermission('publications')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$publications = getPublications();
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('publications_mgr', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showPubForm()"><?= __('pub_add', 'admin') ?></button>
    </div>
    <?php if (empty($publications)): ?>
        <p><?= __('no_data', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('pub_title', 'admin') ?></th>
                    <th><?= __('pub_authors', 'admin') ?></th>
                    <th><?= __('pub_journal', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($publications as $pub): ?>
                <tr>
                    <td><?= htmlspecialchars($pub['title']) ?></td>
                    <td><?= htmlspecialchars($pub['authors']) ?></td>
                    <td><?= htmlspecialchars($pub['journal']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick='editPub(<?= json_encode($pub, JSON_UNESCAPED_UNICODE) ?>)'><?= __('edit', 'admin') ?></button>
                        <button class="btn btn-sm btn-danger" onclick="deletePub(<?= $pub['id'] ?? 'null' ?>)"><?= __('delete', 'admin') ?></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div id="pubModal" class="modal">
    <div class="modal-content">
        <h3 id="pubModalTitle"><?= __('pub_add', 'admin') ?></h3>
        <form id="pubForm">
            <input type="hidden" name="id" value="">
            <div class="form-group">
                <label><?= __('pub_title', 'admin') ?></label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('pub_authors', 'admin') ?></label>
                <input type="text" name="authors" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('pub_journal', 'admin') ?></label>
                <input type="text" name="journal" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('pub_link', 'admin') ?></label>
                <input type="url" name="link" class="form-control">
            </div>
            <div class="form-group">
                <label><?= __('pub_sort', 'admin') ?></label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closePubModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__PUB_LANG = {
    add_title: '<?= __('pub_add', 'admin') ?>',
    edit_title: '<?= __('pub_edit', 'admin') ?>',
    delete_confirm: '<?= __('pub_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>'
};
</script>
<script src="assets/js/publications.js"></script>

