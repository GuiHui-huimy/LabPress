<?php
if (!hasPermission('all')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('plugin_market_title', 'admin') ?></h3>
    </div>
    <div class="card-body">
        <p><?= __('plugin_market_desc', 'admin') ?></p>
        <div id="market-container">
            <div class="text-center text-muted"><?= __('loading', 'admin') ?></div>
        </div>
    </div>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__MARKET_LANG = {
    loading: '<?= __('loading', 'admin') ?>',
    no_plugins: '<?= __('plugin_market_no_plugins', 'admin') ?>',
    install_btn: '<?= __('plugin_install_btn', 'admin') ?>',
    install_confirm: '<?= __('plugin_install_confirm', 'admin') ?>',
    installing: '<?= __('plugin_installing', 'admin') ?>',
    install_success: '<?= __('plugin_install_success', 'admin') ?>',
    install_fail: '<?= __('plugin_install_fail', 'admin') ?>',
    network_error: '<?= __('network_error', 'admin') ?>',
    market_unavailable: '<?= __('plugin_market_unavailable', 'admin') ?>',
    version_label: '<?= __('plugin_version_label', 'admin') ?>',
    author_label: '<?= __('plugin_author_label', 'admin') ?>'
};
</script>
<script src="assets/js/plugins-market.js"></script>