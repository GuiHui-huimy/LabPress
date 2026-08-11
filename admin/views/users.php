<?php
if (!hasPermission('users')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('users_mgr', 'admin') ?></h3>
        <button class="btn btn-primary" onclick="showUserForm()"><?= __('user_add', 'admin') ?></button>
    </div>
    <div id="userList">
        <p><?= __('loading', 'admin') ?></p>
    </div>
</div>

<div id="userModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999;">
    <div style="background:white; max-width:500px; margin:5% auto; padding:2rem; border-radius:8px;">
        <h3 id="userModalTitle"><?= __('user_add', 'admin') ?></h3>
        <form id="userForm">
            <input type="hidden" name="username_old" value="">
            <div class="form-group">
                <label><?= __('user_username', 'admin') ?></label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= __('password', 'admin') ?></label>
                <input type="password" name="password" class="form-control" placeholder="<?= __('user_password_new', 'admin') ?>">
            </div>
            <div class="form-group">
                <label><?= __('user_permissions', 'admin') ?></label>
                <div>
                    <label><input type="checkbox" name="permissions[]" value="all"> <?= __('user_perm_all', 'admin') ?></label>
                    <label><input type="checkbox" name="permissions[]" value="tools"> <?= __('user_perm_tools', 'admin') ?></label>
                    <label><input type="checkbox" name="permissions[]" value="publications"> <?= __('user_perm_publications', 'admin') ?></label>
                    <label><input type="checkbox" name="permissions[]" value="slides"> <?= __('user_perm_slides', 'admin') ?></label>
                    <label><input type="checkbox" name="permissions[]" value="users"> <?= __('user_perm_users', 'admin') ?></label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= __('save', 'admin') ?></button>
            <button type="button" class="btn" onclick="closeUserModal()"><?= __('cancel', 'admin') ?></button>
        </form>
    </div>
</div>

<!-- 修改密码卡片 -->
<div class="card mt-4">
    <div class="card-header">
        <h3>🔒 <?= __('change_password') ?></h3>
    </div>
    <div class="card-body">
        <!-- 修改自己的密码 --> 
         <?php if (isUsingDefaultPassword($_SESSION['user'])): ?>
            <div class="alert alert-warning">⚠️ <?= __('default_password_warning') ?></div>
        <?php endif; ?>
        <h5><?= __('change_my_password') ?></h5>
        <form id="changeMyPasswordForm" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label><?= __('new_password') ?></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label><?= __('confirm_password') ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><?= __('update') ?></button>
                </div>
            </div>
        </form>

        <!-- 管理员修改其他用户密码 -->
        <?php if (hasPermission('all')): ?>
        <hr>
        <h5><?= __('change_user_password') ?></h5>
        <form id="changeUserPasswordForm">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label><?= __('select_user') ?></label>
                    <select name="target_user" class="form-control" id="targetUserSelect" required>
                        <option value=""><?= __('choose_user') ?></option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label><?= __('new_password') ?></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group col-md-3">
                    <label><?= __('confirm_password') ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group col-md-3" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><?= __('update') ?></button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- 注入翻译字符串和当前用户名 -->
<script>
window.__USER_LANG = {
    edit: '<?= __('edit', 'admin') ?>',
    delete: '<?= __('delete', 'admin') ?>',
    delete_confirm: '<?= __('user_delete_confirm', 'admin') ?>',
    error: '<?= __('error', 'admin') ?>',
    user_edit_title: '<?= __('user_edit', 'admin') ?>',
    user_add_title: '<?= __('user_add', 'admin') ?>',
    password_placeholder: '<?= __('user_password_new', 'admin') ?>',
    username: '<?= __('user_username', 'admin') ?>',
    permissions: '<?= __('user_permissions', 'admin') ?>',
    password_mismatch: '<?= __('password_mismatch') ?>',
    password_changed: '<?= __('password_changed') ?>',
    select_user: '<?= __('select_user') ?>',
    loading: '<?= __('loading', 'admin') ?>',
    no_data: '<?= __('no_data', 'admin') ?>'
};
window.__CURRENT_USER = '<?= $_SESSION['user'] ?>';
</script>
<script src="assets/js/users.js"></script>