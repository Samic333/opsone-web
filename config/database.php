<?php
/**
 * Database Connection (PDO)
 * Supports MySQL (production) and SQLite (development)
 */

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $driver = env('DB_DRIVER', 'mysql');
            
            if ($driver === 'sqlite') {
                $dbPath = env('DB_DATABASE', 'database/crewassist.sqlite');
                // Resolve relative paths against BASE_PATH
                if (!str_starts_with($dbPath, '/')) {
                    $dbPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__)) . '/' . $dbPath;
                }
                self::$instance = new PDO("sqlite:$dbPath", null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                self::$instance->exec("PRAGMA journal_mode=WAL");
                self::$instance->exec("PRAGMA foreign_keys=ON");
            } else {
                $host     = env('DB_HOST', '127.0.0.1');
                $port     = env('DB_PORT', '3306');
                $database = env('DB_DATABASE', '');
                $username = env('DB_USERNAME', '');
                $password = env('DB_PASSWORD', '');

                if (empty($database) || empty($username)) {
                    throw new \RuntimeException(
                        'Database credentials are not configured. ' .
                        'Set DB_DATABASE, DB_USERNAME, and DB_PASSWORD in your .env file.'
                    );
                }

                $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
                self::$instance = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): \PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetch(string $sql, array $params = []): ?array {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    public static function insert(string $sql, array $params = []): int {
        self::query($sql, $params);
        return (int) self::getInstance()->lastInsertId();
    }

    public static function execute(string $sql, array $params = []): int {
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Returns the correct INSERT-or-skip keyword for the current driver.
     * Usage: Database::insertIgnore() . " INTO table ..."
     */
    public static function insertIgnore(): string {
        return env('DB_DRIVER', 'mysql') === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
    }

    public static function beginTransaction(): void {
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void {
        self::getInstance()->commit();
    }

    public static function rollback(): void {
        self::getInstance()->rollBack();
    }
}
