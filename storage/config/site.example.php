<?php
/**
 * LabPress 部署配置示例（v1.2.0）。
 *
 * 复制为 storage/config/site.php 后填写；不要提交真实密码。
 * storage/config/site.php 已在 .gitignore 中忽略。
 */
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'labpress',
        'user' => 'labpress',
        'pass' => 'change-me',
        'charset' => 'utf8mb4',
    ],
    'timezone' => 'Asia/Shanghai',
    'debug' => false,
    'session' => [
        // 留空使用 PHP 默认 PHPSESSID，避免升级后旧登录态全部失效。
        'name' => '',
        'same_site' => 'Lax',
        // null = 根据 HTTPS 自动判断
        'secure' => null,
    ],
];
