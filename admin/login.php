<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
if (isset($_SESSION['user'])) { header('Location: index.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = $user['username'];
        $_SESSION['permissions'] = json_decode($user['permissions'], true);
        $redirectUrl = LabPress_Hooks::applyFilters('login_redirect_url', 'index.php');
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        $error = __('login_error', 'admin');
    }
}
?>
<!DOCTYPE html>
<html <?= lp_html_lang_attr() ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e(__('login_title', 'admin')) ?> — LabPress</title>
<meta name="description" content="LabPress 管理后台登录">
<meta name="robots" content="noindex, nofollow">
<link rel="preload" href="assets/fonts/roboto-mono/roboto-mono-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/fonts.css">
<link rel="stylesheet" href="assets/css/login.css">
<?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>

<body data-auth-error="<?= $error ? '1' : '0' ?>">

<div class="topbar">
    <div class="topbar-inner">
        <div class="brand">LAB<span>·</span>PRESS · SECURE ACCESS</div>
        <div class="meta-line">
            <span class="dot-live" aria-hidden="true"></span>
            <span>系统运行正常</span>
            <span class="sep">/</span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
</div>

<main class="auth">

    <section class="auth-visual">
        <div class="visual-top">
            <div class="eyebrow">Authentication · 身份验证</div>
            <h2>进入<span class="hl">实验室</span><br>后台。</h2>
            <p class="visual-sub">
                在这里维护论文、项目、工具与科研动态，让站点内容与实验室的最新进展保持一致。
            </p>

            <ul class="visual-list">
                <li><strong>统一入口。</strong>内容、成员与站点配置集中在一个控制台。</li>
                <li><strong>权限分级。</strong>按角色开放论文、工具、用户与插件管理。</li>
                <li><strong>操作留痕。</strong>发布、上传与插件变更均可追溯。</li>
            </ul>
        </div>

        <div class="visual-foot">
            <span><span class="dot-live" aria-hidden="true"></span> System operational</span>
            <span>LAB-PRESS / node-01</span>
        </div>
    </section>

    <section class="auth-panel">
        <div class="form-wrap" id="formWrap">

            <div class="eyebrow">Sign in · 登录</div>
            <h1 class="form-title">欢迎回来<span class="accent">.</span></h1>
            <p class="form-sub">
                请输入管理员账号，继续进入你的工作台。
            </p>

            <?php LabPress_Hooks::doAction('login_before_form'); ?>

            <form id="loginForm" method="post" action="" novalidate>

                <div class="field">
                    <label for="username">用户名 <span class="req">*</span></label>
                    <div class="input-shell">
                        <input type="text" id="username" name="username"
                               placeholder="your-username"
                               autocomplete="username" spellcheck="false"
                               aria-describedby="usernameError" required>
                    </div>
                    <p class="field-error" id="usernameError" role="alert"></p>
                </div>

                <div class="field<?= $error ? ' has-error' : '' ?>" data-server-error="<?= e($error) ?>">
                    <label for="password">密码 <span class="req">*</span></label>
                    <div class="input-shell has-toggle">
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               aria-describedby="passwordError" required>
                        <button type="button" class="toggle-pw" id="togglePw"
                                aria-label="显示密码" aria-pressed="false">SHOW</button>
                    </div>
                    <p class="caps-note" id="capsNote" hidden>Caps Lock 已开启</p>
                    <p class="field-error" id="passwordError" role="alert"><?= e($error) ?></p>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-label"><?= e(__('login_btn', 'admin')) ?></span>
                </button>
            </form>

            <?php LabPress_Hooks::doAction('login_after_form'); ?>

            <div class="panel-foot">
                <span>© <?= date('Y') ?> HUI-MY.LAB</span>
                <span>v<?= e(LABPRESS_VERSION) ?></span>
            </div>

        </div>
    </section>

</main>

<script src="assets/js/login.js"></script>
</body>
</html>
