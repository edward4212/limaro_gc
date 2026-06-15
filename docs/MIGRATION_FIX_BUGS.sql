-- MIGRATION: Correciones de seguridad - Acceso temporal a archivos
-- Fecha: 2026-06-11
-- Descripción: Agrega tabla para tokens de acceso temporal a archivos office (fix bug #3)
-- Compatibilidad: BD existente con archivo(id_archivo BIGINT UNSIGNED), usuario(id_usuario INT UNSIGNED)

-- Tabla para almacenar tokens temporales de acceso a documentos Office
CREATE TABLE IF NOT EXISTS archivo_acceso_temporal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_archivo BIGINT UNSIGNED NOT NULL COMMENT 'FK a archivo.id_archivo',
    id_usuario INT UNSIGNED NOT NULL COMMENT 'FK a usuario.id_usuario',
    token VARCHAR(64) NOT NULL COMMENT 'Token único (hex 32 bytes = 64 caracteres)',
    expira_en TIMESTAMP NOT NULL COMMENT 'Fecha/hora de expiración del token',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha/hora de creación',
    FOREIGN KEY (id_archivo) REFERENCES archivo(id_archivo) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expira (expira_en),
    INDEX idx_archivo (id_archivo),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens temporales para acceso a archivos Office vía Microsoft Viewer';

-- Crear vista para tokens aún válidos (consultas frecuentes)
CREATE OR REPLACE VIEW archivo_acceso_temporal_validos AS
SELECT * FROM archivo_acceso_temporal
WHERE expira_en > NOW();

-- ========================================================================
-- MANTENIMIENTO - Ejecutar periódicamente via cron (cada hora):
-- ========================================================================
-- DELETE FROM archivo_acceso_temporal WHERE expira_en <= NOW();
-- 
-- Script recomendado para cron:
-- 0 * * * * mysql -u root -pPASSWORD limarocloud_limaro -e \
--   "DELETE FROM archivo_acceso_temporal WHERE expira_en <= NOW();"
-- ========================================================================
