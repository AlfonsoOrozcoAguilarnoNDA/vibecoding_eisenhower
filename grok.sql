CREATE DATABASE IF NOT EXISTS pendientes_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE pendientes_db;

CREATE TABLE IF NOT EXISTS pendientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(100) NOT NULL,
    urgencia ENUM('Urgente', 'Importante', 'Urgente Importante', 'No Urgente') NOT NULL,
    asunto VARCHAR(150) NOT NULL,
    descripcion VARCHAR(255) NULL,
    comentario TEXT NULL,
    fecha_realizar DATETIME NOT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    actualizado_en DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
