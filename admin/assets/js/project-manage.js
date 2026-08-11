// ========== 项目管理脚本 ==========

// 显示新增表单
function showProjectForm() {
    document.getElementById('projectModal').style.display = 'block';
    document.getElementById('projectModalTitle').innerText = '新增项目';
    document.getElementById('projectForm').reset();
    document.getElementById('projectForm').id.value = '';
    document.getElementById('projectImagePreview').innerHTML = '';
    // 若已初始化编辑器，清空内容
    if (window.projectEditor) {
        window.projectEditor.value('');
    }
}

// 关闭弹窗
function closeProjectModal() {
    document.getElementById('projectModal').style.display = 'none';
}

// 编辑项目
async function editProject(id) {
    try {
        const baseUrl = window.location.origin + '/api/data.php?type=project-detail&id=' + id;
        const res = await fetch(baseUrl);
        if (!res.ok) throw new Error('加载失败');
        const proj = await res.json();

        document.getElementById('projectModal').style.display = 'block';
        document.getElementById('projectModalTitle').innerText = '编辑项目';
        const form = document.getElementById('projectForm');
        form.id.value = proj.id;
        form.title.value = proj.title || '';
        form.summary.value = proj.summary || '';
        form.image.value = proj.image || '';
        form.category_slug.value = proj.category_slug || '';
        form.github_url.value = proj.github_url || '';
        form.doc_url.value = proj.doc_url || '';
        form.external_url.value = proj.external_url || '';
        form.sort_order.value = proj.sort_order ?? 0;
        form.featured.value = proj.featured ? '1' : '0';

        // 图片预览（修正：使用绝对路径）
        const preview = document.getElementById('projectImagePreview');
        if (proj.image) {
            preview.innerHTML = `<img src="/${proj.image}" style="max-width:200px; border-radius:6px;">`;
        } else {
            preview.innerHTML = '';
        }

        // Markdown 编辑器赋值
        if (window.projectEditor) {
            window.projectEditor.value(proj.content || '');
        } else {
            const textarea = document.getElementById('projectContent');
            if (textarea) textarea.value = proj.content || '';
        }
    } catch (e) {
        console.error('编辑项目异常:', e);
        alert('加载项目数据失败: ' + e.message);
    }
}

// 删除项目
async function deleteProject(id) {
    if (!confirm('确定删除该项目？')) return;
    try {
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'projects', action: 'delete', data: { id } })
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert('删除失败: ' + (result.error || '未知错误'));
    } catch (e) {
        alert('网络错误');
    }
}

// 图片上传（已修正：携带 type 参数指定为 'projects'，并修正预览路径）
async function uploadProjectImage() {
    const fileInput = document.getElementById('projectImageUpload');
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'projects');   // 关键：指定上传类型，对应 uploads/projects/ 目录

    const preview = document.getElementById('projectImagePreview');
    preview.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> 上传中...';
    try {
        const res = await fetch('../api/upload.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            document.getElementById('projectImage').value = result.url;
            // 修正：使用绝对路径显示预览
            preview.innerHTML = `<img src="/${result.url}" style="max-width:200px; border-radius:6px;">`;
        } else {
            preview.innerHTML = `<span style="color:red;">${result.error || '上传失败'}</span>`;
        }
    } catch (e) {
        preview.innerHTML = '<span style="color:red;">网络错误</span>';
    }
}

// 表单提交绑定
document.addEventListener('DOMContentLoaded', function () {
    const projectForm = document.getElementById('projectForm');
    if (projectForm) {
        projectForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            data.featured = parseInt(data.featured);
            data.sort_order = parseInt(data.sort_order);
            try {
                const res = await fetch('../api/save.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ type: 'projects', data })
                });
                const result = await res.json();
                if (result.success) {
                    alert('保存成功');
                    location.reload();
                } else {
                    alert('保存失败: ' + (result.error || '未知错误'));
                }
            } catch (e) {
                alert('保存请求失败');
            }
        });
    }
});

// ========== EasyMDE 编辑器初始化 ==========
function initProjectEditor() {
    const textarea = document.getElementById('projectContent');
    if (!textarea) return;
    if (window.projectEditor) {
        return; // 已存在实例
    }
    window.projectEditor = new EasyMDE({
        element: textarea,
        spellChecker: false,
        autoDownloadFontAwesome: false,
        placeholder: '输入 Markdown 内容...',
        toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'image', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide']
    });
}

// 页面加载后尝试初始化编辑器
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(initProjectEditor, 100);
});