<?php

function getDatabaseConfig(): array
{
    $config = [
        'host' => getenv('DB_HOST') ?: getenv('MYSQL_ADDON_HOST') ?: getenv('MYSQL_HOST') ?: 'b98n23uiof7zawx6bliz-mysql.services.clever-cloud.com',
        'port' => getenv('DB_PORT') ?: getenv('MYSQL_ADDON_PORT') ?: getenv('MYSQL_PORT') ?: '20158',
        'username' => getenv('DB_USER') ?: getenv('DB_USERNAME') ?: getenv('MYSQL_ADDON_USER') ?: 'unahcrallmdqs7qq',
        'password' => getenv('DB_PASSWORD') ?: getenv('MYSQL_ADDON_PASSWORD') ?: 'G5lYOObxVfCRBbTLjZT',
        'database' => getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: getenv('MYSQL_ADDON_DB') ?: 'b98n23uiof7zawx6bliz',
        'socket' => getenv('DB_SOCKET') ?: '',
    ];

    $databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_ADDON_URI') ?: '';
    if ($databaseUrl !== '') {
        $parsed = parse_url($databaseUrl);
        if ($parsed !== false) {
            if (!empty($parsed['host'])) {
                $config['host'] = $parsed['host'];
            }
            if (!empty($parsed['port'])) {
                $config['port'] = (string)$parsed['port'];
            }
            if (!empty($parsed['user'])) {
                $config['username'] = $parsed['user'];
            }
            if (!empty($parsed['pass'])) {
                $config['password'] = $parsed['pass'];
            }
            if (!empty($parsed['path'])) {
                $config['database'] = ltrim($parsed['path'], '/');
            }
        }
    }

    return $config;
}

function getPdo(): ?PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $config = getDatabaseConfig();

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
        if ($config['socket'] !== '') {
            $dsn .= ";unix_socket={$config['socket']}";
        }

        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}`");
        $pdo->exec("USE `{$config['database']}`");
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS estudiantes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                grado VARCHAR(50) NOT NULL,
                estado ENUM('Aprobado', 'Reprobado') NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    } catch (PDOException $e) {
        $pdo = null;
    }

    return $pdo;
}

function getDataFilePath(): string
{
    $dir = __DIR__ . '/data';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    return $dir . '/students.json';
}

function ensureInitialData(): void
{
    $file = getDataFilePath();
    if (file_exists($file)) {
        return;
    }

    $seed = [
        [
            'id' => 1,
            'nombre' => 'Fernandez',
            'grado' => '5°',
            'estado' => 'Aprobado',
        ],
        [
            'id' => 2,
            'nombre' => 'Ana',
            'grado' => '3°',
            'estado' => 'Reprobado',
        ],
    ];

    file_put_contents($file, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function loadStudentsFromFile(): array
{
    ensureInitialData();
    $content = file_get_contents(getDataFilePath());
    $data = json_decode($content, true);

    return is_array($data) ? $data : [];
}

function saveStudentsToFile(array $students): void
{
    file_put_contents(getDataFilePath(), json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function getNextStudentId(array $students): int
{
    $ids = array_map(static function ($student): int {
        return (int)($student['id'] ?? 0);
    }, $students);

    return $ids ? max($ids) + 1 : 1;
}
