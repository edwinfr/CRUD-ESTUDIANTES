CREATE DATABASE IF NOT EXISTS sistema_estudiantes;
USE sistema_estudiantes;

CREATE TABLE IF NOT EXISTS estudiantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    grado VARCHAR(50) NOT NULL,
    estado ENUM('Aprobado', 'Reprobado') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
