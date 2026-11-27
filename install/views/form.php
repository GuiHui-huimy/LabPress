<?php
if (!defined('LABPRESS_INSTALLER')) {
    http_response_code(404);
    exit;
}
/** @var array $checks @var bool $passed @var string $csrf @var array $old @var array $errors */
$groups = [
    'db' => '数据库连接',
    'site' => '站点与管理员',
    'demo' => '演示内容',
];
?>
<div class="eyebrow">Initial setup / 01</div>
<h1>安装 LabPress</h1>
<p class="lead">填写数据库和站点信息后，安装器会创建表结构、写入配置并生成管理员账号。整个过程不需要导入 SQL 文件。</p>

<?php if ($errors !== []): ?>
<div class="alerts">
    <?php foreach ($errors as $error): ?>
        <div class="alert"><?= h((string)$error) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="checks">
    <?php foreach ($checks as $check): ?>
        <div class="check <?= !empty($check['ok']) ? 'ok' : 'bad' ?> <?= empty($check['required']) ? 'optional' : '' ?>">
            <span class="flag"><?= !empty($check['ok']) ? '✓' : '×' ?></span>
            <span class="name"><?= h((string)$check['label']) ?><?= empty($check['required']) ? ' <span class="tag">可选</span>' : '' ?></span>
            <span class="detail"><?= h((string)$check['detail']) ?></span>
        </div>
    <?php endforeach; ?>
</div>

<form id="installForm" method="post" action="install.php" autocomplete="off">
    <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

    <section class="group">
        <h2>数据库连接<small>建议先在面板创建一个空数据库；若数据库用户有 CREATE 权限，安装器也可以自动创建。</small></h2>
        <div class="form-row">
            <div class="field">
                <label for="db_host">数据库主机</label>
                <input id="db_host" type="text" name="db_host" value="<?= h($old['db_host']) ?>" required placeholder="127.0.0.1">
                <p class="hint">本地 socket 环境可填 localhost，或使用下方 Socket 路径。</p>
            </div>
            <div class="field narrow">
                <label for="db_port">端口</label>
                <input id="db_port" type="number" min="1" max="65535" name="db_port" value="<?= h($old['db_port']) ?>" required>
            </div>
        </div>
        <div class="field">
            <label for="db_socket">Unix Socket（可选）</label>
            <input id="db_socket" type="text" name="db_socket" value="<?= h($old['db_socket']) ?>" placeholder="/var/run/mysqld/mysqld.sock">
            <p class="hint">填写后将优先使用 socket，不再使用主机和端口。共享主机通常留空。</p>
        </div>
        <div class="form-row">
            <div class="field">
                <label for="db_name">数据库名</label>
                <input id="db_name" type="text" name="db_name" value="<?= h($old['db_name']) ?>" required pattern="[A-Za-z0-9_]{1,64}" placeholder="labpress">
            </div>
            <div class="field">
                <label for="db_user">数据库用户名</label>
                <input id="db_user" type="text" name="db_user" value="<?= h($old['db_user']) ?>" required placeholder="labpress_user">
            </div>
            <div class="field">
                <label for="db_pass">数据库密码</label>
                <input id="db_pass" type="password" name="db_pass" value="" autocomplete="new-password" placeholder="留空表示空密码">
            </div>
        </div>
    </section>

    <section class="group">
        <h2>站点与管理员<small>这些信息会写入 storage/config/site.php 和 site_config 表，可在后台继续修改。</small></h2>
        <div class="form-row">
            <div class="field">
                <label for="site_name">站点名称</label>
                <input id="site_name" type="text" name="site_name" value="<?= h($old['site_name']) ?>" required maxlength="100" placeholder="例如：HUI-MY.LAB">
            </div>
            <div class="field">
                <label for="site_url">站点 URL</label>
                <input id="site_url" type="url" name="site_url" value="<?= h($old['site_url']) ?>" required placeholder="https://example.com">
            </div>
            <div class="field narrow">
                <label for="language">站点语言</label>
                <select id="language" name="language">
                    <option value="zh_CN" <?= ($old['language'] ?? 'zh_CN') === 'zh_CN' ? 'selected' : '' ?>>简体中文</option>
                    <option value="en_US" <?= ($old['language'] ?? '') === 'en_US' ? 'selected' : '' ?>>English</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="field">
                <label for="admin_username">管理员用户名</label>
                <input id="admin_username" type="text" name="admin_username" value="<?= h($old['admin_username']) ?>" required pattern="[A-Za-z0-9_.@\-]{3,32}" placeholder="admin">
            </div>
            <div class="field">
                <label for="admin_password">管理员密码</label>
                <input id="admin_password" type="password" name="admin_password" value="" autocomplete="new-password" required minlength="8" placeholder="至少 8 个字符">
                <p class="hint">不会回显；请使用至少 8 位、包含字母和数字的密码。</p>
            </div>
        </div>
    </section>

    <section class="group">
        <h2>演示内容<small>安装器使用一套全新的“计算生物学实验室”演示数据，不沿用旧示例 SQL。</small></h2>
        <label class="check-line">
            <input type="checkbox" name="demo" value="1" <?= !empty($old['demo']) ? 'checked' : '' ?>>
            <span>安装演示数据：6 个项目、4 篇示例成果、3 条动态、3 个工具、导航菜单与首页介绍。安装后可在后台删除或替换。</span>
        </label>
    </section>

    <div class="actions">
        <button class="btn" type="submit" <?= $passed ? '' : 'disabled' ?>>开始安装</button>
        <span class="note"><?= $passed ? '环境检查已通过。' : '请先处理上方标红的必要检查项。' ?></span>
    </div>
</form>
<p class="hint" style="margin-top:26px">安装器不会删除已有表；请使用空数据库。安装成功后会生成 <b>storage/install.lock</b>，再次访问本页只显示已安装状态。</p>
<script>
(function () {
    var form = document.getElementById('installForm');
    if (!form) return;
    form.addEventListener('submit', function () {
        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = '正在安装...';
        }
    });
})();
</script>
