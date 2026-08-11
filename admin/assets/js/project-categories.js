// 项目分类管理脚本

function showCatForm() {
    document.getElementById('catModal').style.display = 'block';
    document.getElementById('catModalTitle').innerText = window.__CAT_LANG.add_title;
    const form = document.getElementById('catForm');
    form.reset();
    form.old_slug.value = '';
}

function closeCatModal() {
    document.getElementById('catModal').style.display = 'none';
}

function editCat(cat) {
    showCatForm();
    document.getElementById('catModalTitle').innerText = window.__CAT_LANG.edit_title;
    const form = document.getElementById('catForm');
    form.old_slug.value = cat.slug;
    form.slug.value = cat.slug;
    form.name.value = cat.name;
    form.description.value = cat.description || '';
    form.sort_order.value = cat.sort_order || 0;
}

async function deleteCat(slug) {
    if (!confirm(window.__CAT_LANG.delete_confirm)) return;
    const res = await fetch('../api/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'project-categories', action: 'delete', data: { slug } })
    });
    const result = await res.json();
    if (result.success) location.reload();
    else alert(window.__CAT_LANG.error + ': ' + (result.error || ''));
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('catForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const L = window.__CAT_LANG;
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.sort_order = parseInt(data.sort_order) || 0;
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'project-categories', data })
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert(L.save_fail + ': ' + (result.error || ''));
    });
});