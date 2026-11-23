<?php
if (!hasPermission('users')) {
    echo '<div class="card"><p>' . e(__('no_permission', 'admin')) . '</p></div>';
    return;
}
?>
<div class="card">
    <div class="card-header">
        <h3><?= e(__('users_mgr', 'admin')) ?></h3>
        <button class="btn btn-primary" onclick="showUserForm()"><?= e(__('user_add', 'admin')) ?></button>
    </div>
    <div id="userList">
        <p><?= e(__('loading', 'admin')) ?></p>
    </div>
</div>

<div id="userModal" class="modal">
    <div class="modal-content">
        <h3 id="userModalTitle"><?= e(__('user_add', 'admin')) ?></h3>
        <form id="userForm">
            <input type="hidden" name="username_old" value="">
            <div class="form-group">
                <label><?= e(__('user_username', 'admin')) ?></label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label><?= e(__('password', 'admin')) ?></label>
                <input type="password" name="password" class="form-control" placeholder="<?= e(__('user_password_new', 'admin')) ?>">
            </div>
            <div class="form-group">
                <label><?= e(__('user_permissions', 'admin')) ?></label>
                <div>
                    <label><input type="checkbox" name="permissions[]" value="all"> <?= e(__('user_perm_all', 'admin')) ?></label>
                    <label><input type="checkbox" name="permissions[]" value="tools"> <?= e(__('user_perm_tools', 'admin')) ?></label>
                    <label><input type="checkbox" name="permissions[]" value="publications"> <?= e(__('user_perm_publications', 'admin')) ?></label>
                    <label><input type="checkbox" name="permissions[]" value="slides"> <?= e(__('user_perm_slides', 'admin')) ?></label>
                    <label><input type="checkbox" name="permissions[]" value="users"> <?= e(__('user_perm_users', 'admin')) ?></label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><?= e(__('save', 'admin')) ?></button>
            <button type="button" class="btn" onclick="closeUserModal()"><?= e(__('cancel', 'admin')) ?></button>
        </form>
    </div>
</div>

<!-- 修改密码卡片 -->
<div class="card mt-4">
    <div class="card-header">
        <h3><?= e(__('change_password', 'admin')) ?></h3>
    </div>
    <div class="card-body">
        <!-- 修改自己的密码 -->
        <?php if (isUsingDefaultPassword($_SESSION['user'])): ?>
            <div class="alert alert-warning"><?= e(__('default_password_warning', 'admin')) ?></div>
        <?php endif; ?>
        <h5><?= e(__('change_my_password', 'admin')) ?></h5>
        <form id="changeMyPasswordForm" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label><?= e(__('new_password', 'admin')) ?></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                    <label><?= e(__('confirm_password', 'admin')) ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group col-md-4" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><?= e(__('update', 'admin')) ?></button>
                </div>
            </div>
        </form>

        <!-- 管理员修改其他用户密码 -->
        <?php if (hasPermission('all')): ?>
        <hr>
        <h5><?= e(__('change_user_password', 'admin')) ?></h5>
        <form id="changeUserPasswordForm">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label><?= e(__('select_user', 'admin')) ?></label>
                    <select name="target_user" class="form-control" id="targetUserSelect" required>
                        <option value=""><?= e(__('choose_user', 'admin')) ?></option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label><?= e(__('new_password', 'admin')) ?></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group col-md-3">
                    <label><?= e(__('confirm_password', 'admin')) ?></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-group col-md-3" style="display:flex; align-items:flex-end;">
                    <button type="submit" class="btn btn-primary"><?= e(__('update', 'admin')) ?></button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- 注入翻译字符串和当前用户名 -->
<script>
window.__USER_LANG = {
    edit: '<?= e(__('edit', 'admin')) ?>',
    delete: '<?= e(__('delete', 'admin')) ?>',
    delete_confirm: '<?= e(__('user_delete_confirm', 'admin')) ?>',
    error: '<?= e(__('error', 'admin')) ?>',
    user_edit_title: '<?= e(__('user_edit', 'admin')) ?>',
    user_add_title: '<?= e(__('user_add', 'admin')) ?>',
    password_placeholder: '<?= e(__('user_password_new', 'admin')) ?>',
    username: '<?= e(__('user_username', 'admin')) ?>',
    permissions: '<?= e(__('user_permissions', 'admin')) ?>',
    password_mismatch: '<?= e(__('password_mismatch', 'admin')) ?>',
    password_changed: '<?= e(__('password_changed', 'admin')) ?>',
    select_user: '<?= e(__('select_user', 'admin')) ?>',
    loading: '<?= e(__('loading', 'admin')) ?>',
    no_data: '<?= e(__('no_data', 'admin')) ?>'
};
window.__CURRENT_USER = '<?= e($_SESSION['user']) ?>';
</script>
<script src="assets/js/users.js"></script>


