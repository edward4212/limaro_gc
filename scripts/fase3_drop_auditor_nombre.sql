-- ════════════════════════════════════════════════════════════════════
-- fase3_drop_auditor_nombre.sql
-- Eliminar auditoria_plan_actividad.auditor_nombre
--
-- SEGURO: verificado que ningún SELECT lee esta columna directamente.
-- Todos los usos de "auditor_nombre" en el código son aliases de JOIN:
--   e.nombre_completo AS auditor_nombre  (via id_auditor → empleado)
-- Ningún INSERT escribe en esta columna.
-- ════════════════════════════════════════════════════════════════════

USE limarocloud_limaro;

-- PASO 1: Confirmar que la columna existe y tiene o no tiene datos
SELECT COUNT(*) AS filas_con_auditor_nombre
FROM auditoria_plan_actividad
WHERE auditor_nombre IS NOT NULL AND auditor_nombre <> '';

-- PASO 2: Migrar datos históricos a id_auditor si es posible
-- (por si algún registro solo tenía auditor_nombre sin id_auditor)
UPDATE auditoria_plan_actividad apa
INNER JOIN empleado e ON e.nombre_completo = apa.auditor_nombre
    AND e.estado_empleado = 'ACTIVO'
SET apa.id_auditor = e.id_empleado
WHERE apa.id_auditor IS NULL
  AND apa.auditor_nombre IS NOT NULL
  AND apa.auditor_nombre <> '';

-- PASO 3: Verificar cuántos quedaron sin id_auditor (datos legacy sin match)
SELECT id, actividad, auditor_nombre
FROM auditoria_plan_actividad
WHERE id_auditor IS NULL
  AND auditor_nombre IS NOT NULL
  AND auditor_nombre <> '';
-- Si hay filas: asignar id_auditor manualmente antes de continuar

-- PASO 4: Eliminar la columna VARCHAR redundante
ALTER TABLE `auditoria_plan_actividad`
    DROP COLUMN `auditor_nombre`;

-- ════════════════════════════════════════════════════════════════════
