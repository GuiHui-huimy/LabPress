document.addEventListener('DOMContentLoaded', function () {
    const L = window.__PLUGINS_LANG;

    // 启用/停用按钮
    document.querySelectorAll('.toggle-plugin').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const slug = this.dataset.slug;
            const action = this.dataset.action;
            try {
                const res = await fetch('../api/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'plugins', action: action, data: { slug: slug } })
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert((action === 'activate' ? L.activate_fail : L.deactivate_fail) + ': ' + (result.error || ''));
                }
            } catch (e) {
                alert(L.network_error);
            }
        });
    });

    // 扫描新插件按钮
    const syncBtn = document.getElementById('sync-plugins-btn');
    if (syncBtn) {
        syncBtn.addEventListener('click', async function () {
            this.disabled = true;
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> ' + L.sync_scanning;
            try {
                const res = await fetch('../api/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'plugins', action: 'sync', data: {} })
                });
                const result = await res.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert(L.sync_fail + ': ' + (result.error || ''));
                    this.disabled = false;
                    this.innerHTML = originalText;
                }
            } catch (e) {
                alert(L.network_error);
                this.disabled = false;
                this.innerHTML = originalText;
            }
        });
    }
});