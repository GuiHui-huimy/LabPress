<?php
if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}
/** @var string $admin @var bool $demo @var string $db @var string $url @var string $schemaVersion */
?>
<div class="eyebrow">Installation complete / 02</div>
<h1>LabPress 已安装</h1>
<p class="lead">数据库表、站点配置和管理员账号已经创建。现在可以进入后台，继续配置导航、首页内容和插件。</p>

<div class="summary">
    <div class="row"><span>管理员</span><span><?= h($admin) ?></span></div>
    <div class="row"><span>数据库</span><span><?= h($db) ?></span></div>
    <div class="row"><span>Schema</span><span><?= h($schemaVersion) ?></span></div>
    <div class="row"><span>演示数据</span><span><span class="tag <?= $demo ? 'ok' : '' ?>"><?= $demo ? '已安装' : '未安装' ?></span></span></div>
    <div class="row"><span>站点</span><span><?= h($url) ?></span></div>
</div>

<section class="group">
    <h2>下一步<small>建议按顺序完成这三件事。</small></h2>
    <p class="note">1. 登录后台，修改站点名称、导航和首页介绍。<br>
    2. 在「插件管理」中启用需要的插件，例如页脚信息或多语言。<br>
    3. 删除或限制 <b>install.php</b> 的访问，避免安装入口长期暴露。</p>
    <div class="mono-box">location = /install.php { return 404; }</div>
</section>

<section class="group">
    <h2>安全提醒<small>安装锁已经写入 storage/install.lock。</small></h2>
    <p class="note">即使保留 install.php，检测到安装锁后也不会再次执行安装。但生产环境仍建议在 Nginx / Apache 中关闭该入口，或直接删除文件。</p>
</section>

<div class="actions">
    <a class="btn" href="<?= h(rtrim($url, '/') . '/admin/login.php') ?>">进入后台登录</a>
    <a class="btn btn-ghost" href="<?= h(rtrim($url, '/') . '/') ?>" target="_blank" rel="noopener">打开站点首页</a>
</div>
