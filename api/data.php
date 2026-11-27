<?php
error_reporting(0);
ini_set('display_errors', 0);

// 清空缓冲区
while (ob_get_level()) {
    ob_end_clean();
}

require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$type = $_GET['type'] ?? '';
$name = $_GET['name'] ?? '';
$id   = $_GET['id'] ?? '';

$data = [];

try {
    switch ($type) {
        case 'slides':
            $data = getSlides();
            // 多语言插件过滤器
            $data = LabPress_Hooks::applyFilters('slides_data', $data);
            break;
        case 'publications':
            $data = getPublications();
            break;
        case 'tools':
            $data = getTools();
            break;
        case 'tool-detail':
            if (empty($name)) {
                http_response_code(400);
                $data = ['error' => '缺少工具名称'];
            } else {
                $detail = getToolDetail($name);
                $data = $detail ?: [];
            }
            break;
        case 'tool-versions':
            if (empty($name)) {
                http_response_code(400);
                $data = ['error' => '缺少工具名称'];
            } else {
                $data = getToolVersions($name);
            }
            break;
        case 'news-detail':
            if (empty($id)) {
                http_response_code(400);
                $data = ['error' => '缺少新闻ID'];
            } else {
                $news = getNewsById((int)$id);
                $data = $news ?: [];
            }
            break;
        case 'project-detail':
            if (empty($id)) {
                http_response_code(400);
                $data = ['error' => '缺少项目ID'];
            } else {
                $project = getProjectById((int)$id);
                $data = $project ?: [];
            }
            break;
        default:
            http_response_code(400);
            $data = ['error' => '无效请求类型'];
    }
} catch (Throwable $e) {
    \LabPress\Support\Logger::exception($e, ['stage' => 'api_data']);
    http_response_code(500);
    $data = ['error' => 'Internal error'];
}

ob_start();
echo json_encode($data, JSON_UNESCAPED_UNICODE);
ob_end_flush();
exit;