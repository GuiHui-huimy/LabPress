// ========== 轮播图逻辑（带备用） ==========
(function () {
    let slides = [],
        indicators = [],
        currentSlide = 0,
        slideInterval;

    async function loadSlides() {
        try {
            const res = await fetch('/api/data.php?type=slides');
            if (!res.ok) throw new Error('网络错误');
            const data = await res.json();
            if (data && data.length > 0) {
                buildCarousel(data);
                return;
            }
        } catch (e) {
            console.warn('轮播图数据加载失败，使用静态备用内容', e);
        }
        // 使用静态备用轮播（已在 HTML 中）
        slides = document.querySelectorAll('#slider-container .slide');
        indicators = document.querySelectorAll('#slider-indicators .indicator');
        if (slides.length) initCarousel();
    }

    function buildCarousel(data) {
        let slidesHtml = '',
            indHtml = '';
        data.forEach((slide, i) => {
            slidesHtml += `<div class="slide${i === 0 ? ' active' : ''}" style="background-image: url('${slide.image}');"><div class="slide-content"><h1>${slide.title}</h1><p>${slide.description}</p></div></div>`;
            indHtml += `<button class="indicator${i === 0 ? ' active' : ''}" data-index="${i}"></button>`;
        });
        document.getElementById('slider-container').innerHTML = slidesHtml;
        document.getElementById('slider-indicators').innerHTML = indHtml;
        slides = document.querySelectorAll('#slider-container .slide');
        indicators = document.querySelectorAll('#slider-indicators .indicator');
        initCarousel();
    }

    function initCarousel() {
        if (!slides.length) return;
        slideInterval = setInterval(() => goToSlide((currentSlide + 1) % slides.length), 5000);
        indicators.forEach((ind, i) => {
            ind.addEventListener('click', () => {
                clearInterval(slideInterval);
                goToSlide(i);
                slideInterval = setInterval(() => goToSlide((currentSlide + 1) % slides.length), 5000);
            });
        });
        const slider = document.querySelector('.hero-slider');
        slider.addEventListener('mouseenter', () => clearInterval(slideInterval));
        slider.addEventListener('mouseleave', () => slideInterval = setInterval(() => goToSlide((currentSlide + 1) % slides.length), 5000));
    }

    function goToSlide(n) {
        if (!slides[currentSlide]) return;
        slides[currentSlide].classList.remove('active');
        indicators[currentSlide].classList.remove('active');
        currentSlide = n;
        slides[currentSlide].classList.add('active');
        indicators[currentSlide].classList.add('active');
    }

    loadSlides();
})();

// ========== 加载最新成果（只展示最新5篇） ==========
async function loadPublications() {
    const container = document.getElementById('publications-list');
    const moreContainer = document.getElementById('publications-more');
    try {
        const res = await fetch('/api/data.php?type=publications');
        if (!res.ok) throw new Error('数据请求失败');
        const pubs = await res.json();
        if (!pubs || pubs.length === 0) {
            container.innerHTML = '<p style="text-align:center;color:var(--text-light);">暂无最新成果，请稍后关注</p>';
            moreContainer.style.display = 'none';
            return;
        }

        // 只取前5条
        const latestPubs = pubs.slice(0, 5);
        let html = '';
        latestPubs.forEach(p => {
            html += `<div class="publication-item">
                <div class="publication-authors">${p.authors}</div>
                <div class="publication-title"><a href="${p.link || '#'}" target="_blank" rel="noopener">${p.title}</a></div>
                <div class="publication-journal">${p.journal}</div>
            </div>`;
        });
        container.innerHTML = html;

        // 显示“查看更多成果”按钮
        moreContainer.innerHTML = `<a href="publications.php" class="btn-more">查看更多成果 <i class="fas fa-arrow-right"></i></a>`;
        moreContainer.style.display = 'block';
    } catch (e) {
        container.innerHTML = '<p style="color:#dc3545;">加载失败，请刷新重试</p>';
        moreContainer.style.display = 'none';
        console.error('加载最新成果失败:', e);
    }
}

// 页面加载时执行
document.addEventListener('DOMContentLoaded', function () {
    loadPublications();
});