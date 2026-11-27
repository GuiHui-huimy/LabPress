<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../includes/config.php';

$command = $argv[1] ?? 'status';
$steps = isset($argv[2]) ? max(1, (int)$argv[2]) : 1;

$runner = new \LabPress\Migration\Runner($pdo, LABPRESS_ROOT . '/migrations');

try {
    if ($command === 'status') {
        $rows = $runner->status();
        if ($rows === []) {
            echo "No migrations found.\n";
            exit(0);
        }
        printf("%-8s %-42s %s\n", 'STATE', 'MIGRATION', 'DESCRIPTION');
        foreach ($rows as $row) {
            printf(
                "%-8s %-42s %s\n",
                $row['applied'] ? 'applied' : 'pending',
                $row['version'],
                $row['description']
            );
        }
        echo "\nCurrent schema_version: " . ($runner->currentVersion() ?? '(none)') . "\n";
        exit(0);
    }

    if ($command === 'up') {
        $runner->up();
        echo "Migration up completed. schema_version: " . ($runner->currentVersion() ?? '(none)') . "\n";
        exit(0);
    }

    if ($command === 'down') {
        $runner->down($steps);
        echo "Migration down completed. schema_version: " . ($runner->currentVersion() ?? '(none)') . "\n";
        exit(0);
    }

    fwrite(STDERR, "Usage: php bin/migrate.php [status|up|down] [steps]\n");
    exit(1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
