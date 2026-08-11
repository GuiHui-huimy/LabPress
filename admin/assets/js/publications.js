// 论文管理脚本

function showPubForm() {
    document.getElementById('pubModal').style.display = 'block';
    document.getElementById('pubModalTitle').innerText = window.__PUB_LANG.add_title;
    const form = document.getElementById('pubForm');
    form.reset();
    form.id.value = '';
}

function closePubModal() {
    document.getElementById('pubModal').style.display = 'none';
}

function editPub(pub) {
    showPubForm();
    document.getElementById('pubModalTitle').innerText = window.__PUB_LANG.edit_title;
    const form = document.getElementById('pubForm');
    form.id.value = pub.id || '';
    form.title.value = pub.title;
    form.authors.value = pub.authors;
    form.journal.value = pub.journal;
    form.link.value = pub.link || '';
    form.sort_order.value = pub.sort_order || 0;
}

async function deletePub(id) {
    if (!confirm(window.__PUB_LANG.delete_confirm)) return;
    const res = await fetch('../api/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'publications', action: 'delete', data: { id } })
    });
    const result = await res.json();
    if (result.success) location.reload();
    else alert(window.__PUB_LANG.error + ': ' + (result.error || ''));
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('pubForm');
    if (!form) return;
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const L = window.__PUB_LANG;
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        data.sort_order = parseInt(data.sort_order) || 0;
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'publications', data })
        });
        const result = await res.json();
        if (result.success) location.reload();
        else alert(L.save_fail + ': ' + (result.error || ''));
    });
});