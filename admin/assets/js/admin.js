// 统一为后台的写请求附加 CSRF token（v1.1.1）
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    if (!token || !window.fetch) return;

    var originalFetch = window.fetch.bind(window);
    window.fetch = function (input, init) {
        init = init || {};
        var method = (init.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            init.headers = new Headers(init.headers || {});
            if (!init.headers.has('X-CSRF-Token')) {
                init.headers.set('X-CSRF-Token', token);
            }
        }
        return originalFetch(input, init);
    };
})();

document.addEventListener('DOMContentLoaded', function () {
    // 绑定父菜单点击事件
    document.querySelectorAll('.nav-parent').forEach(function (parent) {
        parent.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var submenu = document.getElementById(targetId);
            if (!submenu) return;

            // 切换子菜单显示
            var isVisible = submenu.style.display === 'block';
            submenu.style.display = isVisible ? 'none' : 'block';

            // 切换箭头方向与父菜单高亮
            this.classList.toggle('expanded', !isVisible);
        });
    });

    // 顶栏时钟
    var clock = document.getElementById('adminClock');
    if (clock) {
        var tick = function () {
            var d = new Date();
            clock.textContent = [d.getHours(), d.getMinutes(), d.getSeconds()]
                .map(function (n) { return String(n).padStart(2, '0'); })
                .join(':');
        };
        tick();
        window.setInterval(tick, 1000);
    }

    // 移动端侧边栏开关
    var sidebarToggle = document.getElementById('sidebarToggle');
    var sidebarOverlay = document.getElementById('sidebarOverlay');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });
    }
    document.querySelectorAll('.sidebar-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            document.body.classList.remove('sidebar-open');
        });
    });
});
