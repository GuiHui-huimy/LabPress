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
<html>
<head>
<title><?= __('login_title', 'admin') ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #333; }
    .login-box { background: white; padding: 3rem; border-radius: 16px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
    .login-box input { width: 100%; padding: 0.8rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 6px; }
    .login-box button { width: 100%; background: #333; color: white; border: none; padding: 0.8rem; border-radius: 30px; cursor: pointer; }
    .error { color: red; }
</style>
<?php LabPress_Hooks::doAction('before_head_end'); ?>
</head>
<body>
    <div class="login-box">
        <h2><?= __('login_title', 'admin') ?></h2>
        <?php if ($error): ?>
            <p class="error"><?= LabPress_Hooks::applyFilters('login_error_message', $error) ?></p>
        <?php endif; ?>
        <?php LabPress_Hooks::doAction('login_before_form'); ?>
        <form method="post">
            <input type="text" name="username" placeholder="<?= __('username', 'admin') ?>" required>
            <input type="password" name="password" placeholder="<?= __('password', 'admin') ?>" required>
            <button type="submit"><?= __('login_btn', 'admin') ?></button>
        </form>
        <?php LabPress_Hooks::doAction('login_after_form'); ?>
    </div>
</body>
</html>