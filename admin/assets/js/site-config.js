// admin/assets/js/site-config.js

document.addEventListener('DOMContentLoaded', function () {
    const L = window.__SITE_CONFIG_LANG || {};

    // 添加统计项
    document.getElementById('addStatItemBtn').addEventListener('click', function () {
        const container = document.getElementById('statItemsContainer');
        const div = document.createElement('div');
        div.className = 'stat-item-row';
        div.style.marginBottom = '1rem';
        div.style.border = '1px solid #ddd';
        div.style.padding = '1rem';
        div.style.borderRadius = '6px';
        div.innerHTML = `
            <div class="form-row">
                <div class="form-group" style="flex:0.8;">
                    <label>${L.stat_icon || '图标'}</label>
                    <input type="text" name="stat_icon[]" class="form-control" value="fas fa-star" placeholder="fas fa-tools">
                </div>
                <div class="form-group" style="flex:1.2;">
                    <label>${L.stat_label || '标题'}</label>
                    <input type="text" name="stat_label[]" class="form-control" required>
                </div>
                <div class="form-group" style="flex:0.8;">
                    <label>${L.stat_value || '数值'}</label>
                    <input type="text" name="stat_value[]" class="form-control" required>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.stat-item-row').remove()">× ${L.delete || '删除'}</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });

    // 添加研究方向卡片
    document.getElementById('addResearchCardBtn').addEventListener('click', function () {
        const container = document.getElementById('researchCardsContainer');
        const div = document.createElement('div');
        div.className = 'research-card-row';
        div.style.marginBottom = '1rem';
        div.style.border = '1px solid #ddd';
        div.style.padding = '1rem';
        div.style.borderRadius = '6px';
        div.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label>${L.research_icon || '图标'}</label>
                    <input type="text" name="research_icon[]" class="form-control" placeholder="flask">
                </div>
                <div class="form-group">
                    <label>${L.research_card_title || '标题'}</label>
                    <input type="text" name="research_card_title[]" class="form-control">
                </div>
                <div class="form-group">
                    <label>${L.research_card_desc || '描述'}</label>
                    <textarea name="research_card_desc[]" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.research-card-row').remove()">× ${L.delete || '删除'}</button>
                </div>
            </div>
        `;
        container.appendChild(div);
    });

    // 表单提交
    document.getElementById('siteConfigForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const form = this;
        const formData = new FormData(form);

        // 收集统计项
        const statIcons = formData.getAll('stat_icon[]');
        const statLabels = formData.getAll('stat_label[]');
        const statValues = formData.getAll('stat_value[]');
        const stats = [];
        for (let i = 0; i < statLabels.length; i++) {
            if (statLabels[i] || statValues[i]) {
                stats.push({
                    icon: statIcons[i] || 'fas fa-star',
                    label: statLabels[i],
                    value: statValues[i]
                });
            }
        }

        // 收集研究方向卡片
        const cardIcons = formData.getAll('research_icon[]');
        const cardTitles = formData.getAll('research_card_title[]');
        const cardDescs = formData.getAll('research_card_desc[]');
        const cards = [];
        for (let i = 0; i < cardIcons.length; i++) {
            if (cardIcons[i] || cardTitles[i] || cardDescs[i]) {
                cards.push({
                    icon: cardIcons[i],
                    title: cardTitles[i],
                    desc: cardDescs[i]
                });
            }
        }

        // 构建提交对象
        const data = {};
        for (let [key, val] of formData.entries()) {
            if (key.endsWith('[]')) continue;
            data[key] = val;
        }
        data.stat_items = JSON.stringify(stats);
        data.research_cards = JSON.stringify(cards);

        try {
            const res = await fetch('../api/save.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: 'site-config', data: data })
            });
            const result = await res.json();
            if (result.success) {
                alert(L.save_success || '保存成功');
                location.reload();
            } else {
                alert((L.save_fail || '保存失败') + ': ' + (result.error || '未知错误'));
            }
        } catch (err) {
            alert((L.save_fail || '网络错误') + ': ' + err.message);
        }
    });
});