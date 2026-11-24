document.addEventListener('DOMContentLoaded', async function () {
    const container = document.getElementById('market-container');
    const L = window.__MARKET_LANG;

    try {
        // 从远程 API 获取市场数据（后续由你自行搭建）
        const res = await fetch('https://example.com/api/market.php');
        if (!res.ok) throw new Error('Market unavailable');
        const plugins = await res.json();
        if (!plugins || plugins.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">' + L.no_plugins + '</p>';
            return;
        }

        let html = '<div class="row">';
        plugins.forEach(p => {
            html += `
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5>${p.name}</h5>
                            <p>${p.description}</p>
                            <p class="text-muted small">${L.version_label} ${p.version} | ${L.author_label} ${p.author}</p>
                            <button class="btn btn-sm btn-primary install-market-plugin" data-url="${p.download_url}">${L.install_btn}</button>
                        </div>
                    </div>
                </div>`;
        });
        html += '</div>';
        container.innerHTML = html;

        // 绑定安装按钮事件
        document.querySelectorAll('.install-market-plugin').forEach(btn => {
            btn.addEventListener('click', async function () {
                const url = this.dataset.url;
                if (!confirm(L.install_confirm)) return;

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> ' + L.installing;

                try {
                    const res = await fetch('../api/plugin-install-from-url.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ download_url: url })
                    });
                    const result = await res.json();
                    if (result.success) {
                        alert(L.install_success);
                    } else {
                        alert(L.install_fail + ': ' + (result.error || ''));
                        this.disabled = false;
                        this.innerHTML = L.install_btn;
                    }
                } catch (e) {
                    alert(L.network_error);
                    this.disabled = false;
                    this.innerHTML = L.install_btn;
                }
            });
        });
    } catch (e) {
        container.innerHTML = '<p class="text-center text-danger">' + L.market_unavailable + '</p>';
    }
});