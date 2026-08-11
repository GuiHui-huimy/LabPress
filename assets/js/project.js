
// 项目分类筛选
document.addEventListener('DOMContentLoaded', function () {
    const filterBtns = document.querySelectorAll('.project-filters .filter-btn');
    const cards = document.querySelectorAll('.project-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');

            // 更新按钮激活样式
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // 显示/隐藏卡片
            cards.forEach(card => {
                if (filter === '*' || card.classList.contains(filter.substring(1))) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        });
    });
});
