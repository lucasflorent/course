<?php

declare(strict_types=1);

namespace App\Config;

use PDO;

/**
 * Fabrique de connexion PDO a MariaDB (singleton).
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect(Env::get('DB_NAME'));
        }

        return self::$pdo;
    }

    /**
     * Connexion au serveur MariaDB sans selectionner de base, utilisee
     * uniquement par scripts/migrate.php pour pouvoir faire CREATE DATABASE.
     */
    public static function pdoSansBase(): PDO
    {
        return self::connect(null);
    }

    private static function connect(?string $dbName): PDO
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};charset={$charset}";
        if ($dbName !== null) {
            $dsn .= ";dbname={$dbName}";
        }

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
