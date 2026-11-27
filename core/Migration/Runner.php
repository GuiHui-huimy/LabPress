<?php
declare(strict_types=1);

namespace LabPress\Migration;

use PDO;
use Throwable;

/**
 * 极简迁移执行器。
 *
 * 迁移文件返回：
 * [
 *   'version' => '20261125_0001',
 *   'description' => '...',
 *   'up' => function(PDO $pdo) { ... },
 *   'down' => function(PDO $pdo) { ... },
 * ]
 *
 * 注意：MySQL/MariaDB 的 DDL 会隐式提交，不能假装 DDL 在事务里可回滚。
 * 这里只保证“失败不写 schema_version”，down 由迁移自己负责可逆。
 */
final class Runner
{
    private PDO $pdo;
    private string $dir;
    private string $table = 'schema_version';

    public function __construct(PDO $pdo, string $dir)
    {
        $this->pdo = $pdo;
        $this->dir = rtrim($dir, '/');
    }

    public function up(): array
    {
        $this->ensureTable();
        $applied = $this->appliedVersions();
        $executed = [];
        foreach ($this->loadMigrations() as $migration) {
            if (in_array($migration['version'], $applied, true)) {
                continue;
            }
            try {
                ($migration['up'])($this->pdo);
                $this->record($migration['version'], $migration['description']);
                $executed[] = $migration['version'];
            } catch (Throwable $e) {
                throw new \RuntimeException('Migration failed: ' . $migration['version'] . ' - ' . $e->getMessage(), 0, $e);
            }
        }
        return $executed;
    }

    public function down(int $steps = 1): array
    {
        $this->ensureTable();
        $rows = $this->pdo->query('SELECT version, description FROM `' . $this->table . '` ORDER BY version DESC')->fetchAll(PDO::FETCH_ASSOC);
        $executed = [];
        foreach ($rows as $row) {
            if ($steps <= 0) {
                break;
            }
            $migration = $this->findMigration((string)$row['version']);
            if ($migration === null || !isset($migration['down'])) {
                throw new \RuntimeException('Migration has no down(): ' . $row['version']);
            }
            ($migration['down'])($this->pdo);
            $this->pdo->prepare('DELETE FROM `' . $this->table . '` WHERE version = ?')->execute([$row['version']]);
            $executed[] = $row['version'];
            $steps--;
        }
        return $executed;
    }

    public function status(): array
    {
        $this->ensureTable();
        $applied = $this->appliedVersions();
        $result = [];
        foreach ($this->loadMigrations() as $migration) {
            $result[] = [
                'version' => $migration['version'],
                'description' => $migration['description'],
                'applied' => in_array($migration['version'], $applied, true),
            ];
        }
        return $result;
    }

    public function currentVersion(): ?string
    {
        $this->ensureTable();
        $version = $this->pdo->query('SELECT version FROM `' . $this->table . '` ORDER BY version DESC LIMIT 1')->fetchColumn();
        return $version === false ? null : (string)$version;
    }

    private function ensureTable(): void
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `version` VARCHAR(40) NOT NULL PRIMARY KEY,
            `description` VARCHAR(255) NOT NULL DEFAULT '',
            `applied_at` DATETIME NOT NULL,
            `batch` INT NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * @return array<int,array{version:string,description:string,up:callable,down?:callable}>
     */
    private function loadMigrations(): array
    {
        $files = glob($this->dir . '/*.php') ?: [];
        $items = [];
        foreach ($files as $file) {
            $migration = require $file;
            if (!is_array($migration) || empty($migration['version']) || !isset($migration['up'])) {
                continue;
            }
            $items[] = [
                'version' => (string)$migration['version'],
                'description' => (string)($migration['description'] ?? ''),
                'up' => $migration['up'],
                'down' => $migration['down'] ?? null,
            ];
        }
        usort($items, static fn(array $a, array $b): int => strcmp($a['version'], $b['version']));
        return $items;
    }

    private function appliedVersions(): array
    {
        return $this->pdo->query('SELECT version FROM `' . $this->table . '`')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function record(string $version, string $description): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO `' . $this->table . '` (version, description, applied_at, batch) VALUES (?, ?, ?, 1)');
        $stmt->execute([$version, $description, date('Y-m-d H:i:s')]);
    }

    private function findMigration(string $version): ?array
    {
        foreach ($this->loadMigrations() as $migration) {
            if ($migration['version'] === $version) {
                return $migration;
            }
        }
        return null;
    }
}
