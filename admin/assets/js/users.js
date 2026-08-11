// 加载用户列表
async function loadUsers() {
    const listEl = document.getElementById('userList');
    const L = window.__USER_LANG;
    try {
        const res = await fetch('../api/users.php');
        const data = await res.json();
        if (!data.success || !data.users) {
            listEl.innerHTML = '<p>' + L.no_data + '</p>';
            return;
        }
        const users = data.users;
        let html = '<table><thead><tr><th>' + L.username + '</th><th>' + L.permissions + '</th><th></th></tr></thead><tbody>';
        users.forEach(u => {
            html += `<tr>
                <td>${u.username}</td>
                <td>${Array.isArray(u.permissions) ? u.permissions.join(', ') : u.permissions}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editUser('${u.username}')">${L.edit}</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteUser('${u.username}')">${L.delete}</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        listEl.innerHTML = html;
    } catch (e) {
        listEl.innerHTML = '<p class="error">' + L.loading + '...</p>';
    }
}

// 加载用户下拉框（供管理员选择）
async function loadUserSelect() {
    const select = document.getElementById('targetUserSelect');
    if (!select) return;
    try {
        const res = await fetch('../api/users.php');
        const data = await res.json();
        if (data.success && data.users) {
            select.innerHTML = '<option value="">' + window.__USER_LANG.select_user + '</option>';
            data.users.forEach(u => {
                select.innerHTML += `<option value="${u.username}">${u.username}</option>`;
            });
        }
    } catch (e) {
        console.error(e);
    }
}

// 显示新增用户弹窗
function showUserForm() {
    document.getElementById('userModal').style.display = 'block';
    document.getElementById('userModalTitle').innerText = window.__USER_LANG.user_add_title;
    const form = document.getElementById('userForm');
    form.reset();
    form.username_old.value = '';
    form.username.disabled = false;
    form.password.required = true;
}

// 关闭弹窗
function closeUserModal() {
    document.getElementById('userModal').style.display = 'none';
}

// 编辑用户
async function editUser(username) {
    const L = window.__USER_LANG;
    try {
        const res = await fetch('../api/users.php');
        const data = await res.json();
        const user = data.users.find(u => u.username === username);
        if (!user) return;
        showUserForm();
        document.getElementById('userModalTitle').innerText = L.user_edit_title;
        const form = document.getElementById('userForm');
        form.username_old.value = username;
        form.username.value = username;
        form.username.disabled = true;
        form.password.required = false;
        form.password.placeholder = L.password_placeholder;
        document.querySelectorAll('input[name="permissions[]"]').forEach(cb => {
            cb.checked = Array.isArray(user.permissions) && user.permissions.includes(cb.value);
        });
    } catch (e) {
        alert(L.error);
    }
}

// 删除用户
async function deleteUser(username) {
    const L = window.__USER_LANG;
    if (!confirm(`${L.delete_confirm} ${username}？`)) return;
    try {
        const res = await fetch('../api/users.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username })
        });
        const result = await res.json();
        if (result.success) {
            loadUsers();
            loadUserSelect();
        } else {
            alert(L.error);
        }
    } catch (e) {
        alert(L.error);
    }
}

// 用户表单提交（新增/编辑）
document.getElementById('userForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const L = window.__USER_LANG;
    const formData = new FormData(e.target);
    const username = formData.get('username');
    const password = formData.get('password');
    const permissions = formData.getAll('permissions[]');
    const oldUsername = formData.get('username_old');

    const method = oldUsername ? 'PUT' : 'POST';
    const body = oldUsername
        ? JSON.stringify({ username: oldUsername, new_username: username, permissions })
        : JSON.stringify({ username, password, permissions });

    try {
        const res = await fetch('../api/users.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body
        });
        const result = await res.json();
        if (result.success) {
            loadUsers();
            loadUserSelect();
            closeUserModal();
        } else {
            alert(L.error + ': ' + (result.error || ''));
        }
    } catch (e) {
        alert(L.error);
    }
});

// 修改自己的密码
document.getElementById('changeMyPasswordForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const form = e.target;
    const newPassword = form.new_password.value;
    const confirmPassword = form.confirm_password.value;
    const L = window.__USER_LANG;

    if (newPassword !== confirmPassword) {
        alert(L.password_mismatch);
        return;
    }

    try {
        const res = await fetch('../api/users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                username: window.__CURRENT_USER,
                password: newPassword
            })
        });
        const result = await res.json();
        if (result.success) {
            alert(L.password_changed);
            form.reset();
        } else {
            alert(L.error + ': ' + (result.error || ''));
        }
    } catch (e) {
        alert(L.error);
    }
});

// 管理员修改其他用户密码
const changeUserPasswordForm = document.getElementById('changeUserPasswordForm');
if (changeUserPasswordForm) {
    changeUserPasswordForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = e.target;
        const targetUser = form.target_user.value;
        const newPassword = form.new_password.value;
        const confirmPassword = form.confirm_password.value;
        const L = window.__USER_LANG;

        if (!targetUser) {
            alert(L.select_user);
            return;
        }
        if (newPassword !== confirmPassword) {
            alert(L.password_mismatch);
            return;
        }

        try {
            const res = await fetch('../api/users.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    username: targetUser,
                    password: newPassword
                })
            });
            const result = await res.json();
            if (result.success) {
                alert(L.password_changed);
                form.reset();
            } else {
                alert(L.error + ': ' + (result.error || ''));
            }
        } catch (e) {
            alert(L.error);
        }
    });
}

// 页面初始化
document.addEventListener('DOMContentLoaded', function () {
    loadUsers();
    loadUserSelect();
});