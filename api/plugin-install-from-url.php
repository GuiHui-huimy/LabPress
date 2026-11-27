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
$sha256 = strtolower(trim((string)($input['sha256'] ?? '')));

// 默认关闭，必须由管理员显式开启并配置允许的下载主机。
if (!in_array(LabPress::config('plugin_remote_install_enabled', '0'), ['1', 1, true, 'true'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '远程安装未启用']);
    exit;
}

$allowedHosts = [];
foreach (preg_split('/[\s,]+/', (string)LabPress::config('plugin_remote_install_hosts', '')) as $host) {
    $host = strtolower(trim($host));
    if ($host !== '') {
        $allowedHosts[] = $host;
    }
}
if ($allowedHosts === []) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => '未配置远程安装允许主机']);
    exit;
}

if ($url === '' || !preg_match('#^https://#i', $url)) {
    echo json_encode(['success' => false, 'error' => '仅支持 https 下载地址']);
    exit;
}
if (!preg_match('/^[a-f0-9]{64}$/', $sha256)) {
    echo json_encode(['success' => false, 'error' => '必须提供合法的 sha256 校验值']);
    exit;
}

/**
 * @param string $url
 * @param array<int,string> $allowedHosts
 * @return array{ok:bool,error?:string,host?:string,ip?:string,url?:string}
 */
function lp_remote_install_validate_url(string $url, array $allowedHosts): array
{
    $parts = parse_url($url);
    if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https') {
        return ['ok' => false, 'error' => '仅支持 https 下载地址'];
    }
    if (isset($parts['user']) || isset($parts['pass'])) {
        return ['ok' => false, 'error' => '下载地址不能包含认证信息'];
    }
    $host = strtolower((string)($parts['host'] ?? ''));
    $port = (int)($parts['port'] ?? 443);
    if ($host === '' || $port !== 443) {
        return ['ok' => false, 'error' => '下载地址必须使用 443 端口'];
    }

    $allowed = false;
    foreach ($allowedHosts as $entry) {
        if (str_starts_with($entry, '*.')) {
            $suffix = substr($entry, 2);
            if ($host === $suffix || str_ends_with($host, '.' . $suffix)) {
                $allowed = true;
                break;
            }
        } elseif ($host === $entry) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        return ['ok' => false, 'error' => '下载主机不在允许列表'];
    }

    $ips = gethostbynamel($host) ?: [];
    $aaaa = function_exists('dns_get_record') ? (dns_get_record($host, DNS_AAAA) ?: []) : [];
    foreach ($aaaa as $record) {
        if (!empty($record['ipv6'])) {
            $ips[] = (string)$record['ipv6'];
        }
    }
    if ($ips === []) {
        return ['ok' => false, 'error' => '无法解析下载主机'];
    }
    foreach ($ips as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['ok' => false, 'error' => '不允许从内网或保留地址下载插件'];
        }
    }

    return ['ok' => true, 'host' => $host, 'ip' => (string)$ips[0], 'url' => $url];
}

/**
 * Download a ZIP manually, validating every redirect hop and capping bytes.
 *
 * @param array<int,string> $allowedHosts
 * @return array{ok:bool,error?:string,body?:string}
 */
function lp_remote_install_fetch(string $url, array $allowedHosts): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => '服务器缺少 curl 扩展'];
    }
    $maxBytes = 20 * 1024 * 1024;
    $current = $url;
    for ($hop = 0; $hop <= 3; $hop++) {
        $check = lp_remote_install_validate_url($current, $allowedHosts);
        if (empty($check['ok'])) {
            return ['ok' => false, 'error' => (string)($check['error'] ?? '下载地址不合法')];
        }

        $body = '';
        $tooLarge = false;
        $headers = [];
        $ch = curl_init($current);
        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_USERAGENT => 'LabPress-Plugin-Installer/1.3.0',
            CURLOPT_HTTPHEADER => ['Accept: application/zip, application/octet-stream'],
            CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use (&$body, &$tooLarge, $maxBytes): int {
                if (strlen($body) + strlen($chunk) > $maxBytes) {
                    $tooLarge = true;
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$headers): int {
                $trimmed = trim($line);
                if (stripos($trimmed, 'HTTP/') === 0) {
                    $headers = [];
                } elseif (str_contains($trimmed, ':')) {
                    [$name, $value] = explode(':', $trimmed, 2);
                    $headers[strtolower(trim($name))] = trim($value);
                }
                return strlen($line);
            },
        ];
        if (str_contains($check['ip'], ':')) {
            $options[CURLOPT_RESOLVE] = [$check['host'] . ':443:[' . $check['ip'] . ']'];
        } else {
            $options[CURLOPT_RESOLVE] = [$check['host'] . ':443:' . $check['ip']];
        }
        curl_setopt_array($ch, $options);
        $ok = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($tooLarge) {
            return ['ok' => false, 'error' => '插件包不能超过 20MB'];
        }
        if ($ok === false) {
            return ['ok' => false, 'error' => '无法下载插件：' . ($error !== '' ? $error : '网络错误')];
        }
        if ($status >= 300 && $status < 400) {
            $location = (string)($headers['location'] ?? '');
            if ($location === '') {
                return ['ok' => false, 'error' => '下载重定向缺少 Location'];
            }
            if (!preg_match('#^https://#i', $location)) {
                $base = parse_url($current);
                $location = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '') . '/' . ltrim($location, '/');
            }
            $current = $location;
            continue;
        }
        if ($status !== 200) {
            return ['ok' => false, 'error' => '下载失败，HTTP ' . $status];
        }
        if ($body === '') {
            return ['ok' => false, 'error' => '下载内容为空'];
        }
        return ['ok' => true, 'body' => $body];
    }
    return ['ok' => false, 'error' => '重定向次数超过限制'];
}

$fetch = lp_remote_install_fetch($url, $allowedHosts);
if (empty($fetch['ok'])) {
    echo json_encode(['success' => false, 'error' => $fetch['error'] ?? '无法下载插件']);
    exit;
}
$zipContent = (string)$fetch['body'];
if (strlen($zipContent) > 20 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => '插件包不能超过 20MB']);
    exit;
}
if (!hash_equals($sha256, hash('sha256', $zipContent))) {
    echo json_encode(['success' => false, 'error' => 'sha256 校验失败']);
    exit;
}

$tmpDir = dirname(__DIR__) . '/storage/tmp';
if (!is_dir($tmpDir)) {
    @mkdir($tmpDir, 0775, true);
}
$tempName = tempnam($tmpDir, 'plugin_remote_');
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
