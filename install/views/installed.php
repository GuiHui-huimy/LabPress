<?php
if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}
/** @var array $lock */
$lock = is_array($lock ?? null) ? $lock : [];
$url = rtrim((string)($lock['site_url'] ?? ''), '/');
?>
<div class="eyebrow">Already installed / 00</div>
<h1>LabPress 已安装</h1>
<p class="lead">检测到 <b>storage/install.lock</b>，安装器已停止运行。要继续升级或重置，请按文档使用迁移工具，或先备份并移除安装锁。</p>

<div class="summary">
    <div class="row"><span>安装时间</span><span><?= h((string)($lock['installed_at'] ?? '—')) ?></span></div>
    <div class="row"><span>版本</span><span><?= h((string)($lock['version'] ?? '—')) ?></span></div>
    <div class="row"><span>Schema</span><span><?= h((string)($lock['schema_version'] ?? '—')) ?></span></div>
</div>

<div class="actions">
    <?php if ($url !== ''): ?>
        <a class="btn" href="<?= h($url . '/admin/login.php') ?>">进入后台登录</a>
        <a class="btn btn-ghost" href="<?= h($url . '/') ?>" target="_blank" rel="noopener">打开站点首页</a>
    <?php endif; ?>
</div>

<section class="group">
    <h2>需要重新安装？<small>不要直接删除数据库表。</small></h2>
    <p class="note">先导出备份，再删除 storage/install.lock，并确认数据库为空或仅包含可重建的数据。生产环境不建议保留安装入口。</p>
</section>
