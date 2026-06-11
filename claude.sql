-- ============================================================
-- Script de base de datos: Pendientes · Cuadrante Eisenhower
-- Charset: utf8mb4 (soporte completo Unicode / emojis)
-- Zona horaria: América/México_Centro (manejada en PHP)
-- Generado por: Claude Sonnet 4.6 (Anthropic)
-- Fecha: <?php echo date('Y-m-d'); ?>
-- ============================================================

-- Crear base de datos (opcional, ajusta el nombre)
-- CREATE DATABASE IF NOT EXISTS `mi_bd`
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
-- USE `mi_bd`;

-- ------------------------------------------------------------
-- Tabla principal de pendientes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pendientes` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `categoria`      VARCHAR(80)      NOT NULL COMMENT 'Categoría libre capturada por el usuario',
  `urgencia`       ENUM(
                     'urgente_importante',
                     'urgente_no_importante',
                     'importante_no_urgente',
                     'no_urgente_no_importante'
                   )                NOT NULL COMMENT 'Cuadrante de Eisenhower',
  `asunto`         VARCHAR(120)     NOT NULL COMMENT 'Título breve de la tarea',
  `descripcion`    VARCHAR(40)      NOT NULL COMMENT 'Descripción corta (máx 40 caracteres)',
  `comentario`     VARCHAR(80)      NOT NULL COMMENT 'Comentario adicional (máx 80 caracteres)',
  `fecha_realizar` DATE             NOT NULL COMMENT 'Fecha objetivo para completar la tarea',
  `fecha_crear`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha/hora de creación (hora México en PHP)',
  `fecha_update`   DATETIME         NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación',

  PRIMARY KEY (`id`),
  KEY `idx_urgencia`      (`urgencia`),
  KEY `idx_fecha_realizar`(`fecha_realizar`),
  KEY `idx_fecha_crear`   (`fecha_crear`)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Control de pendientes · Cuadrante de Eisenhower';

-- ------------------------------------------------------------
-- Datos de ejemplo (opcional, eliminar en producción)
-- ------------------------------------------------------------
INSERT INTO `pendientes`
  (`categoria`, `urgencia`, `asunto`, `descripcion`, `comentario`, `fecha_realizar`, `fecha_crear`)
VALUES
  ('Trabajo',   'urgente_importante',       'Entregar reporte Q2',     'Reporte trimestral',   'Revisar con contador antes de enviar',    DATE_ADD(CURDATE(), INTERVAL 1 DAY),  NOW()),
  ('Trabajo',   'urgente_no_importante',    'Contestar correos',       'Bandeja de entrada',   'Priorizar clientes activos',              DATE_ADD(CURDATE(), INTERVAL 2 DAY),  NOW()),
  ('Personal',  'importante_no_urgente',    'Certificación PHP 8',     'Estudio autodidacta',  'Completar módulo 3 del curso en línea',   DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW()),
  ('Hogar',     'no_urgente_no_importante', 'Reorganizar archivero',   'Documentos físicos',   'Solo si hay tiempo libre el fin de semana', DATE_ADD(CURDATE(), INTERVAL 60 DAY), NOW());

-- ------------------------------------------------------------
-- Vista útil: pendientes con días restantes
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW `v_pendientes_estado` AS
SELECT
  p.*,
  DATEDIFF(p.fecha_realizar, CURDATE())    AS dias_restantes,
  DATEDIFF(CURDATE(), p.fecha_crear)       AS dias_desde_creacion,
  CASE
    WHEN DATEDIFF(p.fecha_realizar, CURDATE()) < 0  THEN 'vencida'
    WHEN DATEDIFF(p.fecha_realizar, CURDATE()) = 0  THEN 'vence_hoy'
    WHEN DATEDIFF(p.fecha_realizar, CURDATE()) <= 3 THEN 'proxima'
    ELSE 'normal'
  END AS estado_fecha
FROM `pendientes` p
ORDER BY p.fecha_realizar ASC;
