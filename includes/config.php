<?php
// Start Session
session_start();

// Database Connect Config ( You shoud input these infomations as your own MySql Server Infomations)
define('DB_HOST', 'localhost'); //Example : localhost
define('DB_NAME', 'labpage');   //Example : labpage
define('DB_USER', 'your user name');// Your Database user name
define('DB_PASS', 'Password');  // Your Database user password

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database Connect Error: " . $e->getMessage());
}

// TimeZone
date_default_timezone_set('Asia/Shanghai');