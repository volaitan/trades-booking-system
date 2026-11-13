<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    public static function connect(string $dsn, string $user = '', string $password = ''): PDO
    {
        $pdo = new PDO($dsn, $user ?: null, $password ?: null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        if (self::driver($pdo) === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }
        return $pdo;
    }

    public static function driver(PDO $pdo): string
    {
        return (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /** Create all tables using the schema file for this database driver. */
    public static function migrate(PDO $pdo): void
    {
        $driver = self::driver($pdo) === 'mysql' ? 'mysql' : 'sqlite';
        $sql = (string) file_get_contents(dirname(__DIR__) . "/database/schema.$driver.sql");
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $pdo->exec($statement);
        }
    }
}
