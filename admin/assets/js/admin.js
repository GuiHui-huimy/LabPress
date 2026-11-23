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
});
