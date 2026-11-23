<?php
// admin-page.php - 后台管理页面回调
if (!hasPermission('all')) {
    echo '<div class="card"><p>' . __('no_permission', 'admin') . '</p></div>';
    return;
}

$db = LabPress::db();
$languages = getAvailableLanguages();
// 后台统一不使用国旗 emoji，语言名直接展示
foreach ($languages as &$langRow) {
    $langRow['flag'] = '';
}
unset($langRow);
$sourceLocale = ml_get_source_locale();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ml_source_locale'])) {
        ml_set_source_locale($_POST['ml_source_locale']);
        $sourceLocale = $_POST['ml_source_locale'];
    }
    $translations = $_POST['translations'] ?? [];
    foreach ($translations as $table => $items) {
        foreach ($items as $recordId => $fields) {
            foreach ($fields as $field => $locales) {
                foreach ($locales as $locale => $value) {
                    ml_set_translation($table, $recordId, $field, $locale, $value);
                }
            }
        }
    }
    echo '<div class="alert alert-success">' . __('success', 'admin') . '</div>';
}

$targetLocales = array_filter($languages, function($l) use ($sourceLocale) { return $l['locale'] !== $sourceLocale; });

$navMenus = $db->query("SELECT * FROM nav_menu ORDER BY location, sort_order")->fetchAll();
$slides = $db->query("SELECT * FROM slides ORDER BY sort_order")->fetchAll();

$configFields = ['about_us', 'research_title', 'research_subtitle', 'stat_items', 'research_cards'];
$siteConfig = [];
foreach ($configFields as $field) {
    $stmt = $db->prepare("SELECT config_value FROM site_config WHERE config_key=?");
    $stmt->execute([$field]);
    $siteConfig[$field] = $stmt->fetchColumn();
}

$statItems = json_decode($siteConfig['stat_items'] ?? '[]', true) ?: [];
$researchCards = json_decode($siteConfig['research_cards'] ?? '[]', true) ?: [];

$dynamicItems = [];
foreach ($statItems as $item) {
    if (!empty($item['label'])) {
        $dynamicItems[] = ['type' => 'stat_label', 'text' => $item['label']];
    }
}
foreach ($researchCards as $card) {
    if (!empty($card['title'])) {
        $dynamicItems[] = ['type' => 'card_title', 'text' => $card['title']];
    }
    if (!empty($card['desc'])) {
        $dynamicItems[] = ['type' => 'card_desc', 'text' => $card['desc']];
    }
}

// 批量获取所有已存在的自定义翻译（用于填充动态内容输入框）
$existingCustomTrans = [];
$customRes = $db->query("SELECT record_id, locale, translated_value FROM ml_translations WHERE table_name='__custom'");
while ($row = $customRes->fetch()) {
    $existingCustomTrans[$row['record_id']][$row['locale']] = $row['translated_value'];
}

$pluginLangFile = __DIR__ . '/languages/zh_CN.php';
$pluginKeys = file_exists($pluginLangFile) ? include $pluginLangFile : [];

$totalTrans = $db->query("SELECT COUNT(*) FROM ml_translations")->fetchColumn();
if ($totalTrans == 0) {
    echo '<div class="alert alert-warning">Warning: ' . __('no_translations_yet', 'admin') . '</div>';
}
?>
<div class="card">
    <div class="card-header"><h3><?= __('ml_plugin_name','plugin:dynamic-multilang') ?></h3></div>
    <div class="card-body">
        <form method="post" id="ml-form">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label"><?= __('ml_select_source_locale','plugin:dynamic-multilang') ?></label>
                <div class="col-sm-4">
                    <select name="ml_source_locale" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?= htmlspecialchars($lang['locale']) ?>" <?= $lang['locale'] === $sourceLocale ? 'selected' : '' ?>>
                                <?= $lang['flag'] . ' ' . $lang['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <small class="col-sm-5 form-text text-muted"><?= __('ml_select_source_locale_help','plugin:dynamic-multilang') ?></small>
            </div>
            <hr>
            <ul class="nav nav-tabs" id="mlTabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#nav-menus"><?= __('ml_tab_nav_menus','plugin:dynamic-multilang') ?></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#slides"><?= __('ml_tab_slides','plugin:dynamic-multilang') ?></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#about-us"><?= __('ml_tab_about','plugin:dynamic-multilang') ?></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#dynamic-content"><?= __('ml_tab_dynamic_content','plugin:dynamic-multilang') ?></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#plugin-lang"><?= __('ml_tab_plugin_lang','plugin:dynamic-multilang') ?></a></li>
            </ul>
            <div class="tab-content mt-3">
                <!-- 导航菜单 -->
                <div class="tab-pane active" id="nav-menus">
                    <?php foreach ($navMenus as $menu): ?>
                    <div class="card mb-2">
                        <div class="card-header"><?= htmlspecialchars($menu['location']) ?> - <?= htmlspecialchars($menu['title']) ?></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label><?= str_replace('{locale}', $sourceLocale, __('ml_original_content','plugin:dynamic-multilang')) ?></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($menu['title']) ?>" readonly>
                            </div>
                            <?php foreach ($targetLocales as $lang): ?>
                            <div class="form-group">
                                <label><?= $lang['flag'] . ' ' . $lang['name'] ?> <?= __('ml_translation','plugin:dynamic-multilang') ?></label>
                                <input type="text" class="form-control"
                                       name="translations[nav_menu][<?= $menu['id'] ?>][title][<?= $lang['locale'] ?>]"
                                       value="<?= htmlspecialchars(ml_get_translation('nav_menu', $menu['id'], 'title', $lang['locale']) ?: '') ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 轮播图 -->
                <div class="tab-pane" id="slides">
                    <?php foreach ($slides as $slide): ?>
                    <div class="card mb-2">
                        <div class="card-header"><?= __('ml_slide_title','plugin:dynamic-multilang') ?> - <?= htmlspecialchars($slide['title']) ?></div>
                        <div class="card-body">
                            <h6><?= __('ml_slide_title','plugin:dynamic-multilang') ?></h6>
                            <div class="form-group">
                                <label><?= str_replace('{locale}', $sourceLocale, __('ml_original_content','plugin:dynamic-multilang')) ?></label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($slide['title']) ?>" readonly>
                            </div>
                            <?php foreach ($targetLocales as $lang): ?>
                            <div class="form-group">
                                <label><?= $lang['flag'] . ' ' . $lang['name'] ?> <?= __('ml_translation','plugin:dynamic-multilang') ?></label>
                                <input type="text" class="form-control"
                                       name="translations[slides][<?= $slide['id'] ?>][title][<?= $lang['locale'] ?>]"
                                       value="<?= htmlspecialchars(ml_get_translation('slides', $slide['id'], 'title', $lang['locale']) ?: '') ?>">
                            </div>
                            <?php endforeach; ?>
                            <h6><?= __('ml_slide_description','plugin:dynamic-multilang') ?></h6>
                            <div class="form-group">
                                <label><?= str_replace('{locale}', $sourceLocale, __('ml_original_content','plugin:dynamic-multilang')) ?></label>
                                <textarea class="form-control" readonly><?= htmlspecialchars($slide['description']) ?></textarea>
                            </div>
                            <?php foreach ($targetLocales as $lang): ?>
                            <div class="form-group">
                                <label><?= $lang['flag'] . ' ' . $lang['name'] ?> <?= __('ml_translation','plugin:dynamic-multilang') ?></label>
                                <textarea class="form-control"
                                          name="translations[slides][<?= $slide['id'] ?>][description][<?= $lang['locale'] ?>]"><?= htmlspecialchars(ml_get_translation('slides', $slide['id'], 'description', $lang['locale']) ?: '') ?></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 关于我们 / 研究方向 -->
                <div class="tab-pane" id="about-us">
                    <?php 
                    $labels = ['about_us' => __('ml_about_us','plugin:dynamic-multilang'), 'research_title' => __('ml_research_title','plugin:dynamic-multilang'), 'research_subtitle' => __('ml_research_subtitle','plugin:dynamic-multilang')];
                    foreach (['about_us', 'research_title', 'research_subtitle'] as $field): ?>
                    <div class="card mb-2">
                        <div class="card-header"><?= $labels[$field] ?></div>
                        <div class="card-body">
                            <div class="form-group">
                                <label><?= str_replace('{locale}', $sourceLocale, __('ml_original_content','plugin:dynamic-multilang')) ?></label>
                                <textarea class="form-control" readonly rows="3"><?= htmlspecialchars($siteConfig[$field]) ?></textarea>
                            </div>
                            <?php foreach ($targetLocales as $lang): ?>
                            <div class="form-group">
                                <label><?= $lang['flag'] . ' ' . $lang['name'] ?> <?= __('ml_translation','plugin:dynamic-multilang') ?></label>
                                <textarea class="form-control"
                                          name="translations[site_config][<?= $field ?>][config_value][<?= $lang['locale'] ?>]"><?= htmlspecialchars(ml_get_translation('site_config', $field, 'config_value', $lang['locale']) ?: '') ?></textarea>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 动态内容翻译 -->
                <div class="tab-pane" id="dynamic-content">
                    <p class="text-muted"><?= __('ml_dynamic_content_desc','plugin:dynamic-multilang') ?></p>
                    <?php if (empty($dynamicItems)): ?>
                        <p><?= __('ml_no_dynamic_content','plugin:dynamic-multilang') ?></p>
                    <?php else: ?>
                        <?php foreach ($dynamicItems as $item): ?>
                        <?php $origText = $item['text']; ?>
                        <div class="card mb-2">
                            <div class="card-header">
                                <?= $item['type'] === 'stat_label' ? '统计标签' : '卡片文本' ?>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label><?= str_replace('{locale}', $sourceLocale, __('original_content','plugin:dynamic-multilang')) ?></label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($origText) ?>" readonly>
                                </div>
                                <?php foreach ($targetLocales as $lang): 
                                    $savedVal = $existingCustomTrans[$origText][$lang['locale']] ?? '';
                                ?>
                                <div class="form-group">
                                    <label><?= $lang['flag'] . ' ' . $lang['name'] ?> <?= __('ml_translation','plugin:dynamic-multilang') ?></label>
                                    <input type="text" class="form-control"
                                           name="translations[__custom][<?= htmlspecialchars($origText) ?>][text][<?= $lang['locale'] ?>]"
                                           value="<?= htmlspecialchars($savedVal) ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- 插件自身语言 -->
                <div class="tab-pane" id="plugin-lang">
                    <p class="text-muted"><?= __('ml_plugin_lang_help','plugin:dynamic-multilang') ?></p>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th><?= __('ml_key','plugin:dynamic-multilang') ?></th>
                                <th><?= __('ml_default_value','plugin:dynamic-multilang') ?> (<?= $sourceLocale ?>)</th>
                                <?php foreach ($targetLocales as $lang): ?>
                                    <th><?= $lang['flag'] . ' ' . $lang['name'] ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pluginKeys as $key => $defaultText): ?>
                            <tr>
                                <td><code><?= htmlspecialchars($key) ?></code></td>
                                <td><?= htmlspecialchars($defaultText) ?></td>
                                <?php foreach ($targetLocales as $lang): ?>
                                    <td>
                                        <input type="text" class="form-control"
                                               name="translations[plugin_dynamic-multilang][<?= $key ?>][plugin_text][<?= $lang['locale'] ?>]"
                                               value="<?= htmlspecialchars(ml_get_translation('plugin_dynamic-multilang', $key, 'plugin_text', $lang['locale']) ?: '') ?>"
                                               style="min-width:200px;">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3"><?= __('ml_save_all','plugin:dynamic-multilang') ?></button>
        </form>
    </div>
</div>