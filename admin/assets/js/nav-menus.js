document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('menuModal');
    const form = document.getElementById('menuForm');
    const titleEl = document.getElementById('menuModalTitle');
    const L = window.__NAV_LANG;

    // 新增菜单按钮（头部和页脚）
    document.querySelectorAll('.add-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const location = this.dataset.location;
            form.reset();
            form.id.value = '';
            form.location.value = location;
            titleEl.innerText = (location === 'header') ? L.add_header : L.add_footer;
            modal.style.display = 'block';
        });
    });

    // 编辑按钮
    document.querySelectorAll('.edit-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const menu = JSON.parse(this.dataset.menu);
            form.id.value = menu.id;
            form.title.value = menu.title;
            form.url.value = menu.url;
            form.target.value = menu.target;
            form.sort_order.value = menu.sort_order;
            form.is_active.value = menu.is_active ? '1' : '0';
            form.location.value = menu.location || 'header';
            titleEl.innerText = L.edit_title;
            modal.style.display = 'block';
        });
    });

    // 删除按钮
    document.querySelectorAll('.delete-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            if (!confirm(L.delete_confirm)) return;
            const id = this.dataset.id;
            const res = await fetch('../api/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'nav-menus', action: 'delete', data: { id } })
            });
            const result = await res.json();
            if (result.success) location.reload();
            else alert(L.error + ': ' + result.error);
        });
    });

    // 表单提交
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        data.is_active = parseInt(data.is_active);
        data.sort_order = parseInt(data.sort_order);
        try {
            const res = await fetch('../api/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'nav-menus', data: data })
            });
            const result = await res.json();
            if (result.success) {
                alert(L.save_success);
                location.reload();
            } else {
                alert(L.save_fail + ': ' + result.error);
            }
        } catch (err) {
            alert(L.network_error);
        }
    });
});