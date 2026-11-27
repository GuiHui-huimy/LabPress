<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 登录验证
if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

lp_guard_write_request();

$input = json_decode(file_get_contents('php://input'), true);
$type = $input['type'] ?? '';
$data = $input['data'] ?? null;
$action = $input['action'] ?? 'save'; // save / delete

// 权限映射
$permMap = [
    'publications' => 'publications',
    'slides'       => 'slides',
    'tools'        => 'tools',
    'tool-detail'  => 'tools',
    'tool-versions'=> 'tools',
    'projects'     => 'tools',
    'project-categories' => 'tools',
    'news'         => 'publications',
    'users'        => 'users',
    'site-config'  => 'all',
    'nav-menus'    => 'all',
];

$required = $permMap[$type] ?? '';
if ($required && !hasPermission($required)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '无权限']);
    exit;
}

// ========== 通用保存前钩子（允许插件修改数据） ==========
$data = LabPress_Hooks::applyFilters('admin_prepare_data', $data, $type, $action);
$data = LabPress_Hooks::applyFilters("admin_prepare_{$type}", $data, $action);

try {
    // ========== 通用动作：操作开始 ==========
    LabPress_Hooks::doAction("admin_before_{$action}", $type, $data);
    LabPress_Hooks::doAction("admin_before_{$type}_{$action}", $data);

    switch ($type) {
        // ========== 论文管理 ==========
        case 'publications':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM publications WHERE id = ?");
                $stmt->execute([$data['id']]);
            } else {
                if (!empty($data['id'])) {
                    $stmt = $pdo->prepare("UPDATE publications SET title=?, authors=?, journal=?, link=?, sort_order=? WHERE id=?");
                    $stmt->execute([$data['title'], $data['authors'], $data['journal'], $data['link'], $data['sort_order']??0, $data['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO publications (title, authors, journal, link, sort_order) VALUES (?,?,?,?,?)");
                    $stmt->execute([$data['title'], $data['authors'], $data['journal'], $data['link'], $data['sort_order']??0]);
                }
            }
            break;

        // ========== 轮播图管理 ==========
        case 'slides':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM slides WHERE id = ?");
                $stmt->execute([$data['id']]);
            } else {
                if (!empty($data['id'])) {
                    $stmt = $pdo->prepare("UPDATE slides SET image=?, title=?, description=?, sort_order=? WHERE id=?");
                    $stmt->execute([$data['image'], $data['title'], $data['description'], $data['sort_order']??0, $data['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO slides (image, title, description, sort_order) VALUES (?,?,?,?)");
                    $stmt->execute([$data['image'], $data['title'], $data['description'], $data['sort_order']??0]);
                }
            }
            break;

        // ========== 工具列表管理 ==========
        case 'tools':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM tools WHERE name = ?");
                $stmt->execute([$data['name']]);
            } else {
                $existing = $pdo->prepare("SELECT id FROM tools WHERE name = ?");
                $existing->execute([$data['name']]);
                if ($existing->fetch()) {
                    $stmt = $pdo->prepare("UPDATE tools SET type=?, icon=?, description=?, features=?, github=?, website=?, detail_link=?, sort_order=? WHERE name=?");
                    $stmt->execute([$data['type'], $data['icon'], $data['description'], $data['features'], $data['github'], $data['website'], $data['detail_link'], $data['sort_order']??0, $data['name']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tools (name, type, icon, description, features, github, website, detail_link, sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$data['name'], $data['type'], $data['icon'], $data['description'], $data['features'], $data['github'], $data['website'], $data['detail_link'], $data['sort_order']??0]);
                }
            }
            break;

        // ========== 工具详情管理 ==========
        case 'tool-detail':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM tool_details WHERE tool_name = ?");
                $stmt->execute([$data['tool_name']]);
            } else {
                $existing = $pdo->prepare("SELECT id FROM tool_details WHERE tool_name = ?");
                $existing->execute([$data['tool_name']]);
                if ($existing->fetch()) {
                    $stmt = $pdo->prepare("UPDATE tool_details SET short_description=?, detailed_description=?, version=?, language=?, license=?, update_date=?, github=?, document=?, homepage=?, image=?, screenshot=?, citation_title=?, citation_url=? WHERE tool_name=?");
                    $stmt->execute([
                        $data['short_description'], $data['detailed_description'], $data['version'],
                        $data['language'], $data['license'], $data['update_date'] ?? null,
                        $data['github'], $data['document'], $data['homepage'],
                        $data['image'], $data['screenshot'],
                        $data['citation_title'], $data['citation_url'],
                        $data['tool_name']
                    ]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tool_details (tool_name, short_description, detailed_description, version, language, license, update_date, github, document, homepage, image, screenshot, citation_title, citation_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([
                        $data['tool_name'], $data['short_description'], $data['detailed_description'],
                        $data['version'], $data['language'], $data['license'],
                        $data['update_date'] ?? null, $data['github'], $data['document'],
                        $data['homepage'], $data['image'], $data['screenshot'],
                        $data['citation_title'], $data['citation_url']
                    ]);
                }
            }
            break;

        // ========== 版本管理 ==========
        case 'tool-versions':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM tool_versions WHERE id = ?");
                $stmt->execute([$data['id']]);
            } else {
                if (!empty($data['id'])) {
                    $stmt = $pdo->prepare("UPDATE tool_versions SET version=?, date=?, changes=?, downloads=? WHERE id=?");
                    $stmt->execute([$data['version'], $data['date'], json_encode($data['changes']), json_encode($data['downloads']), $data['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO tool_versions (tool_name, version, date, changes, downloads) VALUES (?,?,?,?,?)");
                    $stmt->execute([$data['tool_name'], $data['version'], $data['date'], json_encode($data['changes']), json_encode($data['downloads'])]);
                }
            }
            break;

        // ========== 新闻管理 ==========
        case 'news':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
                $stmt->execute([$data['id']]);
            } else {
                if (!empty($data['id'])) {
                    $stmt = $pdo->prepare("UPDATE news SET title=?, summary=?, content=?, image=?, author=?, date=?, sort_order=? WHERE id=?");
                    $stmt->execute([$data['title'], $data['summary'], $data['content'] ?? '', $data['image'], $data['author'], $data['date'], $data['sort_order']??0, $data['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO news (title, summary, content, image, author, date, sort_order) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$data['title'], $data['summary'], $data['content'] ?? '', $data['image'], $data['author'], $data['date'], $data['sort_order']??0]);
                }
            }
            break;
            
        case 'site-config':
            if (!is_array($data)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => '配置数据格式错误']);
                exit;
            }
            $invalidKeys = array_values(array_filter(array_keys($data), static fn($key) => !\LabPress\SiteConfig::isAllowed((string)$key)));
            if ($invalidKeys !== []) {
                http_response_code(422);
                echo json_encode(['success' => false, 'error' => '不支持的配置键', 'invalid_keys' => $invalidKeys]);
                exit;
            }
            foreach ($data as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO site_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
                $stmt->execute([$key, $value]);
            }
            break;

        case 'nav-menus':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM nav_menu WHERE id = ?");
                $stmt->execute([$data['id']]);
            } else {
                $title = $data['title'] ?? '';
                $url = $data['url'] ?? '';
                $target = $data['target'] ?? '_self';
                $sort_order = (int)($data['sort_order'] ?? 0);
                $is_active = (int)($data['is_active'] ?? 1);
                $location = $data['location'] ?? 'header';
                if (!empty($data['id'])) {
                    $stmt = $pdo->prepare("UPDATE nav_menu SET title=?, url=?, target=?, sort_order=?, is_active=?, location=? WHERE id=?");
                    $stmt->execute([$title, $url, $target, $sort_order, $is_active, $location, $data['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO nav_menu (title, url, target, sort_order, is_active, location) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$title, $url, $target, $sort_order, $is_active, $location]);
                }
            }
            break;

        case 'users':
            echo json_encode(['success' => false, 'error' => '请使用专门的用户接口']);
            exit;
        
        case 'project-categories':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM project_categories WHERE slug = ?");
                $stmt->execute([$data['slug']]);
            } else {
                if (!empty($data['old_slug'])) {
                    $stmt = $pdo->prepare("UPDATE project_categories SET slug=?, name=?, description=?, sort_order=? WHERE slug=?");
                    $stmt->execute([$data['slug'], $data['name'], $data['description'], (int)$data['sort_order'], $data['old_slug']]);
                } else {
                    $existing = $pdo->prepare("SELECT slug FROM project_categories WHERE slug = ?");
                    $existing->execute([$data['slug']]);
                    if ($existing->fetch()) {
                        echo json_encode(['success' => false, 'error' => '标识已存在']);
                        exit;
                    }
                    $stmt = $pdo->prepare("INSERT INTO project_categories (slug, name, description, sort_order) VALUES (?,?,?,?)");
                    $stmt->execute([$data['slug'], $data['name'], $data['description'], (int)$data['sort_order']]);
                }
            }
            break;
        case 'projects':
            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                $stmt->execute([$data['id']]);
            } else {
                $title = $data['title'] ?? '';
                $summary = $data['summary'] ?? '';
                $content = $data['content'] ?? '';
                $image = $data['image'] ?? null;
                $category_slug = $data['category_slug'] ?? '';
                $featured = isset($data['featured']) ? (int)$data['featured'] : 1;
                $github_url = $data['github_url'] ?? null;
                $doc_url = $data['doc_url'] ?? null;
                $external_url = $data['external_url'] ?? null;
                $sort_order = (int)($data['sort_order'] ?? 0);

                if (!empty($data['id'])) {
                    $stmt = $pdo->prepare("UPDATE projects SET title=?, summary=?, content=?, image=?, category_slug=?, featured=?, github_url=?, doc_url=?, external_url=?, sort_order=? WHERE id=?");
                    $stmt->execute([$title, $summary, $content, $image, $category_slug, $featured, $github_url, $doc_url, $external_url, $sort_order, $data['id']]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO projects (title, summary, content, image, category_slug, featured, github_url, doc_url, external_url, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$title, $summary, $content, $image, $category_slug, $featured, $github_url, $doc_url, $external_url, $sort_order]);
                }
            }
            break;

        case 'plugins':
            if (!hasPermission('all')) {
                echo json_encode(['success' => false, 'error' => '无权限']);
                exit;
            }
            $action = $input['action'] ?? '';
            $slug = $input['data']['slug'] ?? '';

            // 插件操作前通用钩子
            LabPress_Hooks::doAction("admin_before_plugins_{$action}", $slug);

            if ($action === 'sync') {
                syncPlugins();
                echo json_encode(['success' => true, 'message' => '插件扫描完成']);
                // 同步后钩子
                LabPress_Hooks::doAction("admin_after_plugins_{$action}", $slug);
                exit;
            } elseif ($action === 'activate') {
                // v1.2.0：统一走 Plugin Manager，生命周期幂等，异常隔离。
                $ok = \LabPress\Plugin\Manager::activate($slug);
                if (!$ok) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => '插件不存在']);
                    exit;
                }
                echo json_encode(['success' => true]);
            } elseif ($action === 'deactivate') {
                // 停用只触发 plugin_deactivation，不再调用卸载逻辑。
                $ok = \LabPress\Plugin\Manager::deactivate($slug);
                if (!$ok) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => '插件不存在']);
                    exit;
                }
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '无效操作']);
            }

            // 插件操作后通用钩子
            LabPress_Hooks::doAction("admin_after_plugins_{$action}", $slug);
            exit;
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '未知类型']);
            exit;
    }

    // ========== 通用操作成功后的动作 ==========
    LabPress_Hooks::doAction("admin_after_{$action}", $type, $data, true);
    LabPress_Hooks::doAction("admin_after_{$type}_{$action}", $data, true);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    // ========== 操作失败后的动作 ==========
    LabPress_Hooks::doAction("admin_after_{$action}", $type, $data, false);
    LabPress_Hooks::doAction("admin_after_{$type}_{$action}", $data, false);

    // 详情只写日志，不返回给客户端
    \LabPress\Support\Logger::exception($e, ['stage' => 'api_save', 'type' => $type, 'action' => $action]);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal error']);
}