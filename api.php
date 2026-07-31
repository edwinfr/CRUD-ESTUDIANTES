<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? '';

function filterStudents(array $students, string $search = '', string $estado = '', string $grado = ''): array
{
    return array_values(array_filter($students, static function (array $student) use ($search, $estado, $grado): bool {
        $matchesSearch = $search === '' || stripos($student['nombre'] ?? '', $search) !== false || stripos($student['grado'] ?? '', $search) !== false;
        $matchesEstado = $estado === '' || ($student['estado'] ?? '') === $estado;
        $matchesGrado = $grado === '' || ($student['grado'] ?? '') === $grado;

        return $matchesSearch && $matchesEstado && $matchesGrado;
    }));
}

try {
    $pdo = getPdo();

    if ($action === 'list') {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 5;
        $search = trim($_GET['search'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $grado = trim($_GET['grado'] ?? '');

        if ($pdo instanceof PDO) {
            $where = [];
            $params = [];

            if ($search !== '') {
                $where[] = '(nombre LIKE :search_nombre OR grado LIKE :search_grado)';
                $params[':search_nombre'] = '%' . $search . '%';
                $params[':search_grado'] = '%' . $search . '%';
            }

            if ($estado !== '') {
                $where[] = 'estado = :estado';
                $params[':estado'] = $estado;
            }

            if ($grado !== '') {
                $where[] = 'grado = :grado';
                $params[':grado'] = $grado;
            }

            $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countStmt = $pdo->prepare("select COUNT(*) AS total FROM estudiantes {$whereSql}");
            $countStmt->execute($params);
            $totalItems = (int)$countStmt->fetchColumn();
            $totalPages = max(1, (int)ceil($totalItems / $perPage));
            $offset = ($page - 1) * $perPage;

            $stmt = $pdo->prepare(
                "select id, nombre, grado, estado FROM estudiantes {$whereSql} ORDER BY id DESC LIMIT :limit OFFSET :offset"
            );

            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $students = $stmt->fetchAll();
        } else {
            $students = filterStudents(loadStudentsFromFile(), $search, $estado, $grado);
            $totalItems = count($students);
            $totalPages = max(1, (int)ceil($totalItems / $perPage));
            $offset = ($page - 1) * $perPage;
            $students = array_slice($students, $offset, $perPage);
        }

        echo json_encode([
            'status' => 'success',
            'page' => $page,
            'limit' => $perPage,
            'total_records' => $totalItems,
            'total_pages' => $totalPages,
            'data' => $students,
        ]);
        exit;
    }

    if ($action === 'details') {
        $id = (int)($_GET['id'] ?? 0);

        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare('select id, nombre, grado, estado FROM estudiantes WHERE id = :id');
            $stmt->execute([':id' => $id]);
            $student = $stmt->fetch();
        } else {
            $students = loadStudentsFromFile();
            $student = null;
            foreach ($students as $item) {
                if ((int)($item['id'] ?? 0) === $id) {
                    $student = $item;
                    break;
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'data' => $student,
        ]);
        exit;
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $grado = trim($_POST['grado'] ?? '');
        $estado = trim($_POST['estado'] ?? '');

        if ($nombre === '' || $grado === '' || !in_array($estado, ['Aprobado', 'Reprobado'], true)) {
            throw new Exception('Todos los campos son obligatorios.');
        }

        if ($pdo instanceof PDO) {
            if ($id > 0) {
                $stmt = $pdo->prepare('update estudiantes SET nombre = :nombre, grado = :grado, estado = :estado WHERE id = :id');
                $stmt->execute([':nombre' => $nombre, ':grado' => $grado, ':estado' => $estado, ':id' => $id]);
            } else {
                $stmt = $pdo->prepare('insert into estudiantes (nombre, grado, estado) VALUES (:nombre, :grado, :estado)');
                $stmt->execute([':nombre' => $nombre, ':grado' => $grado, ':estado' => $estado]);
            }
        } else {
            $students = loadStudentsFromFile();
            if ($id > 0) {
                foreach ($students as &$student) {
                    if ((int)($student['id'] ?? 0) === $id) {
                        $student['nombre'] = $nombre;
                        $student['grado'] = $grado;
                        $student['estado'] = $estado;
                        break;
                    }
                }
                unset($student);
            } else {
                $students[] = [
                    'id' => getNextStudentId($students),
                    'nombre' => $nombre,
                    'grado' => $grado,
                    'estado' => $estado,
                ];
            }
            saveStudentsToFile($students);
        }

        echo json_encode([
            'status' => 'success',
            'message' => $id > 0 ? 'Estudiante actualizado correctamente.' : 'Estudiante registrado correctamente.',
        ]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        if ($pdo instanceof PDO) {
            $stmt = $pdo->prepare('DELETE FROM estudiantes WHERE id = :id');
            $stmt->execute([':id' => $id]);
        } else {
            $students = loadStudentsFromFile();
            $students = array_values(array_filter($students, static function (array $student) use ($id): bool {
                return (int)($student['id'] ?? 0) !== $id;
            }));
            saveStudentsToFile($students);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Estudiante eliminado correctamente.',
        ]);
        exit;
    }

    throw new Exception('Acción no válida.');
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
