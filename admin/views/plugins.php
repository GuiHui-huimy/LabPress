<?php
if (!hasPermission('all')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
$plugins = $pdo->query("SELECT * FROM plugins ORDER BY name")->fetchAll();
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('plugins_list', 'admin') ?></h3>
        <button class="btn btn-secondary" id="sync-plugins-btn"><?= __('plugin_sync_btn', 'admin') ?></button>
    </div>
    <?php if (empty($plugins)): ?>
        <p><?= __('plugin_no_plugins', 'admin') ?></p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th><?= __('plugin_name', 'admin') ?></th>
                    <th><?= __('plugin_description', 'admin') ?></th>
                    <th><?= __('plugin_version', 'admin') ?></th>
                    <th><?= __('plugin_status', 'admin') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $p): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($p['name']) ?></strong><br><small><?= htmlspecialchars($p['slug']) ?></small></td>
                    <td><?= htmlspecialchars($p['description']) ?></td>
                    <td><?= htmlspecialchars($p['version']) ?></td>
                    <td><?= $p['status'] ? __('plugin_activated', 'admin') : __('plugin_deactivated', 'admin') ?></td>
                    <td>
                        <button class="btn btn-sm btn-<?= $p['status'] ? 'warning' : 'success' ?> toggle-plugin" 
                                data-slug="<?= htmlspecialchars($p['slug']) ?>" 
                                data-action="<?= $p['status'] ? 'deactivate' : 'activate' ?>">
                            <?= $p['status'] ? __('plugin_deactivate_btn', 'admin') : __('plugin_activate_btn', 'admin') ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__PLUGINS_LANG = {
    sync_btn: '<?= __('plugin_sync_btn', 'admin') ?>',
    sync_scanning: '<?= __('plugin_sync_scanning', 'admin') ?>',
    sync_fail: '<?= __('plugin_sync_fail', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    network_error: '<?= __('network_error', 'admin') ?>',
    activate_success: '<?= __('plugin_activate_success', 'admin') ?>',
    deactivate_success: '<?= __('plugin_deactivate_success', 'admin') ?>',
    activate_fail: '<?= __('plugin_activate_fail', 'admin') ?>',
    deactivate_fail: '<?= __('plugin_deactivate_fail', 'admin') ?>'
};
</script>
<script src="assets/js/plugins-manage.js"></script>