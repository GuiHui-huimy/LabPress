<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/labpress.php';

// 版本号（发布前由维护者更新；后台页脚与登录页展示用）
if (!defined('LABPRESS_VERSION')) {
    define('LABPRESS_VERSION', '1.1.1');
}

// ==================== 输出与请求助手（v1.1.1） ====================

/**
 * HTML 转义输出助手
 */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * 会话 Cookie 加固：config.php 负责 session_start，这里补 HttpOnly / SameSite
 */
function lp_harden_session_cookie() {
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent() || session_id() === '') {
        return;
    }
    setcookie(session_name(), session_id(), [
        'expires'  => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

lp_harden_session_cookie();

/**
 * 生成 <html> 标签的 lang / dir 属性（跟随当前 locale）
 */
function lp_html_lang_attr() {
    $locale = getLocale();
    $lang = str_replace('_', '-', $locale);
    $dir = 'ltr';
    foreach (getAvailableLanguages() as $item) {
        if (($item['locale'] ?? '') === $locale && !empty($item['direction'])) {
            $dir = $item['direction'];
            break;
        }
    }
    return 'lang="' . e($lang) . '" dir="' . e($dir) . '"';
}

/**
 * 在当前 URL 上合并查询参数（保留 id / category / page 等）
 */
function lp_url_with_params(array $overrides = []) {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = strtok($uri, '?');
    if ($path === false || $path === '') {
        $path = '/';
    }
    $query = [];
    $qs = parse_url($uri, PHP_URL_QUERY);
    if ($qs) {
        parse_str($qs, $query);
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    $qs = http_build_query($query);
    return $path . ($qs !== '' ? '?' . $qs : '');
}

/**
 * 语言切换 URL：保留当前路径与查询串，只替换 lang
 */
function lp_url_for_lang($locale) {
    return lp_url_with_params(['lang' => $locale]);
}

/**
 * 是否为写请求
 */
function lp_is_write_request() {
    return in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'DELETE', 'PATCH'], true);
}

/**
 * 判断请求是否来自同源（Origin / Referer 任一存在则必须匹配）
 */
function lp_request_is_same_origin() {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host === '') {
        return true;
    }
    $hostNoPort = preg_replace('/:\d+$/', '', $host);
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $key) {
        if (empty($_SERVER[$key])) {
            continue;
        }
        $originHost = parse_url($_SERVER[$key], PHP_URL_HOST);
        if (!$originHost) {
            continue;
        }
        if (strcasecmp($originHost, $hostNoPort) !== 0) {
            return false;
        }
    }
    return true;
}

/**
 * 当前会话的 CSRF token
 */
function lp_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 校验 CSRF token
 */
function lp_csrf_verify($token) {
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 写操作守卫：带 token 则必须匹配；不带 token 则必须同源。
 * 校验失败直接输出 403 并终止请求。
 */
function lp_guard_write_request() {
    if (!lp_is_write_request()) {
        return true;
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);
    if ($token !== null) {
        if (lp_csrf_verify($token)) {
            return true;
        }
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    if (!lp_request_is_same_origin()) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid request origin']);
        exit;
    }
    return true;
}

/**
 * 递归删除目录（供插件安装失败时回滚使用）
 */
function lp_remove_dir($dir) {
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            lp_remove_dir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

/**
 * 安全解压并安装插件 ZIP（v1.1.1 应急校验版）
 * 返回 ['success' => bool, 'error' => string, 'slug' => string, 'plugin_info' => array]
 */
function lp_install_plugin_zip($zipPath) {
    $pluginsDir = realpath(__DIR__ . '/../plugins');
    if ($pluginsDir === false) {
        return ['success' => false, 'error' => '插件目录不可用'];
    }

    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        return ['success' => false, 'error' => '无法打开 ZIP 文件'];
    }

    if ($zip->numFiles > 2000) {
        $zip->close();
        return ['success' => false, 'error' => '压缩包条目过多'];
    }

    $root = null;
    $totalSize = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if ($name === false) {
            continue;
        }
        $norm = ltrim(str_replace('\\', '/', $name), '/');
        if (preg_match('#(^|/)\.\.(/|$)#', $norm) || preg_match('#^[A-Za-z]:#', $norm)) {
            $zip->close();
            return ['success' => false, 'error' => '压缩包包含非法路径'];
        }
        $parts = explode('/', $norm);
        if ($root === null) {
            $root = $parts[0];
        }
        if ($parts[0] !== $root) {
            $zip->close();
            return ['success' => false, 'error' => '压缩包必须只包含一个根目录'];
        }
        $stat = $zip->statIndex($i);
        $totalSize += (int)($stat['size'] ?? 0);
        if ($totalSize > 50 * 1024 * 1024) {
            $zip->close();
            return ['success' => false, 'error' => '压缩包解压后体积超过限制'];
        }
    }

    if (!$root || !preg_match('/^[A-Za-z0-9][A-Za-z0-9_.-]{0,63}$/', $root)) {
        $zip->close();
        return ['success' => false, 'error' => '插件根目录名不合法'];
    }

    $targetDir = $pluginsDir . DIRECTORY_SEPARATOR . $root;
    if (file_exists($targetDir)) {
        $zip->close();
        return ['success' => false, 'error' => '插件目录已存在，请先删除再安装'];
    }

    if (!$zip->extractTo($pluginsDir)) {
        $zip->close();
        return ['success' => false, 'error' => '解压失败'];
    }
    $zip->close();

    $pluginFile = $targetDir . '/plugin.php';
    if (!file_exists($pluginFile)) {
        lp_remove_dir($targetDir);
        return ['success' => false, 'error' => '压缩包中未找到 plugin.php 文件'];
    }

    $pluginInfo = [
        'slug' => $root,
        'name' => $root,
        'description' => '',
        'version' => '',
        'author' => '',
    ];
    $content = (string)file_get_contents($pluginFile);
    if (preg_match('/Plugin Name:\s*(.+)/i', $content, $m)) $pluginInfo['name'] = trim($m[1]);
    if (preg_match('/Description:\s*(.+)/i', $content, $m)) $pluginInfo['description'] = trim($m[1]);
    if (preg_match('/Version:\s*(.+)/i', $content, $m)) $pluginInfo['version'] = trim($m[1]);
    if (preg_match('/Author:\s*(.+)/i', $content, $m)) $pluginInfo['author'] = trim($m[1]);

    return ['success' => true, 'slug' => $root, 'plugin_info' => $pluginInfo];
}

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
 * 前台优先级：URL 参数 > Cookie > 系统配置
 * 后台：默认站点语言，允许 admin_locale 过滤器接管（插件可选）
 */
function getLocale() {
    static $locale = null;
    if ($locale === null) {
        $available = getAvailableLanguages();
        $availLocales = array_column($available, 'locale');
        $defaultLocale = LabPress::config('language', 'zh_CN');

        // 后台默认跟随站点语言；插件可通过 admin_locale 过滤器提供独立的后台语言
        if (defined('IS_ADMIN') || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) {
            $adminLocale = LabPress_Hooks::applyFilters('admin_locale', $defaultLocale, $availLocales);
            if (!is_string($adminLocale) || !in_array($adminLocale, $availLocales, true)) {
                $adminLocale = $defaultLocale;
            }
            $locale = $adminLocale;
            return $locale;
        }

        // 前台逻辑：URL参数 > Cookie > 系统配置
        if (!empty($_GET['lang']) && in_array($_GET['lang'], $availLocales)) {
            $locale = $_GET['lang'];
            setcookie('lang', $locale, [
                'expires'  => time() + 86400 * 30,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
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

    $locale = getLocale();
    $baseDir = __DIR__ . '/../languages/';

    // 1. 加载核心公共语言包
    if (!isset($loadedModules['common'])) {
        $file = "{$baseDir}{$locale}/common.php";
        if (file_exists($file)) {
            $lang = array_merge($lang, include $file);
        }
        $loadedModules['common'] = true;
    }

    // 2. 加载核心模块语言包（插件模块必须走 plugin: 前缀）
    if ($module !== 'common' && strpos($module, 'plugin:') !== 0 && !isset($loadedModules[$module])) {
        $file = "{$baseDir}{$locale}/{$module}.php";
        if (file_exists($file)) {
            $lang = array_merge($lang, include $file);
        }
        $loadedModules[$module] = true;
    }

    // 3. 显式插件语言包：只读取指定插件，不污染核心语言命名空间
    if (strpos($module, 'plugin:') === 0) {
        $pluginSlug = substr($module, 7);
        $pluginSlug = preg_replace('/[^a-zA-Z0-9_-]/', '', $pluginSlug);
        $pluginLangFile = __DIR__ . "/../plugins/{$pluginSlug}/languages/{$locale}.php";
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
        // 如果插件语言文件不存在，回退到键名（调试信息写入全局变量供开发面板读取）
        if (defined('LABPRESS_DEBUG') && LABPRESS_DEBUG) {
            $GLOBALS['lp_i18n_missing'][] = $pluginLangFile;
        }
        return LabPress_Hooks::applyFilters('translate', $key, $key, $locale, $module);
    }

    // 4. 从核心语言数组中查找翻译
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

// 提前解析语言，保证 ?lang= 的 Cookie 在任何 HTML 输出之前写入
// 必须在 loadPlugins() 之后执行，让插件的 admin_locale 过滤器先注册
if (isset($_GET['lang'])) {
    getLocale();
}

LabPress_Hooks::doAction('init');
