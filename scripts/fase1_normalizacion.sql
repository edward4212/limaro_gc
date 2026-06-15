-- ════════════════════════════════════════════════════════════════════
-- fase1_normalizacion.sql
-- Limaro SGC — Normalización Fase 1
-- Sin DROP de columnas — solo ADD y UNIQUE
-- Ejecutar en orden, paso a paso
-- ════════════════════════════════════════════════════════════════════

USE limarocloud_limaro;

-- ────────────────────────────────────────────────────────────────────
-- 1. acuerdos — clave única (año + número)
--    Previene duplicados silenciosos sin afectar ningún código.
-- ────────────────────────────────────────────────────────────────────
ALTER TABLE `acuerdos`
    ADD UNIQUE KEY IF NOT EXISTS `uq_acuerdo_anio_numero`
        (`año_acuerdo`, `numero_acuerdo`);

-- ────────────────────────────────────────────────────────────────────
-- 2. solicitud_asignacion — agregar id_usuario_asignador
--    El VARCHAR asignado_por se conserva como fallback histórico.
--    Nuevas asignaciones poblarán ambos campos desde el código.
-- ────────────────────────────────────────────────────────────────────
ALTER TABLE `solicitud_asignacion`
    ADD COLUMN IF NOT EXISTS `id_usuario_asignador` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK: id del usuario que hizo la asignación (normaliza asignado_por)'
    AFTER `asignado_por`;

-- Poblar desde usuario: buscar por nombre de usuario (asignado_por = username)
UPDATE `solicitud_asignacion` sa
INNER JOIN `usuario` u ON u.usuario = sa.asignado_por
SET sa.id_usuario_asignador = u.id_usuario
WHERE sa.id_usuario_asignador IS NULL
  AND sa.asignado_por IS NOT NULL
  AND sa.asignado_por <> '';

-- Verificar cuántos quedaron sin FK (usuarios renombrados o eliminados)
-- SELECT COUNT(*) FROM solicitud_asignacion
-- WHERE asignado_por <> '' AND id_usuario_asignador IS NULL;

ALTER TABLE `solicitud_asignacion`
    ADD CONSTRAINT `fk_sa_asignador`
        FOREIGN KEY (`id_usuario_asignador`) REFERENCES `usuario` (`id_usuario`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD KEY IF NOT EXISTS `idx_sa_asignador` (`id_usuario_asignador`);

-- ────────────────────────────────────────────────────────────────────
-- 3. documento_reasignacion — agregar id_usuario
--    El VARCHAR usuario se conserva como fallback histórico.
-- ────────────────────────────────────────────────────────────────────
ALTER TABLE `documento_reasignacion`
    ADD COLUMN IF NOT EXISTS `id_usuario` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK: id del usuario que reasignó (normaliza varchar usuario)'
    AFTER `usuario`;

-- Poblar desde usuario: buscar por nombre completo o username
UPDATE `documento_reasignacion` dr
INNER JOIN `usuario` u ON u.usuario = dr.usuario
SET dr.id_usuario = u.id_usuario
WHERE dr.id_usuario IS NULL
  AND dr.usuario IS NOT NULL
  AND dr.usuario <> '';

-- Si asignado_por era nombre completo en lugar de username:
UPDATE `documento_reasignacion` dr
INNER JOIN `usuario` u ON u.usuario = dr.usuario
INNER JOIN `empleado` e ON e.id_empleado = u.id_empleado
    AND e.nombre_completo = dr.usuario
SET dr.id_usuario = u.id_usuario
WHERE dr.id_usuario IS NULL
  AND dr.usuario IS NOT NULL;

ALTER TABLE `documento_reasignacion`
    ADD CONSTRAINT `fk_dr_usuario_norm`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD KEY IF NOT EXISTS `idx_dr_usuario` (`id_usuario`);

-- ────────────────────────────────────────────────────────────────────
-- 4. objetivo_medicion — agregar id_usuario
--    El VARCHAR registrado_por se conserva como fallback histórico.
-- ────────────────────────────────────────────────────────────────────
ALTER TABLE `objetivo_medicion`
    ADD COLUMN IF NOT EXISTS `id_usuario` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK: id del usuario que registró la medición (normaliza registrado_por)'
    AFTER `registrado_por`;

-- Poblar: registrado_por puede ser nombre completo
UPDATE `objetivo_medicion` om
INNER JOIN `empleado` e ON e.nombre_completo = om.registrado_por
INNER JOIN `usuario`  u ON u.id_empleado     = e.id_empleado
SET om.id_usuario = u.id_usuario
WHERE om.id_usuario IS NULL
  AND om.registrado_por IS NOT NULL
  AND om.registrado_por <> '';

ALTER TABLE `objetivo_medicion`
    ADD CONSTRAINT `fk_om_usuario_norm`
        FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD KEY IF NOT EXISTS `idx_om_usuario` (`id_usuario`);

-- ════════════════════════════════════════════════════════════════════
-- VERIFICACIÓN FINAL
-- ════════════════════════════════════════════════════════════════════
SELECT 'solicitud_asignacion' AS tabla,
       COUNT(*) AS sin_fk
FROM solicitud_asignacion
WHERE asignado_por <> '' AND id_usuario_asignador IS NULL
UNION ALL
SELECT 'documento_reasignacion',
       COUNT(*)
FROM documento_reasignacion
WHERE usuario <> '' AND id_usuario IS NULL
UNION ALL
SELECT 'objetivo_medicion',
       COUNT(*)
FROM objetivo_medicion
WHERE registrado_por IS NOT NULL AND id_usuario IS NULL;
-- Resultado esperado: las 3 filas con sin_fk = 0
-- Si alguna > 0, hay registros históricos sin usuario en BD actual (datos legacy)
-- ════════════════════════════════════════════════════════════════════
