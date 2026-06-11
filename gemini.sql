CREATE TABLE IF NOT EXISTS `pendientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria` VARCHAR(50) NOT NULL,
  `urgencia` ENUM('Urgente', 'Importante', 'Urgente Importante', 'Importante no Urgente') NOT NULL,
  `asunto` VARCHAR(255) NOT NULL,
  `descripcion` VARCHAR(40) NOT NULL,
  `comentario` VARCHAR(80) NOT NULL,
  `fecha_compromiso` DATE NOT NULL,
  `fecha_registro` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
