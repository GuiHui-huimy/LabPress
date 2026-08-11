
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
