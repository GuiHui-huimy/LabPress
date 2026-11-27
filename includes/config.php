<?php

/**
 * v1.2.0 兼容入口。
 *
 * 新部署请复制 storage/config/site.example.php 为 storage/config/site.php；
 * 下面的 DB_* 常量只为旧部署保留回退，不再在这里 session_start/new PDO。
 * 真正的唯一引导在 includes/bootstrap.php。
 */

if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'labpress');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'labpress_user');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'Password');
}

require_once __DIR__ . '/bootstrap.php';
