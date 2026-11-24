// ========== 项目管理脚本 ==========
const PROJECT_LANG = window.__PROJECT_LANG || {};
const PT = function (key, fallback) {
    return PROJECT_LANG[key] || fallback;
};

// 显示新增表单
function showProjectForm() {
    document.getElementById('projectModal').style.display = 'block';
    document.getElementById('projectModalTitle').innerText = PT('add_title', 'Add project');
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
        if (!res.ok) throw new Error(PT('error', 'Failed to load'));
        const proj = await res.json();

        document.getElementById('projectModal').style.display = 'block';
        document.getElementById('projectModalTitle').innerText = PT('edit_title', 'Edit project');
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

        // 图片预览（使用绝对路径）
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
        console.error('Project edit failed:', e);
        alert(PT('error', 'Failed to load') + ': ' + e.message);
    }
}

// 删除项目
async function deleteProject(id) {
    if (!confirm(PT('delete_confirm', 'Delete this project?'))) return;
    try {
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'projects', action: 'delete', data: { id } })
        });
        const result = await res.json();
        if (result.success) {
            location.reload();
        } else {
            alert(PT('save_fail', 'Delete failed') + ': ' + (result.error || PT('error', 'Unknown error')));
        }
    } catch (e) {
        alert(PT('network_error', 'Network error'));
    }
}

// 图片上传（携带 type 参数指定为 projects）
async function uploadProjectImage() {
    const fileInput = document.getElementById('projectImageUpload');
    const file = fileInput.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'projects');

    const preview = document.getElementById('projectImagePreview');
    preview.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> ' + PT('upload_btn', 'Upload') + '...';
    try {
        const res = await fetch('../api/upload.php', { method: 'POST', body: formData });
        const result = await res.json();
        if (result.success) {
            document.getElementById('projectImage').value = result.url;
            preview.innerHTML = `<img src="/${result.url}" style="max-width:200px; border-radius:6px;">`;
        } else {
            preview.innerHTML = `<span style="color:red;">${result.error || PT('upload_fail', 'Upload failed')}</span>`;
        }
    } catch (e) {
        preview.innerHTML = '<span style="color:red;">' + PT('network_error', 'Network error') + '</span>';
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
                    alert(PT('save_success', 'Saved'));
                    location.reload();
                } else {
                    alert(PT('save_fail', 'Save failed') + ': ' + (result.error || PT('error', 'Unknown error')));
                }
            } catch (e) {
                alert(PT('network_error', 'Network error'));
            }
        });
    }
});

// ========== EasyMDE 编辑器初始化 ==========
function initProjectEditor() {
    const textarea = document.getElementById('projectContent');
    if (!textarea) return;
    if (window.projectEditor) {
        return;
    }
    window.projectEditor = new EasyMDE({
        element: textarea,
        spellChecker: false,
        autoDownloadFontAwesome: false,
        placeholder: PT('editor_placeholder', 'Enter Markdown content...'),
        toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'image', '|', 'preview', 'side-by-side', 'fullscreen', '|', 'guide']
    });
}

// 页面加载后尝试初始化编辑器
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(initProjectEditor, 100);
});
