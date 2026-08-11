// EasyMDE 编辑器实例
let newsEditor = null;

// 初始化 EasyMDE 编辑器（只创建一次）
function initNewsEditor() {
    const textarea = document.getElementById('newsContent');
    if (!textarea) return;
    if (newsEditor) {
        return;
    }
    newsEditor = new EasyMDE({
        element: textarea,
        spellChecker: false,
        autoDownloadFontAwesome: false,
        placeholder: window.__NEWS_LANG.editor_placeholder || '输入 Markdown 内容...',
        toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'image', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide']
    });
}

// 显示新增表单
function showNewsForm() {
    document.getElementById('newsModal').style.display = 'block';
    document.getElementById('newsModalTitle').innerText = window.__NEWS_LANG.add_title;
    const form = document.forms['newsForm'];
    form.reset();
    form.id.value = '';
    document.getElementById('newsImagePreview').innerHTML = '';
    initNewsEditor();
    if (newsEditor) newsEditor.value('');
}

// 关闭弹窗
function closeNewsModal() {
    document.getElementById('newsModal').style.display = 'none';
}

// 编辑新闻：获取详情并填充表单
async function editNews(id) {
    const L = window.__NEWS_LANG;
    try {
        const res = await fetch(`../api/data.php?type=news-detail&id=${id}`);
        if (!res.ok) throw new Error('获取新闻详情失败');
        const news = await res.json();

        document.getElementById('newsModal').style.display = 'block';
        document.getElementById('newsModalTitle').innerText = L.edit_title;
        const form = document.forms['newsForm'];
        form.id.value = news.id;
        form.title.value = news.title;
        form.summary.value = news.summary || '';
        form.image.value = news.image || '';
        form.author.value = news.author || L.author_default;
        form.date.value = news.date;

        const preview = document.getElementById('newsImagePreview');
        if (news.image) {
            preview.innerHTML = `<img src="/${news.image}" style="max-width:200px; border-radius:6px;">`;
        } else {
            preview.innerHTML = '';
        }

        initNewsEditor();
        if (newsEditor) newsEditor.value(news.content || '');
    } catch (err) {
        alert(err.message);
    }
}

// 删除新闻
async function deleteNews(id) {
    const L = window.__NEWS_LANG;
    if (!confirm(L.delete_confirm)) return;
    try {
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'news', action: 'delete', data: { id } })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            alert(L.save_fail + ': ' + (result.error || ''));
        }
    } catch (err) {
        alert(L.network_error);
    }
}

// 图片上传
async function uploadNewsImage() {
    const fileInput = document.getElementById('newsImageUpload');
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'news');

    const preview = document.getElementById('newsImagePreview');
    const L = window.__NEWS_LANG;
    preview.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> ' + L.upload_btn + '...';

    try {
        const res = await fetch('../api/upload.php', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();
        if (result.success) {
            document.getElementById('newsImage').value = result.url;
            preview.innerHTML = `<img src="/${result.url}" style="max-width:200px; border-radius:6px;">`;
        } else {
            preview.innerHTML = `<span style="color:red;">${result.error || L.upload_fail}</span>`;
        }
    } catch (e) {
        preview.innerHTML = '<span style="color:red;">' + L.network_error + '</span>';
    }
}

// 绑定表单提交事件
document.addEventListener('DOMContentLoaded', function () {
    const newsForm = document.getElementById('newsForm');
    if (newsForm) {
        newsForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const L = window.__NEWS_LANG;
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            if (!data.id) delete data.id;
            try {
                const res = await fetch('../api/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'news', data: data })
                });
                const result = await res.json();
                if (result.success) {
                    alert(L.save_success);
                    location.reload();
                } else {
                    alert(L.save_fail + ': ' + (result.error || ''));
                }
            } catch (err) {
                alert(L.network_error);
            }
        });
    }
});