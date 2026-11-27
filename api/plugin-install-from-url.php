<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');
lp_guard_write_request();

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '未登录']);
    exit;
}

if (!hasPermission('all')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '无权限']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$url = trim((string)($input['download_url'] ?? ''));

if ($url === '') {
    echo json_encode(['success' => false, 'error' => '下载地址不能为空']);
    exit;
}

// 仅允许 https，避免明文传输与本地文件读取
if (!preg_match('#^https://#i', $url)) {
    echo json_encode(['success' => false, 'error' => '仅支持 https 下载地址']);
    exit;
}

$host = parse_url($url, PHP_URL_HOST);
if (!$host) {
    echo json_encode(['success' => false, 'error' => '下载地址不合法']);
    exit;
}

// 解析域名，拒绝内网/回环地址（基础 SSRF 防护）
$ip = gethostbyname($host);
$isPublicIp = filter_var(
    $ip,
    FILTER_VALIDATE_IP,
    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
);
if ($isPublicIp === false) {
    echo json_encode(['success' => false, 'error' => '不允许从内网地址下载插件']);
    exit;
}

// 下载（10 秒超时，最多 3 次跳转）
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'follow_location' => 1,
        'max_redirects' => 3,
        'user_agent' => 'LabPress-Plugin-Installer/1.1.1',
    ],
]);
$zipContent = @file_get_contents($url, false, $context);
if ($zipContent === false) {
    echo json_encode(['success' => false, 'error' => '无法下载插件']);
    exit;
}
if (strlen($zipContent) > 20 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => '插件包不能超过 20MB']);
    exit;
}

$tempName = tempnam(sys_get_temp_dir(), 'plugin_');
if ($tempName === false) {
    echo json_encode(['success' => false, 'error' => '临时目录不可用']);
    exit;
}
file_put_contents($tempName, $zipContent);

$result = lp_install_plugin_zip($tempName);
@unlink($tempName);

if (empty($result['success'])) {
    echo json_encode(['success' => false, 'error' => $result['error'] ?? '安装失败']);
    exit;
}

\LabPress\Plugin\Manager::discover();

echo json_encode([
    'success' => true,
    'slug' => $result['slug'],
    'message' => '安装成功',
    'plugin_info' => $result['plugin_info'],
]);
