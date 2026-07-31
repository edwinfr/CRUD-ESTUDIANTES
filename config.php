<?php

function getPdo(): ?PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $host = '127.0.0.1';
    $port = 3306;
    $username = 'root';
    $password = '';
    $database = 'sistema_estudiantes';
    $socket = '/opt/lampp/var/mysql/mysql.sock';

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        $pdo = new PDO("mysql:host={$host};port={$port};unix_socket={$socket};charset=utf8mb4", $username, $password, $options);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}`");
        $pdo->exec("USE `{$database}`");
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
