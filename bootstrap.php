<?php
declare(strict_types=1);

/**
 * 根目录兼容入口。实际引导在 includes/bootstrap.php。
 * 入口文件继续 require includes/config.php 即可，旧的部署方式不需要一次性全改。
 */
require_once __DIR__ . '/includes/config.php';
