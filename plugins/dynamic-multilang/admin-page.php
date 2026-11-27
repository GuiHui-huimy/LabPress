<?php
// Dynamic Multi-Language 3.0 translation workbench.
// Permission and CSRF are handled by the admin Registry / Guard.

use LabPress\I18n\EntityRegistry;
use LabPress\I18n\Repository;
use LabPress\Guard;

$languages = getAvailableLanguages();
$sourceLocale = ml_get_source_locale();
$errors = [];
$notice = '';

$entityLabels = [
    'nav_menu' => __('ml_tab_nav_menus', 'plugin:dynamic-multilang'),
    'slides' => __('ml_tab_slides', 'plugin:dynamic-multilang'),
    'site_config' => __('ml_tab_about', 'plugin:dynamic-multilang'),
    '__custom' => __('ml_tab_dynamic_content', 'plugin:dynamic-multilang'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Guard::verifyCsrf($_POST['_csrf'] ?? '')) {
        $errors[] = __('ml_csrf_error', 'plugin:dynamic-multilang');
    } else {
        $postedSource = (string)($_POST['ml_source_locale'] ?? '');
        if ($postedSource !== '' && in_array($postedSource, array_column($languages, 'locale'), true)) {
            if ($postedSource !== $sourceLocale) {
                ml_set_source_locale($postedSource);
                $sourceLocale = $postedSource;
                $notice = __('ml_source_locale_saved', 'plugin:dynamic-multilang');
            }
        }

        $translations = $_POST['t'] ?? [];
        if (is_array($translations)) {
            foreach ($translations as $entity => $items) {
                if (EntityRegistry::get((string)$entity) === null || !is_array($items)) {
                    continue;
                }
                foreach ($items as $formId => $fields) {
                    if (!is_array($fields)) {
                        continue;
                    }
                    foreach ($fields as $field => $locales) {
                        if (!is_array($locales)) {
                            continue;
                        }
                        $definition = EntityRegistry::get((string)$entity);
                        if (!isset($definition['fields'][(string)$field])) {
                            continue;
                        }
                        $source = (string)($_POST['src'][$entity][$formId][$field] ?? '');
                        $repoId = $entity === '__custom' ? $source : (string)$formId;
                        foreach ($locales as $locale => $value) {
                            if (!in_array((string)$locale, array_column($languages, 'locale'), true)) {
                                continue;
                            }
                            Repository::set((string)$entity, $repoId, (string)$field, (string)$locale, (string)$value, $source);
                        }
                    }
                }
            }
            Repository::invalidate();
            if ($notice === '') {
                $notice = __('ml_saved', 'plugin:dynamic-multilang');
            }
        }
    }
}

if ($errors !== []) {
    echo '<div class="alert alert-danger"><ul class="mb-0">';
    foreach ($errors as $error) {
        echo '<li>' . e($error) . '</li>';
    }
    echo '</ul></div>';
}
if ($notice !== '') {
    echo '<div class="alert alert-success">' . e($notice) . '</div>';
}

$targetLocales = [];
foreach ($languages as $language) {
    if (($language['locale'] ?? '') !== $sourceLocale) {
        $targetLocales[] = $language;
    }
}

$navRows = EntityRegistry::rows('nav_menu');
$slideRows = EntityRegistry::rows('slides');
$siteRows = EntityRegistry::rows('site_config');
$siteValues = [];
foreach ($siteRows as $row) {
    $siteValues[(string)$row['config_key']] = (string)$row['config_value'];
}
$siteFields = [
    'about_us' => __('ml_about_us', 'plugin:dynamic-multilang'),
    'research_title' => __('ml_research_title', 'plugin:dynamic-multilang'),
    'research_subtitle' => __('ml_research_subtitle', 'plugin:dynamic-multilang'),
];

$customItems = [];
$statItems = json_decode((string)($siteValues['stat_items'] ?? '[]'), true) ?: [];
$researchCards = json_decode((string)($siteValues['research_cards'] ?? '[]'), true) ?: [];
foreach ($statItems as $item) {
    $text = (string)($item['label'] ?? '');
    if ($text !== '') {
        $customItems[hash('sha256', $text)] = $text;
    }
}
foreach ($researchCards as $card) {
    foreach (['title', 'desc'] as $key) {
        $text = (string)($card[$key] ?? '');
        if ($text !== '') {
            $customItems[hash('sha256', $text)] = $text;
        }
    }
}

// Batch prefetch: one query per entity/locale, never one per row.
foreach ($targetLocales as $language) {
    $locale = (string)$language['locale'];
    Repository::prefetch('nav_menu', null, ['title'], $locale);
    Repository::prefetch('slides', null, ['title', 'description'], $locale);
    Repository::prefetch('site_config', null, ['config_value'], $locale);
    Repository::prefetch('__custom', null, ['text'], $locale);
}

$counts = [];
foreach ($targetLocales as $language) {
    $locale = (string)$language['locale'];
    $counts[$locale] = [
        'nav_menu' => Repository::translatedCount('nav_menu', $locale),
        'slides' => Repository::translatedCount('slides', $locale),
        'site_config' => Repository::translatedCount('site_config', $locale),
        '__custom' => Repository::translatedCount('__custom', $locale),
    ];
}

$renderField = static function (string $entity, string $formId, string $field, string $source, string $type = 'text') use ($targetLocales): void {
    $input = $type === 'textarea' ? 'textarea' : 'input';
    echo '<input type="hidden" name="src[' . e($entity) . '][' . e($formId) . '][' . e($field) . ']" value="' . e($source) . '">';
    foreach ($targetLocales as $language) {
        $locale = (string)$language['locale'];
        $value = $entity === '__custom'
            ? (Repository::get('__custom', $source, $field, $locale) ?? '')
            : (Repository::get($entity, $formId, $field, $locale) ?? '');
        echo '<div class="form-group">';
        echo '<label>' . e((string)$language['name']) . ' · ' . e(__('ml_translation', 'plugin:dynamic-multilang')) . '</label>';
        $name = 't[' . $entity . '][' . $formId . '][' . $field . '][' . $locale . ']';
        if ($input === 'textarea') {
            echo '<textarea class="form-control" rows="3" name="' . e($name) . '">' . e((string)$value) . '</textarea>';
        } else {
            echo '<input type="text" class="form-control" name="' . e($name) . '" value="' . e((string)$value) . '">';
        }
        echo '</div>';
    }
};
?>
<div class="card">
    <div class="card-header">
        <h3><?= e(__('ml_plugin_name', 'plugin:dynamic-multilang')) ?></h3>
    </div>
    <div class="card-body">
        <form method="post" id="ml-form">
            <input type="hidden" name="_csrf" value="<?= e(Guard::csrfToken()) ?>">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label"><?= e(__('ml_select_source_locale', 'plugin:dynamic-multilang')) ?></label>
                <div class="col-sm-4">
                    <select name="ml_source_locale" class="form-control" onchange="this.form.submit()">
                        <?php foreach ($languages as $language): ?>
                            <option value="<?= e((string)$language['locale']) ?>" <?= (string)$language['locale'] === $sourceLocale ? 'selected' : '' ?>>
                                <?= e((string)$language['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <small class="col-sm-5 form-text text-muted"><?= e(__('ml_select_source_locale_help', 'plugin:dynamic-multilang')) ?></small>
            </div>
            <hr>

            <?php
            $sections = [
                'nav_menu' => [
                    'rows' => $navRows,
                    'total' => count($navRows),
                    'fields' => ['title' => '标题'],
                    'value' => static fn(array $row, string $field): string => (string)($row[$field] ?? ''),
                    'label' => static fn(array $row): string => '#' . (string)($row['id'] ?? ''),
                    'type' => ['title' => 'text'],
                ],
                'slides' => [
                    'rows' => $slideRows,
                    'total' => count($slideRows) * 2,
                    'fields' => ['title' => '标题', 'description' => '描述'],
                    'value' => static fn(array $row, string $field): string => (string)($row[$field] ?? ''),
                    'label' => static fn(array $row): string => '#' . (string)($row['id'] ?? ''),
                    'type' => ['title' => 'text', 'description' => 'textarea'],
                ],
                'site_config' => [
                    'rows' => array_map(static fn(string $field, string $label): array => ['config_key' => $field, 'label' => $label, 'config_value' => $siteValues[$field] ?? ''], array_keys($siteFields), array_values($siteFields)),
                    'total' => count($siteFields),
                    'fields' => ['config_value' => '文本'],
                    'value' => static fn(array $row, string $field): string => (string)($row[$field] ?? ''),
                    'label' => static fn(array $row): string => (string)($row['label'] ?? $row['config_key']),
                    'type' => ['config_value' => 'textarea'],
                ],
                '__custom' => [
                    'rows' => array_map(static fn(string $hash, string $text): array => ['id' => $hash, 'text' => $text], array_keys($customItems), array_values($customItems)),
                    'total' => count($customItems),
                    'fields' => ['text' => '文本'],
                    'value' => static fn(array $row, string $field): string => (string)($row[$field] ?? ''),
                    'label' => static fn(array $row): string => __('ml_dynamic_content', 'plugin:dynamic-multilang') . ': ' . (function_exists('mb_substr') ? mb_substr((string)($row['text'] ?? ''), 0, 40) : substr((string)($row['text'] ?? ''), 0, 40)),
                    'type' => ['text' => 'text'],
                ],
            ];
            foreach ($sections as $entity => $section):
                $total = (int)$section['total'];
            ?>
                <details class="ml-section" open>
                    <summary>
                        <strong><?= e($entityLabels[$entity] ?? $entity) ?></strong>
                        <span class="text-muted">
                            <?php foreach ($targetLocales as $language): ?>
                                <?php $done = (int)($counts[(string)$language['locale']][$entity] ?? 0); ?>
                                · <?= e((string)$language['name']) ?> <?= $done ?>/<?= $total ?>
                            <?php endforeach; ?>
                        </span>
                    </summary>
                    <?php if (empty($section['rows'])): ?>
                        <p class="text-muted"><?= e(__('ml_no_items', 'plugin:dynamic-multilang')) ?></p>
                    <?php else: ?>
                        <?php foreach ($section['rows'] as $row): ?>
                            <?php $formId = (string)($row['config_key'] ?? $row['id'] ?? ''); ?>
                            <div class="card mb-3">
                                <div class="card-header"><?= e((string)($section['label'])($row)) ?></div>
                                <div class="card-body">
                                    <?php foreach ($section['fields'] as $field => $fieldLabel): ?>
                                        <h6><?= e((string)$fieldLabel) ?></h6>
                                        <div class="form-group">
                                            <label><?= e(str_replace('{locale}', $sourceLocale, __('ml_original_content', 'plugin:dynamic-multilang'))) ?></label>
                                            <?php $source = (string)($section['value'])($row, $field); ?>
                                            <?php if (($section['type'][$field] ?? 'text') === 'textarea'): ?>
                                                <textarea class="form-control" readonly rows="3"><?= e($source) ?></textarea>
                                            <?php else: ?>
                                                <input type="text" class="form-control" readonly value="<?= e($source) ?>">
                                            <?php endif; ?>
                                        </div>
                                        <?php $renderField($entity, $formId, $field, $source, $section['type'][$field] ?? 'text'); ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </details>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary mt-3"><?= e(__('ml_save_all', 'plugin:dynamic-multilang')) ?></button>
        </form>
    </div>
</div>
