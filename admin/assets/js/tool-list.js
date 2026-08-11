// 工具列表管理脚本

function showToolForm() {
    document.getElementById('toolModal').style.display = 'block';
    document.getElementById('toolModalTitle').innerText = window.__TOOL_LIST_LANG.add_title;
    const form = document.getElementById('toolForm');
    form.reset();
    form.old_name.value = '';
}

function closeToolModal() {
    document.getElementById('toolModal').style.display = 'none';
}

function editTool(tool) {
    showToolForm();
    document.getElementById('toolModalTitle').innerText = window.__TOOL_LIST_LANG.edit_title;
    const form = document.getElementById('toolForm');
    form.old_name.value = tool.name;
    form.name.value = tool.name;
    form.type.value = tool.type;
    form.icon.value = tool.icon || '';
    form.description.value = tool.description;
    form.features.value = tool.features || '';
    form.github.value = tool.github || '';
    form.website.value = tool.website || '';
    form.detail_link.value = tool.detail_link;
    form.sort_order.value = tool.sort_order || 0;
}

async function deleteTool(name) {
    if (!confirm(window.__TOOL_LIST_LANG.delete_confirm)) return;
    const res = await fetch('../api/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'tools', action: 'delete', data: { name } })
    });
    if ((await res.json()).success) location.reload();
    else alert(window.__TOOL_LIST_LANG.error);
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('toolForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const L = window.__TOOL_LIST_LANG;
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.sort_order = parseInt(data.sort_order) || 0;
        const oldName = form.old_name.value;
        if (oldName && oldName !== data.name) {
            data.old_name = oldName;
        }
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'tools', data })
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert(L.save_fail);
    });
});