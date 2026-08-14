<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/labpress.php';  

/**
 * 获取所有轮播图（按 sort_order 排序）
 */
function getSlides() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, image, title, description FROM slides ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

/**
 * 获取所有论文成果
 */
function getPublications() {
    global $pdo;
    $stmt = $pdo->query("SELECT title, authors, journal, link FROM publications ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

/**
 * 获取所有工具列表
 */
function getTools() {
    global $pdo;
    $stmt = $pdo->query("SELECT name, type, icon, description, features, github, website, detail_link FROM tools ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

/**
 * 获取单个工具的详细信息
 */
function getToolDetail($toolName) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM tool_details WHERE tool_name = ?");
    $stmt->execute([$toolName]);
    $detail = $stmt->fetch();
    if ($detail) {
        // 转换 citation 字段为对象
        $detail['citation'] = [
            'title' => $detail['citation_title'],
            'url'   => $detail['citation_url']
        ];
        unset($detail['citation_title'], $detail['citation_url']);
    }
    return $detail;
}

/**
 * 获取某个工具的所有版本
 */
function getToolVersions($toolName) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT version, date, changes, downloads FROM tool_versions WHERE tool_name = ? ORDER BY date DESC");
    $stmt->execute([$toolName]);
    $versions = $stmt->fetchAll();
    foreach ($versions as &$v) {
        $v['changes'] = json_decode($v['changes'], true) ?? [];
        $v['downloads'] = json_decode($v['downloads'], true) ?? [];
    }
    return $versions;
}

/**
 * 权限检查（用于后台）
 */
function hasPermission($perm) {
    $userPerms = $_SESSION['permissions'] ?? [];
    return in_array('all', $userPerms) || in_array($perm, $userPerms);
}

function getNews() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, title, summary, image, author, date, link FROM news ORDER BY date DESC, sort_order ASC");
    return $stmt->fetchAll();
}

function getNewsById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$id]);
    $news = $stmt->fetch();
    if ($news) {
        $news['content'] = $news['content'] ?? ''; // 确保 content 不为 null
    }
    return $news;
}

// ========== Markdown 支持 ==========
require_once __DIR__ . '/Parsedown.php';

function markdownToHtml($text) {
    if (empty($text)) return '';
    $parsedown = new Parsedown();
    // 设为 false 允许原始 HTML，设为 true 更安全（只允许 Markdown 生成的标签）
    $parsedown->setSafeMode(false);
    return $parsedown->text($text);
}

function getUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, username, permissions, created_at FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function getProjectById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    $proj = $stmt->fetch();
    if ($proj) {
        // 设置所有可能缺失的字段默认值，避免警告
        $defaults = [
            'content' => $proj['summary'] ?? '',
            'github_url' => '',
            'doc_url' => '',
            'external_url' => '',
            'image' => '',
            'featured' => 1,
            'sort_order' => 0,
        ];
        $proj = array_merge($defaults, $proj);
    }
    return $proj;
}


function getProjects($featuredOnly = false, $limit = 0) {
    global $pdo;
    $sql = "SELECT id, title, summary, content, image, category_slug, featured, github_url, doc_url, external_url, sort_order, created_at 
            FROM projects 
            WHERE 1=1";
    if ($featuredOnly) {
        $sql .= " AND featured = 1";
    }
    $sql .= " ORDER BY sort_order ASC, created_at DESC";
    if ($limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getProjectsByCategory($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, title, summary, content, image, category_slug, featured, github_url, doc_url, external_url, sort_order, created_at 
                           FROM projects WHERE category_slug = ? ORDER BY sort_order ASC, created_at DESC");
    $stmt->execute([$slug]);
    return $stmt->fetchAll();
}

// 获取所有分类
function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT slug, name, description, sort_order FROM project_categories ORDER BY sort_order ASC");
    return $stmt->fetchAll();
}

// 获取项目总数（可按分类过滤）
function getProjectsCount($categorySlug = '') {
    global $pdo;
    $sql = "SELECT COUNT(*) FROM projects";
    $params = [];
    if ($categorySlug !== '') {
        $sql .= " WHERE category_slug = ?";
        $params[] = $categorySlug;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

// 分页获取项目列表（可按分类过滤，按排序和创建时间排序）
function getProjectsPaginated($page = 1, $perPage = 8, $categorySlug = '') {
    global $pdo;
    $offset = ($page - 1) * $perPage;
    $sql = "SELECT id, title, summary, content, image, category_slug, featured, github_url, doc_url, external_url, sort_order, created_at 
            FROM projects";
    $params = [];
    if ($categorySlug !== '') {
        $sql .= " WHERE category_slug = ?";
        $params[] = $categorySlug;
    }
    $sql .= " ORDER BY sort_order ASC, created_at DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $perPage;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * 获取站点配置（所有键值对）
 */
function getSiteConfig() {
    global $pdo;
    $stmt = $pdo->query("SELECT config_key, config_value FROM site_config");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[$row['config_key']] = $row['config_value'];
    }
    return $config;
}

/**
 * 获取启用的导航菜单（按排序）
 */
function getNavMenus($location = 'header') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, title, url, target FROM nav_menu WHERE is_active = 1 AND location = ? ORDER BY sort_order ASC");
    $stmt->execute([$location]);
    return $stmt->fetchAll();
}

/**
 * 获取已启用的插件 slug 列表
 */
function getActivePlugins() {
    global $pdo;
    $stmt = $pdo->query("SELECT slug FROM plugins WHERE status = 1");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

/**
 * 扫描 plugins/ 目录，将新插件插入数据库（已存在的忽略）
 */
function syncPlugins() {
    global $pdo;
    $pluginsDir = __DIR__ . '/../plugins/';
    if (!is_dir($pluginsDir)) return;

    $existingSlugs = $pdo->query("SELECT slug FROM plugins")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $dirs = scandir($pluginsDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || !is_dir($pluginsDir . $dir)) continue;
        $pluginFile = $pluginsDir . $dir . '/plugin.php';
        if (file_exists($pluginFile) && !in_array($dir, $existingSlugs)) {
            // 解析插件头部信息
            $info = [
                'slug' => $dir,
                'name' => $dir,
                'description' => '',
                'version' => '',
                'author' => ''
            ];
            $content = file_get_contents($pluginFile);
            if (preg_match('/Plugin Name:\s*(.+)/i', $content, $m)) $info['name'] = trim($m[1]);
            if (preg_match('/Description:\s*(.+)/i', $content, $m)) $info['description'] = trim($m[1]);
            if (preg_match('/Version:\s*(.+)/i', $content, $m)) $info['version'] = trim($m[1]);
            if (preg_match('/Author:\s*(.+)/i', $content, $m)) $info['author'] = trim($m[1]);

            $stmt = $pdo->prepare("INSERT INTO plugins (slug, name, description, version, author, status) VALUES (?,?,?,?,?,0)");
            $stmt->execute([$info['slug'], $info['name'], $info['description'], $info['version'], $info['author']]);
        }
    }
}

/**
 * 加载所有已启用的插件（执行 plugin.php）
 */
function loadPlugins() {
    $activeSlugs = getActivePlugins();
    $pluginsDir = __DIR__ . '/../plugins/';
    foreach ($activeSlugs as $slug) {
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $slug);
        $pluginFile = $pluginsDir . $slug . '/plugin.php';
        if (file_exists($pluginFile)) {
            require_once $pluginFile;
        }
    }
}

/**
 * 生成全站统一的页面标题
 * @param string $title  页面主标题
 * @param string $suffix 页面后缀（如“新闻详情”“项目列表”）
 * @return string
 */
function getFullTitle($pageTitle = '', $suffix = '') {
    $config = getSiteConfig();
    $siteTitle = $config['site_title'] ?? 'HUI-MY.LAB';
    $format = $config['title_format'] ?? '{page} - {site}';
    
    // 构建页面部分
    $pagePart = $pageTitle;
    if ($suffix) {
        $pagePart .= ' - ' . $suffix;
    }
    
    // 替换模板变量
    $full = str_replace(['{page}', '{site}'], [$pagePart, $siteTitle], $format);
    
    // 允许插件修改
    return LabPress_Hooks::applyFilters('page_title_full', $full, $pageTitle, $suffix);
}

/**
 * 获取当前语言代码
 * 优先级：URL 参数 > Cookie > 系统配置
 */
function getLocale() {
    static $locale = null;
    if ($locale === null) {
        $available = getAvailableLanguages();
        $availLocales = array_column($available, 'locale');
        $defaultLocale = LabPress::config('language', 'zh_CN');

        // 如果是后台页面，强制使用站点默认语言，忽略Cookie/URL参数
        if (defined('IS_ADMIN') || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
            $locale = $defaultLocale;
            return $locale;
        }

        // 前台逻辑：URL参数 > Cookie > 系统配置
        if (!empty($_GET['lang']) && in_array($_GET['lang'], $availLocales)) {
            $locale = $_GET['lang'];
            setcookie('lang', $locale, time() + 86400 * 30, '/');
            return $locale;
        }
        if (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], $availLocales)) {
            $locale = $_COOKIE['lang'];
            return $locale;
        }
        $locale = $defaultLocale;
    }
    return $locale;
}

/**
 * 翻译函数
 * @param string $key    语言键，支持点号分隔的数组路径（如 'footer.links.blog'）
 * @param string $module 模块名，对应 languages/{locale}/{module}.php
 * @return string
 */
function __($key, $module = 'common') {
    static $lang = [];
    static $loadedModules = [];
    static $pluginsLoaded = false;

    $locale = getLocale();
    $baseDir = __DIR__ . '/../languages/';

    // 1. 加载公共语言包
    if (!isset($loadedModules['common'])) {
        $file = "{$baseDir}{$locale}/common.php";
        if (file_exists($file)) {
            $lang = array_merge($lang, include $file);
        }
        $loadedModules['common'] = true;
    }

    // 2. 加载核心模块语言包（非插件模块）
    if ($module !== 'common' && strpos($module, 'plugin:') !== 0 && !isset($loadedModules[$module])) {
        $file = "{$baseDir}{$locale}/{$module}.php";
        if (file_exists($file)) {
            $lang = array_merge($lang, include $file);
        }
        $loadedModules[$module] = true;
    }

    // 3. 处理显式插件语言来源
    if (strpos($module, 'plugin:') === 0) {
        $pluginSlug = substr($module, 7);
        $pluginSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $pluginSlug);
        $pluginLangFile = __DIR__ . "/../plugins/{$pluginSlug}/languages/{$locale}.php";
        echo '<!-- Transition Info: Looking for plugin language file ' . htmlspecialchars($pluginLangFile) . ' -->';
        if (file_exists($pluginLangFile)) {
            $pluginLang = include $pluginLangFile;
            // 直接在当前调用中查找该插件语言包，避免污染全局 $lang
            $value = $pluginLang;
            foreach (explode('.', $key) as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    return LabPress_Hooks::applyFilters('translate', $key, $key, $locale, $module);
                }
                $value = $value[$segment];
            }
            return LabPress_Hooks::applyFilters('translate', $value, $key, $locale, $module);
        }
        // 如果插件语言文件不存在，回退到键名
        echo '<!-- Transition Warning: Plugin language file not found for ' . htmlspecialchars($pluginSlug) . ' in locale ' . htmlspecialchars($locale) . ' -->';
        return LabPress_Hooks::applyFilters('translate', $key, $key, $locale, $module);
    }

    // 4. 自动加载所有已启用插件的语言包（仅一次）
    if (!$pluginsLoaded) {
        $activePlugins = function_exists('getActivePlugins') ? getActivePlugins() : [];
        foreach ($activePlugins as $plugin) {
            $pluginSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $plugin);
            $pluginLangFile = __DIR__ . "/../plugins/{$pluginSlug}/languages/{$locale}.php";
            if (file_exists($pluginLangFile)) {
                $lang = array_merge($lang, include $pluginLangFile);
            }
        }
        $pluginsLoaded = true;
    }

    // 5. 从合并后的全局数组中查找翻译
    $value = $lang;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return LabPress_Hooks::applyFilters('translate', $key, $key, $locale, $module);
        }
        $value = $value[$segment];
    }

    return LabPress_Hooks::applyFilters('translate', $value, $key, $locale, $module);
}

/**
 * 翻译并直接输出（等价于 echo __($key, $module)）
 */
function _e($key, $module = 'common') {
    echo __($key, $module);
}

/**
 * 获取所有可用语言包
 * @return array
 */
function getAvailableLanguages() {
    $baseDir = __DIR__ . '/../languages/';
    $languages = [];
    if (!is_dir($baseDir)) return $languages;

    $dirs = scandir($baseDir);
    foreach ($dirs as $dir) {
        if ($dir === '.' || $dir === '..' || !is_dir($baseDir . $dir)) continue;
        $configFile = $baseDir . $dir . '/lang.config';
        if (file_exists($configFile)) {
            $config = include $configFile;
            $config['locale'] = $dir; // 确保 locale 与目录名一致
            $languages[] = $config;
        }
    }
    return $languages;
}

/**
 * 检查用户是否正在使用默认密码
 * @param string $username
 * @return bool
 */
function isUsingDefaultPassword($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $hash = $stmt->fetchColumn();
    // 默认密码常量，以后可通过配置修改
    $defaultPassword = 'lab123456';
    return $hash && password_verify($defaultPassword, $hash);
}

//=================================================
//在此处上方修改
//=================================================
// 初始化插件系统
loadPlugins();
LabPress_Hooks::doAction('init');
