<?php
if (!hasPermission('all')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}

// 读取所有配置
$stmt = $pdo->query("SELECT config_key, config_value FROM site_config");
$config = [];
while ($row = $stmt->fetch()) {
    $config[$row['config_key']] = $row['config_value'];
}
$siteName = $config['site_name'] ?? '';
$aboutUs = $config['about_us'] ?? '';
$researchTitle = $config['research_title'] ?? __('research_title', 'admin');
$researchSubtitle = $config['research_subtitle'] ?? '';
$researchCards = json_decode($config['research_cards'] ?? '[]', true) ?: [];
$statItems = json_decode($config['stat_items'] ?? '[]', true) ?: [];
?>
<div class="card">
    <div class="card-header">
        <h3><?= __('site_config', 'admin') ?></h3>
    </div>
    <form id="siteConfigForm">

        <!-- 站点语言 -->
        <div class="form-group">
            <label><?= __('site_language', 'admin') ?></label>
            <select name="language" class="form-control">
                <?php foreach (getAvailableLanguages() as $lang): ?>
                    <option value="<?= htmlspecialchars($lang['locale']) ?>" 
                        <?= ($config['language'] ?? 'zh_CN') === $lang['locale'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lang['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 站点名称 -->
        <div class="form-group">
            <label><?= __('site_name', 'admin') ?></label>
            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($siteName) ?>">
        </div>
        <div class="form-group">
            <label><?= __('site_title', 'admin') ?></label>
            <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($config['site_title'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label><?= __('title_format', 'admin') ?></label>
            <input type="text" name="title_format" class="form-control" value="<?= htmlspecialchars($config['title_format'] ?? '{page} - {site}') ?>" placeholder="{page} - {site}">
            <small class="text-muted"><?= __('title_format_help', 'admin') ?></small>
        </div>

        <!-- 关于我们 -->
        <div class="form-group">
            <label><?= __('about_us', 'admin') ?></label>
            <textarea name="about_us" class="form-control" rows="8"><?= htmlspecialchars($aboutUs) ?></textarea>
        </div>

        <!-- 统计数据 -->
        <h4><?= __('stat_items', 'admin') ?></h4>
        <p class="text-muted" style="font-size:0.85rem;"><?= __('stat_items_desc', 'admin') ?></p>
        <div id="statItemsContainer">
            <?php foreach ($statItems as $idx => $item): ?>
            <div class="stat-item-row" >
                <div class="form-row">
                    <div class="form-group" style="flex:0.8;">
                        <label><?= __('stat_icon', 'admin') ?></label>
                        <input type="text" name="stat_icon[]" class="form-control" value="<?= htmlspecialchars($item['icon'] ?? 'fas fa-star') ?>" placeholder="fas fa-tools">
                    </div>
                    <div class="form-group" style="flex:1.2;">
                        <label><?= __('stat_label', 'admin') ?></label>
                        <input type="text" name="stat_label[]" class="form-control" value="<?= htmlspecialchars($item['label'] ?? '') ?>" required>
                    </div>
                    <div class="form-group" style="flex:0.8;">
                        <label><?= __('stat_value', 'admin') ?></label>
                        <input type="text" name="stat_value[]" class="form-control" value="<?= htmlspecialchars($item['value'] ?? '') ?>" required>
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end;">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.stat-item-row').remove()"><i class="fas fa-times"></i> <?= __('delete', 'admin') ?></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-secondary" id="addStatItemBtn">+ <?= __('add_stat_item', 'admin') ?></button>

        <!-- 研究方向标题 -->
        <h4 class="mt-4"><?= __('research_title', 'admin') ?></h4>
        <div class="form-group">
            <label><?= __('research_title_label', 'admin') ?></label>
            <input type="text" name="research_title" class="form-control" value="<?= htmlspecialchars($researchTitle) ?>">
        </div>
        <div class="form-group">
            <label><?= __('research_subtitle', 'admin') ?></label>
            <textarea name="research_subtitle" class="form-control" rows="2"><?= htmlspecialchars($researchSubtitle) ?></textarea>
        </div>

        <!-- 研究方向卡片 -->
        <label><?= __('research_cards', 'admin') ?></label>
        <div id="researchCardsContainer">
            <?php foreach ($researchCards as $card): ?>
            <div class="research-card-row" >
                <div class="form-row">
                    <div class="form-group">
                        <label><?= __('stat_icon', 'admin') ?></label>
                        <input type="text" name="research_icon[]" class="form-control" value="<?= htmlspecialchars($card['icon'] ?? '') ?>" placeholder="flask">
                    </div>
                    <div class="form-group">
                        <label><?= __('stat_label', 'admin') ?></label>
                        <input type="text" name="research_card_title[]" class="form-control" value="<?= htmlspecialchars($card['title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= __('slide_desc', 'admin') ?></label>
                        <textarea name="research_card_desc[]" class="form-control" rows="2"><?= htmlspecialchars($card['desc'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end;">
                        <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.research-card-row').remove()"><i class="fas fa-times"></i> <?= __('delete', 'admin') ?></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-secondary" id="addResearchCardBtn">+ <?= __('add_research_card', 'admin') ?></button>

        <div style="margin-top:2rem;">
            <button type="submit" class="btn btn-primary"><?= __('save_all_settings', 'admin') ?></button>
        </div>
    </form>
</div>

<!-- 注入翻译字符串 -->
<script>
window.__SITE_CONFIG_LANG = {
    save_success: '<?= __('success', 'admin') ?>',
    save_fail: '<?= __('error', 'admin') ?>',
    delete: '<?= __('delete', 'admin') ?>',
    stat_icon: '<?= __('stat_icon', 'admin') ?>',
    stat_label: '<?= __('stat_label', 'admin') ?>',
    stat_value: '<?= __('stat_value', 'admin') ?>',
    research_icon: '<?= __('stat_icon', 'admin') ?>',           // 复用统计图标标签
    research_card_title: '<?= __('stat_label', 'admin') ?>',    // 复用标题标签
    research_card_desc: '<?= __('slide_desc', 'admin') ?>'      // 或单独定义
};
</script>
<script src="assets/js/site-config.js"></script>



