-- ============================================================
-- Script SQL: Pendientes - Cuadrante de Eisenhower
-- Generado: Implementación de Referencia
-- Zona horaria: America/Mexico_City (manejada en PHP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS pendientes_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE pendientes_db;

-- ------------------------------------------------------------
-- Tabla principal de pendientes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pendientes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  categoria       VARCHAR(100) NOT NULL COMMENT 'Categoría libre capturada por el usuario',
  urgencia        ENUM('Urgente', 'Importante', 'Urgente Importante', 'Importante No Urgente') 
                  NOT NULL COMMENT 'Cuadrante de Eisenhower - valores exactos del prompt',
  asunto          VARCHAR(150) NOT NULL COMMENT 'Título de la tarea',
  descripcion     VARCHAR(40)  NULL COMMENT 'Descripción corta (máx 40 caracteres)',
  comentario      VARCHAR(80)  NULL COMMENT 'Comentario adicional (máx 80 caracteres)',
  fecha_realizar  DATETIME     NOT NULL COMMENT 'Fecha y hora objetivo (timezone México en PHP)',
  creado_en       DATETIME     DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha/hora de creación',
  actualizado_en  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Última modificación',

  KEY idx_urgencia (urgencia),
  KEY idx_fecha_realizar (fecha_realizar)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Control de pendientes con cuadrante de Eisenhower';
