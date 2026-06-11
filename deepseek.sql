-- Crear base de datos
CREATE DATABASE IF NOT EXISTS task_manager;
USE task_manager;

-- Crear tabla de tareas
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    urgency ENUM('urgente', 'importante', 'urgente_importante', 'importante_no_urgente') NOT NULL,
    subject VARCHAR(40) NOT NULL,
    description VARCHAR(40) NOT NULL,
    comment VARCHAR(80),
    due_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar datos de ejemplo
INSERT INTO tasks (category, urgency, subject, description, comment, due_date) VALUES
('Trabajo', 'urgente', 'Reunión con cliente', 'Preparar presentación', 'Incluir datos del último trimestre', DATE_ADD(NOW(), INTERVAL 2 DAY)),
('Personal', 'importante', 'Cita médica', 'Chequeo anual', 'Llevar resultados de análisis', DATE_ADD(NOW(), INTERVAL 5 DAY)),
('Estudio', 'urgente_importante', 'Entregar proyecto', 'Sistema de tareas', 'Revisar documentación', DATE_ADD(NOW(), INTERVAL 1 DAY)),
('Hogar', 'importante_no_urgente', 'Mantenimiento', 'Reparar fuga de agua', 'Llamar al plomero', DATE_ADD(NOW(), INTERVAL 7 DAY));
