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
<meta name="description" content="<?= e(__('login_meta_description', 'admin')) ?>">
<meta name="robots" content="noindex, nofollow">
<link rel="preload" href="assets/fonts/roboto-mono/roboto-mono-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/fonts.css">
<link rel="stylesheet" href="../assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/login.css">
<?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>

<body data-auth-error="<?= $error ? '1' : '0' ?>">

<div class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="../">LAB<span>·</span>PRESS · <?= e(__('secure_access', 'admin')) ?></a>
        <div class="meta-line">
            <span class="dot-live" aria-hidden="true"></span>
            <span><?= e(__('system_operational', 'admin')) ?></span>
            <span class="sep">/</span>
            <span id="clock">--:--:--</span>
        </div>
    </div>
</div>

<main class="auth">

    <section class="auth-visual">
        <div class="visual-top">
            <div class="eyebrow"><?= e(__('auth_label', 'admin')) ?></div>
            <h2><?= __('login_heading', 'admin') ?></h2>
            <p class="visual-sub">
                <?= e(__('login_intro', 'admin')) ?>
            </p>

            <ul class="visual-list">
                <li><strong><?= e(__('login_note_1_title', 'admin')) ?></strong><?= e(__('login_note_1_text', 'admin')) ?></li>
                <li><strong><?= e(__('login_note_2_title', 'admin')) ?></strong><?= e(__('login_note_2_text', 'admin')) ?></li>
                <li><strong><?= e(__('login_note_3_title', 'admin')) ?></strong><?= e(__('login_note_3_text', 'admin')) ?></li>
            </ul>
        </div>

        <div class="visual-foot">
            <span><span class="dot-live" aria-hidden="true"></span> <?= e(__('system_operational', 'admin')) ?></span>
            <span>LAB-PRESS / <?= e(__('node_01', 'admin')) ?></span>
        </div>
    </section>

    <section class="auth-panel">
        <div class="form-wrap" id="formWrap">

            <div class="eyebrow"><?= e(__('sign_in_label', 'admin')) ?></div>
            <h1 class="form-title"><?= e(__('login_welcome', 'admin')) ?><span class="accent">.</span></h1>
            <p class="form-sub">
                <?= e(__('login_sub', 'admin')) ?>
            </p>

            <?php LabPress_Hooks::doAction('login_before_form'); ?>

            <form id="loginForm" method="post" action="" novalidate>

                <div class="field">
                    <label for="username"><?= e(__('username', 'admin')) ?> <span class="req">*</span></label>
                    <div class="input-shell">
                        <input type="text" id="username" name="username"
                               placeholder="<?= e(__('username_placeholder', 'admin')) ?>"
                               autocomplete="username" spellcheck="false"
                               aria-describedby="usernameError" required>
                    </div>
                    <p class="field-error" id="usernameError" role="alert"></p>
                </div>

                <div class="field<?= $error ? ' has-error' : '' ?>" data-server-error="<?= e($error) ?>">
                    <label for="password"><?= e(__('password', 'admin')) ?> <span class="req">*</span></label>
                    <div class="input-shell has-toggle">
                        <input type="password" id="password" name="password"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               aria-describedby="passwordError" required>
                        <button type="button" class="toggle-pw" id="togglePw"
                                aria-label="<?= e(__('show_password', 'admin')) ?>" aria-pressed="false"><?= e(__('show_password_short', 'admin')) ?></button>
                    </div>
                    <p class="caps-note" id="capsNote" hidden><?= e(__('caps_lock_on', 'admin')) ?></p>
                    <p class="field-error" id="passwordError" role="alert"><?= e($error) ?></p>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-label"><?= e(__('login_btn', 'admin')) ?></span>
                </button>
            </form>

            <a class="back-home" href="../">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span><?= e(__('back_home', 'admin')) ?></span>
            </a>

            <?php LabPress_Hooks::doAction('login_after_form'); ?>

            <div class="panel-foot">
                <span>© <?= date('Y') ?> HUI-MY.LAB</span>
                <span>v<?= e(LABPRESS_VERSION) ?></span>
            </div>

        </div>
    </section>

</main>

<script>
window.LP_LOGIN_LANG = <?= json_encode([
    'usernameRequired' => __('username_required', 'admin'),
    'passwordRequired' => __('password_required', 'admin'),
    'showShort' => __('show_password_short', 'admin'),
    'hideShort' => __('hide_password_short', 'admin'),
    'showFull' => __('show_password', 'admin'),
    'hideFull' => __('hide_password', 'admin'),
    'verifying' => __('verifying', 'admin'),
    'loginError' => __('login_error', 'admin'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="assets/js/login.js"></script>
</body>
</html>
