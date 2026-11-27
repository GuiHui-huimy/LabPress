<?php
declare(strict_types=1);


$tableExists = static function (PDO $pdo, string $table): bool {
    $stmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($table));
    return (bool)$stmt->fetchColumn();
};

return [
    'version' => '20261125_0003_categories_legacy',
    'description' => '将未再使用的 categories 表改名为 categories_legacy，保留回滚能力',
    'up' => static function (PDO $pdo) use ($tableExists): void {
        if ($tableExists($pdo, 'categories') && !$tableExists($pdo, 'categories_legacy')) {
            $pdo->exec('RENAME TABLE `categories` TO `categories_legacy`');
        }
    },
    'down' => static function (PDO $pdo) use ($tableExists): void {
        if ($tableExists($pdo, 'categories_legacy') && !$tableExists($pdo, 'categories')) {
            $pdo->exec('RENAME TABLE `categories_legacy` TO `categories`');
        }
    },
];
