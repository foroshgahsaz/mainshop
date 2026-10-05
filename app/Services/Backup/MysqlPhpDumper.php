<?php

namespace App\Services\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Dump MySQL/MariaDB via the app's PDO connection when mysqldump is unavailable.
 */
class MysqlPhpDumper
{
    public function dumpConnectionToGzipFile(string $connectionName, string $targetPath): void
    {
        $connection = DB::connection($connectionName);
        $pdo = $connection->getPdo();
        $database = (string) $connection->getDatabaseName();

        if ($database === '') {
            throw new RuntimeException('نام دیتابیس برای بک‌آپ PHP مشخص نیست.');
        }

        $sql = $this->buildSql($connection, $database, $pdo);

        $gz = gzencode($sql, 6);
        if ($gz === false) {
            throw new RuntimeException('فشرده‌سازی بک‌آپ PHP ناموفق بود.');
        }

        if (file_put_contents($targetPath, $gz) === false) {
            throw new RuntimeException('ذخیره فایل بک‌آپ PHP ناموفق بود.');
        }
    }

    protected function buildSql(Connection $connection, string $database, \PDO $pdo): string
    {
        $lines = [
            '-- Shop database backup (PHP/PDO)',
            '-- Generated at: '.now()->toIso8601String(),
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";',
        ];

        $tables = $connection->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? ORDER BY TABLE_NAME',
            [$database, 'BASE TABLE']
        );

        foreach ($tables as $tableRow) {
            $table = (string) ($tableRow->name ?? '');
            if (! $this->isSafeIdentifier($table)) {
                continue;
            }

            $create = $connection->selectOne('SHOW CREATE TABLE `'.$table.'`');
            $createSql = (string) ($create->{'Create Table'} ?? $create->{'Create View'} ?? '');
            if ($createSql === '') {
                continue;
            }

            $lines[] = '';
            $lines[] = 'DROP TABLE IF EXISTS `'.$table.'`;';
            $lines[] = $createSql.';';

            $columnNames = $this->columnNames($connection, $table);
            if ($columnNames === []) {
                continue;
            }

            $columnList = implode(', ', array_map(fn (string $col) => '`'.$col.'`', $columnNames));

            foreach ($connection->cursor('SELECT * FROM `'.$table.'`') as $row) {
                $values = [];
                foreach ($columnNames as $column) {
                    $values[] = $this->quoteValue($row->{$column} ?? null, $pdo);
                }
                $lines[] = 'INSERT INTO `'.$table.'` ('.$columnList.') VALUES ('.implode(', ', $values).');';
            }
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode("\n", $lines);
    }

    /** @return list<string> */
    protected function columnNames(Connection $connection, string $table): array
    {
        $rows = $connection->select(
            'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$connection->getDatabaseName(), $table]
        );

        $names = [];
        foreach ($rows as $row) {
            $name = (string) ($row->name ?? '');
            if ($this->isSafeIdentifier($name)) {
                $names[] = $name;
            }
        }

        return $names;
    }

    protected function isSafeIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    protected function quoteValue(mixed $value, \PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_resource($value)) {
            throw new RuntimeException('ستون BLOB/RESOURCE در بک‌آپ PHP پشتیبانی نمی‌شود.');
        }

        return $pdo->quote((string) $value);
    }
}
