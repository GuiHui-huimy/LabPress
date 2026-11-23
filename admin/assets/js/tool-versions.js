// 版本管理脚本
let currentTool = '';

// 加载版本列表
async function loadVersions(toolName) {
    currentTool = toolName;
    const container = document.getElementById('versionsContainer');
    if (!toolName) {
        container.innerHTML = '';
        return;
    }
    try {
        const res = await fetch(`../api/data.php?type=tool-versions&name=${encodeURIComponent(toolName)}`);
        const versions = await res.json();
        const L = window.__VERSION_LANG;
        let html = '<h4>' + L.version_list + '</h4>';
        if (!versions || versions.length === 0) {
            html += '<p>' + L.no_version_record + '</p>';
        } else {
            html += '<table><thead><tr><th>' + L.version_number + '</th><th>' + L.version_date + '</th><th>' + L.version_changes + '</th><th>' + L.version_downloads + '</th><th></th></tr></thead><tbody>';
            versions.forEach(v => {
                const changesText = (v.changes && v.changes.length > 0) ? v.changes.join('; ') : '-';
                const downloadsCount = (v.downloads && v.downloads.length > 0) ? v.downloads.length + L.count_unit : L.none;
                html += `<tr>
                    <td>${v.version}</td>
                    <td>${v.date}</td>
                    <td>${changesText}</td>
                    <td>${downloadsCount}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editVersion('${v.id}')">${L.edit}</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteVersion(${v.id})">${L.delete}</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
        }
        html += `<button class="btn btn-primary" onclick="showVersionForm()">${L.version_add}</button>`;
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<p class="error">' + L.error + '</p>';
    }
}

// 显示新增表单
function showVersionForm() {
    document.getElementById('versionModal').style.display = 'block';
    document.getElementById('versionModalTitle').innerText = window.__VERSION_LANG.version_add;
    document.getElementById('versionForm').reset();
    document.getElementById('versionForm').id.value = '';
    document.getElementById('versionToolName').value = currentTool;
    clearChangesAndDownloads();
}

// 关闭模态窗口
function closeVersionModal() {
    document.getElementById('versionModal').style.display = 'none';
}

// 编辑版本
async function editVersion(versionId) {
    try {
        const res = await fetch(`../api/data.php?type=tool-versions&name=${encodeURIComponent(currentTool)}`);
        const versions = await res.json();
        const ver = versions.find(v => v.id == versionId);
        if (!ver) return;

        document.getElementById('versionModal').style.display = 'block';
        document.getElementById('versionModalTitle').innerText = window.__VERSION_LANG.version_edit;
        const form = document.getElementById('versionForm');
        form.id.value = ver.id;
        form.version.value = ver.version;
        form.date.value = ver.date;
        document.getElementById('versionToolName').value = currentTool;

        clearChangesAndDownloads();
        if (ver.changes) {
            ver.changes.forEach(change => addChangeField(change));
        }
        if (ver.downloads) {
            ver.downloads.forEach(dl => addDownloadField(dl.name, dl.url, dl.icon));
        }
        if (!ver.changes || ver.changes.length === 0) addChangeField();
        if (!ver.downloads || ver.downloads.length === 0) addDownloadField();
    } catch (e) {
        alert(window.__VERSION_LANG.error);
    }
}

function clearChangesAndDownloads() {
    document.getElementById('changesContainer').innerHTML = '';
    document.getElementById('downloadsContainer').innerHTML = '';
    addChangeField();
    addDownloadField();
}

function addChangeField(value = '') {
    const container = document.getElementById('changesContainer');
    const div = document.createElement('div');
    div.className = 'change-row';
    div.style.display = 'flex';
    div.style.gap = '0.5rem';
    div.style.marginBottom = '0.5rem';
    div.innerHTML = `
        <input type="text" class="form-control change-input" value="${value.replace(/"/g, '&quot;')}" placeholder="${window.__VERSION_LANG.change_placeholder}">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(div);
}

function addDownloadField(name = '', url = '', icon = 'fas fa-download') {
    const container = document.getElementById('downloadsContainer');
    const div = document.createElement('div');
    div.className = 'download-row';
    div.style.display = 'flex';
    div.style.gap = '0.5rem';
    div.style.marginBottom = '0.5rem';
    div.style.flexWrap = 'wrap';
    div.innerHTML = `
        <input type="text" class="form-control download-name" placeholder="${window.__VERSION_LANG.download_name}" value="${name}" style="flex:1;">
        <input type="text" class="form-control download-url" placeholder="${window.__VERSION_LANG.download_url}" value="${url}" style="flex:2;">
        <input type="text" class="form-control download-icon" placeholder="${window.__VERSION_LANG.download_icon}" value="${icon}" style="flex:1;">
        <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
    `;
    container.appendChild(div);
}

async function deleteVersion(id) {
    if (!confirm(window.__VERSION_LANG.delete_confirm)) return;
    const res = await fetch('../api/save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: 'tool-versions', action: 'delete', data: { id } })
    });
    if ((await res.json()).success) {
        loadVersions(currentTool);
    } else {
        alert(window.__VERSION_LANG.error);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('versionForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const L = window.__VERSION_LANG;
        const id = form.id.value;
        const tool_name = form.tool_name.value;
        const version = form.version.value;
        const date = form.date.value;

        const changes = [];
        document.querySelectorAll('#changesContainer .change-input').forEach(input => {
            const val = input.value.trim();
            if (val) changes.push(val);
        });

        const downloads = [];
        document.querySelectorAll('#downloadsContainer .download-row').forEach(row => {
            const name = row.querySelector('.download-name').value.trim();
            const url = row.querySelector('.download-url').value.trim();
            const icon = row.querySelector('.download-icon').value.trim() || 'fas fa-download';
            if (name || url) {
                downloads.push({ name, url, icon });
            }
        });

        const data = { id: id || undefined, tool_name, version, date, changes, downloads };
        const res = await fetch('../api/save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'tool-versions', data })
        });
        const result = await res.json();
        if (result.success) {
            closeVersionModal();
            loadVersions(currentTool);
        } else {
            alert(L.save_fail + ': ' + (result.error || ''));
        }
    });
});


