<?php
if (!hasPermission('all')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}

// 系统环境检查
$phpVersionOk = version_compare(PHP_VERSION, '7.4', '>=');
$zipOk = class_exists('ZipArchive');
$pluginsDir = __DIR__ . '/../../plugins/';
$pluginsWritable = is_writable($pluginsDir);
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');

// 获取所有已安装插件用于卸载列表
$allPlugins = $pdo->query("SELECT * FROM plugins ORDER BY name")->fetchAll();
?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3><?= __('plugin_install_title', 'admin') ?></h3>
            </div>
            <div class="card-body">
                <p><?= __('plugin_install_desc', 'admin') ?></p>
                <form id="install-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label><?= __('plugin_select_zip', 'admin') ?></label>
                        <input type="file" name="zip" class="form-control-file" accept=".zip" required>
                    </div>
                    <button type="submit" class="btn btn-primary" id="install-btn">
                        <i class="fas fa-upload"></i> <?= __('plugin_upload_btn', 'admin') ?>
                    </button>
                </form>
                
                <!-- 进度条区域 -->
                <div id="install-progress" class="mt-4" style="display:none;">
                    <div class="progress" style="height: 25px;">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" 
                             style="width: 0%">0%</div>
                    </div>
                    <p id="progress-status" class="mt-2 text-muted"></p>
                </div>
                
                <!-- 结果展示区域 -->
                <div id="install-result" class="mt-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?= __('plugin_install_info', 'admin') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3><?= __('plugin_sys_check', 'admin') ?></h3>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><?= __('plugin_php_version', 'admin') ?></td>
                        <td>
                            <span class="badge badge-<?= $phpVersionOk ? 'success' : 'danger' ?>">
                                <?= e(PHP_VERSION) ?> <?= $phpVersionOk ? '<i class="fas fa-check"></i>' : '<i class="fas fa-times"></i> ' . e(__('plugin_php_required', 'admin')) ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('plugin_zip_ext', 'admin') ?></td>
                        <td>
                            <span class="badge badge-<?= $zipOk ? 'success' : 'danger' ?>">
                                <?= $zipOk ? e(__('plugin_zip_installed', 'admin')) . ' <i class="fas fa-check"></i>' : e(__('plugin_zip_not_installed', 'admin')) . ' <i class="fas fa-times"></i>' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('plugin_dir_writable', 'admin') ?></td>
                        <td>
                            <span class="badge badge-<?= $pluginsWritable ? 'success' : 'danger' ?>">
                                <?= $pluginsWritable ? e(__('plugin_dir_writable_yes', 'admin')) . ' <i class="fas fa-check"></i>' : e(__('plugin_dir_writable_no', 'admin')) . ' <i class="fas fa-times"></i>' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><?= __('plugin_upload_max', 'admin') ?></td>
                        <td><?= $uploadMax ?></td>
                    </tr>
                    <tr>
                        <td><?= __('plugin_post_max', 'admin') ?></td>
                        <td><?= $postMax ?></td>
                    </tr>
                </table>
                <?php if (!$pluginsWritable): ?>
                    <div class="alert alert-warning mt-3">
                        <strong><?= __('plugin_dir_not_writable_title', 'admin') ?></strong> <?= __('plugin_dir_not_writable', 'admin') ?>
                    </div>
                <?php endif; ?>
                <?php if (!$zipOk): ?>
                    <div class="alert alert-danger mt-3">
                        <strong><?= __('plugin_zip_not_installed_title', 'admin') ?></strong> <?= __('plugin_zip_not_installed', 'admin') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 卸载插件卡片 -->
        <div class="card mt-3">
            <div class="card-header">
                <h3><?= __('plugin_uninstall_title', 'admin') ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($allPlugins)): ?>
                    <p><?= __('plugin_no_installed', 'admin') ?></p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?= __('plugin_name', 'admin') ?></th>
                                <th><?= __('plugin_status', 'admin') ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allPlugins as $p): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($p['name']) ?>
                                    <small class="text-muted d-block">(<?= htmlspecialchars($p['slug']) ?>)</small>
                                </td>
                                <td>
                                    <?php if ($p['status']): ?>
                                        <span class="badge badge-success"><?= __('plugin_activated', 'admin') ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= __('plugin_deactivated', 'admin') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$p['status']): ?>
                                        <button class="btn btn-sm btn-danger uninstall-plugin-btn" data-slug="<?= htmlspecialchars($p['slug']) ?>">
                                            <?= __('plugin_uninstall_btn', 'admin') ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <small class="text-muted"><?= __('plugin_uninstall_tip', 'admin') ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__INSTALL_LANG = {
    select_file: '<?= __('plugin_select_file', 'admin') ?>',
    install_btn: '<?= __('plugin_upload_btn', 'admin') ?>',
    installing: '<?= __('plugin_installing', 'admin') ?>',
    uploading: '<?= __('plugin_uploading', 'admin') ?>',
    installing_progress: '<?= __('plugin_installing_progress', 'admin') ?>',
    install_complete: '<?= __('plugin_install_complete', 'admin') ?>',
    success_title: '<?= __('plugin_install_success_title', 'admin') ?>',
    name_label: '<?= __('plugin_name_label', 'admin') ?>',
    desc_label: '<?= __('plugin_desc_label', 'admin') ?>',
    version_label: '<?= __('plugin_version_label', 'admin') ?>',
    author_label: '<?= __('plugin_author_label', 'admin') ?>',
    manage_btn: '<?= __('plugin_manage_btn', 'admin') ?>',
    network_error: '<?= __('network_error', 'admin') ?>',
    unknown: '<?= __('plugin_unknown', 'admin') ?>',
    none: '<?= __('plugin_none', 'admin') ?>',
    uninstall_confirm: '<?= __('plugin_uninstall_confirm_text', 'admin') ?>',
    uninstall_success: '<?= __('plugin_uninstall_success', 'admin') ?>',
    uninstall_fail: '<?= __('plugin_uninstall_fail', 'admin') ?>'
};
</script>
<script src="assets/js/plugins-install.js"></script>