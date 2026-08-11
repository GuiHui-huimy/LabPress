// 工具详情管理脚本

async function loadDetailForm(name) {
    const container = document.getElementById('detailFormContainer');
    if (!name) {
        container.style.display = 'none';
        return;
    }
    try {
        const res = await fetch(`../api/data.php?type=tool-detail&name=${encodeURIComponent(name)}`);
        const detail = await res.json();
        const form = document.getElementById('detailForm');
        form.tool_name.value = name;
        form.short_description.value = detail.short_description || '';
        form.detailed_description.value = detail.detailed_description || '';
        form.version.value = detail.version || '';
        form.language.value = detail.language || '';
        form.license.value = detail.license || '';
        form.update_date.value = detail.update_date || '';
        form.github.value = detail.github || '';
        form.document.value = detail.document || '';
        form.homepage.value = detail.homepage || '';
        form.image.value = detail.image || '';
        form.screenshot.value = detail.screenshot || '';
        form.citation_title.value = detail.citation ? detail.citation.title : '';
        form.citation_url.value = detail.citation ? detail.citation.url : '';
        container.style.display = 'block';
    } catch (e) {
        alert(window.__TOOL_DETAIL_LANG.error);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('detailForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const L = window.__TOOL_DETAIL_LANG;
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        try {
            const res = await fetch('../api/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'tool-detail', data })
            });
            const result = await res.json();
            if (result.success) {
                alert(L.save_success);
            } else {
                alert(L.save_fail + ': ' + (result.error || ''));
            }
        } catch (e) {
            alert(L.error);
        }
    });
});