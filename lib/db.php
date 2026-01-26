<?php

function get_config(): array
{
    $configPath = __DIR__ . '/../config.php';
    if (!file_exists($configPath)) {
        $examplePath = __DIR__ . '/../config.example.php';
        throw new RuntimeException("Missing config.php. Copy config.example.php to config.php and update DB credentials.\nExample: {$examplePath}");
    }

    /** @var array $cfg */
    $cfg = require $configPath;
    return $cfg;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = get_config();
    $db = $cfg['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $db['host'],
        (int)$db['port'],
        $db['name'],
        $db['charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $hint = "Database connection failed.\n\n"
            . "Fix:\n"
            . "- Ensure MySQL/MariaDB is running\n"
            . "- Ensure the database exists and schema is imported (database/schema.sql)\n"
            . "- Update config.php with correct DB host/port/name/user/pass\n\n"
            . "Current config:\n"
            . "- host: " . ($db['host'] ?? '') . "\n"
            . "- port: " . ($db['port'] ?? '') . "\n"
            . "- name: " . ($db['name'] ?? '') . "\n"
            . "- user: " . ($db['user'] ?? '') . "\n\n"
            . "Original error: " . $e->getMessage();
        throw new RuntimeException($hint, 0, $e);
    }

    return $pdo;
}


