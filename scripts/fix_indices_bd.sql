-- ════════════════════════════════════════════════════════════════════════
-- fix_indices_bd.sql
-- Limaro SGC — Correctivos de base de datos (informe de auditoría técnica)
-- Generado: 2026-06-12
-- Ejecutar con un usuario con privilegios ALTER sobre limarocloud_limaro.
-- ════════════════════════════════════════════════════════════════════════

USE limarocloud_limaro;

-- ────────────────────────────────────────────────────────────────────────
-- 1. TABLA solicitud — eliminar índices duplicados
--
-- REGLA: InnoDB ancla cada FK al PRIMER índice creado sobre esa columna.
-- Eliminar ese índice mientras existe la FK da error #1553.
-- La solución es eliminar el SEGUNDO índice (el duplicado posterior),
-- que no tiene ninguna FK anclada.
--
-- Par 1: id_tipo_documento (anclado a fk_solicitud_tipo) vs idx_solicitud_tipo
--        → Eliminar idx_solicitud_tipo   (sin FK)
-- Par 2: idempleado (anclado a fk_solicitud_empleado) vs idx_solicitud_empleado
--        → Eliminar idx_solicitud_empleado   (sin FK)
-- Par 3: idx_sol_estado vs idx_solicitud_estado  (ninguna FK en ninguno)
--        → Eliminar idx_solicitud_estado   (el más reciente)
-- Par 4: idx_sol_fecha vs idx_solicitud_fecha  (ninguna FK en ninguno)
--        → Eliminar idx_solicitud_fecha
-- ────────────────────────────────────────────────────────────────────────
ALTER TABLE `solicitud`
    DROP KEY IF EXISTS `idx_solicitud_tipo`,      -- duplicado de id_tipo_documento (FK en el original)
    DROP KEY IF EXISTS `idx_solicitud_empleado`,  -- duplicado de idempleado (FK en el original)
    DROP KEY IF EXISTS `idx_solicitud_estado`,    -- duplicado de idx_sol_estado
    DROP KEY IF EXISTS `idx_solicitud_fecha`;     -- duplicado de idx_sol_fecha

-- DB-007 (real): agregar índice faltante sobre tipo_solicitud
-- (distinto de id_tipo_documento FK — aquí indexamos el campo ENUM)
ALTER TABLE `solicitud`
    ADD KEY IF NOT EXISTS `idx_sol_tipo_solicitud` (`tipo_solicitud`);

-- ────────────────────────────────────────────────────────────────────────
-- 2. TABLA archivo — eliminar índice duplicado
--    idx_archivo_modulo_ref ≡ idx_ar_ref  (ambos sobre modulo, id_referencia)
-- ────────────────────────────────────────────────────────────────────────
ALTER TABLE `archivo`
    DROP KEY IF EXISTS `idx_archivo_modulo_ref`;   -- duplicado de idx_ar_ref

-- ────────────────────────────────────────────────────────────────────────
-- 3. DB-006: solicitud_comentario.id_solicitud — no debe ser nullable
--    (un comentario sin solicitud no tiene sentido)
--    PRECAUCIÓN: verificar que no existan filas con id_solicitud NULL antes
--    de ejecutar. Si las hay: DELETE o reasignar.
-- ────────────────────────────────────────────────────────────────────────
-- Paso 1: verificar huérfanos (ejecutar solo en lectura primero)
-- SELECT COUNT(*) FROM solicitud_comentario WHERE id_solicitud IS NULL;

-- Paso 2: si el conteo es 0, aplicar:
ALTER TABLE `solicitud_comentario`
    MODIFY COLUMN `id_solicitud` BIGINT(7) UNSIGNED NOT NULL
    COMMENT 'FK a solicitud — obligatorio';

-- ────────────────────────────────────────────────────────────────────────
-- 4. DB-009: tabla catalogo_estado — sin referencias en código ni BD
--    Archivar antes de eliminar. Ejecutar solo si el SELECT devuelve 0 filas.
-- ────────────────────────────────────────────────────────────────────────
-- Verificar que está vacía:
-- SELECT COUNT(*) FROM catalogo_estado;

-- Si está vacía y no hay FKs apuntando a ella:
-- DROP TABLE IF EXISTS `catalogo_estado`;

-- ────────────────────────────────────────────────────────────────────────
-- 5. BUG-006: estandarizar id_solicitud a BIGINT UNSIGNED sin display-width
--    MariaDB ignora el display-width de BIGINT pero la inconsistencia
--    genera confusión. Migrar en orden por dependencias FK.
-- ────────────────────────────────────────────────────────────────────────
ALTER TABLE `solicitud`
    MODIFY COLUMN `id_solicitud` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `solicitud_comentario`
    MODIFY COLUMN `id_solicitud` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `solicitud_asignacion`
    MODIFY COLUMN `id_solicitud` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `tarea`
    MODIFY COLUMN `id_solicitud` BIGINT UNSIGNED DEFAULT NULL;


-- ────────────────────────────────────────────────────────────────────────
-- NORMALIZACIÓN: objetivo_calidad.id_responsable (BUG-010)
-- El campo responsable (VARCHAR) existe pero sin FK. Agregar id_responsable
-- y poblar desde empleado por nombre_completo (migración de datos).
-- ────────────────────────────────────────────────────────────────────────

-- PASO 1: agregar columna FK
ALTER TABLE `objetivo_calidad`
    ADD COLUMN IF NOT EXISTS `id_responsable` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK a empleado.id_empleado — reemplaza VARCHAR responsable'
    AFTER `responsable`;

-- PASO 2: poblar desde empleado (migración de datos existentes)
UPDATE `objetivo_calidad` oc
INNER JOIN `empleado` e ON e.nombre_completo = oc.responsable
   AND e.estado_empleado = 'ACTIVO'
SET oc.id_responsable = e.id_empleado
WHERE oc.id_responsable IS NULL AND oc.responsable IS NOT NULL;

-- PASO 3: agregar FK e índice
ALTER TABLE `objetivo_calidad`
    ADD CONSTRAINT `fk_obj_responsable`
        FOREIGN KEY (`id_responsable`) REFERENCES `empleado` (`id_empleado`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD KEY IF NOT EXISTS `idx_obj_responsable` (`id_responsable`);

-- PASO 4 (FUTURO — solo cuando todas las vistas usen id_responsable):
--   ALTER TABLE objetivo_calidad DROP COLUMN responsable;

-- ────────────────────────────────────────────────────────────────────────
-- DB-014: EN DESARROLLO → EN_DESARROLLO (coordinar con PHP primero)
-- Ejecutar SOLO después de actualizar el código PHP
-- ────────────────────────────────────────────────────────────────────────
-- PASO 1 (PHP): cambiar todas las ocurrencias de 'EN DESARROLLO' en código
-- PASO 2 (datos):
--   UPDATE solicitud SET estado_solicitud='EN_DESARROLLO'
--    WHERE estado_solicitud='EN DESARROLLO';
-- PASO 3 (ENUM):
--   ALTER TABLE solicitud
--     MODIFY COLUMN estado_solicitud
--     ENUM('CREADA','ASIGNADA','EN_DESARROLLO','FINALIZADA','FINALIZADA_SIN_TRAMITE')
--     NOT NULL DEFAULT 'CREADA';
-- PASO 4: actualizar DocumentoModel::reubicar() línea con IN('...','EN DESARROLLO',...)
-- ════════════════════════════════════════════════════════════════════════
-- FIN DEL SCRIPT
-- Verificar con: SHOW INDEX FROM solicitud; SHOW INDEX FROM archivo;
-- ════════════════════════════════════════════════════════════════════════

-- ════════════════════════════════════════════════════════════════════════
-- fix_bugs_sec_bd.sql  (añadido al script de correcciones)
-- ════════════════════════════════════════════════════════════════════════

-- ────────────────────────────────────────────────────────────────────────
-- BUG-008: versionamiento.id_documento — nullable, debería ser NOT NULL
--
-- Un versionamiento sin documento es un registro huérfano que rompe
-- todos los JOINs con la tabla documento.
--
-- PASO 1: verificar que no existen filas con NULL (ejecutar antes del ALTER)
--   SELECT COUNT(*) FROM versionamiento WHERE id_documento IS NULL;
--
-- PASO 2: si el COUNT es 0, aplicar:
-- ────────────────────────────────────────────────────────────────────────
ALTER TABLE `versionamiento`
    MODIFY COLUMN `id_documento` INT(10) UNSIGNED NOT NULL
    COMMENT 'FK al documento — nunca puede ser NULL';

-- ────────────────────────────────────────────────────────────────────────
-- DB-011: índices faltantes en columnas de filtro frecuente
-- (las otras tablas ya tenían índice; estas tres no)
-- ────────────────────────────────────────────────────────────────────────
ALTER TABLE `empleado`
    ADD KEY IF NOT EXISTS `idx_empleado_estado` (`estado_empleado`);

ALTER TABLE `objetivo_calidad`
    ADD KEY IF NOT EXISTS `idx_obj_estado` (`estado`),
    ADD KEY IF NOT EXISTS `idx_obj_proceso` (`id_proceso`);

ALTER TABLE `revision_direccion`
    ADD KEY IF NOT EXISTS `idx_rd_estado` (`estado`),
    ADD KEY IF NOT EXISTS `idx_rd_anio`   (`anio`);

-- ────────────────────────────────────────────────────────────────────────
-- DB-014: solicitud.estado_solicitud — 'EN DESARROLLO' tiene espacio
--
-- PRECAUCIÓN: requiere actualizar el código PHP primero (o simultáneamente).
-- El valor 'EN DESARROLLO' se usa en:
--   TareaService.php, SolicitudModel.php, vistas solicitudes/
--
-- PASO 1: actualizar código PHP (cambiar 'EN DESARROLLO' → 'EN_DESARROLLO')
-- PASO 2: migrar datos
--   UPDATE solicitud SET estado_solicitud = 'EN_DESARROLLO'
--    WHERE estado_solicitud = 'EN DESARROLLO';
-- PASO 3: modificar el ENUM (no incluido aquí — ejecutar manualmente
--   tras confirmar que el código PHP ya usa EN_DESARROLLO):
--
--   ALTER TABLE solicitud
--       MODIFY COLUMN estado_solicitud
--       ENUM('CREADA','ASIGNADA','EN_DESARROLLO','FINALIZADA','FINALIZADA_SIN_TRAMITE')
--       NOT NULL DEFAULT 'CREADA';
-- ────────────────────────────────────────────────────────────────────────

-- ────────────────────────────────────────────────────────────────────────
-- DB-015: log_cambios — índice compuesto para búsquedas de historial
-- ────────────────────────────────────────────────────────────────────────
ALTER TABLE `log_cambios`
    ADD KEY IF NOT EXISTS `idx_log_tabla_registro` (`tabla_auditada`, `id_registro`);


-- ────────────────────────────────────────────────────────────────────────
-- NORMALIZACIÓN: objetivo_calidad.id_responsable (BUG-010)
-- El campo responsable (VARCHAR) existe pero sin FK. Agregar id_responsable
-- y poblar desde empleado por nombre_completo (migración de datos).
-- ────────────────────────────────────────────────────────────────────────

-- PASO 1: agregar columna FK
ALTER TABLE `objetivo_calidad`
    ADD COLUMN IF NOT EXISTS `id_responsable` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK a empleado.id_empleado — reemplaza VARCHAR responsable'
    AFTER `responsable`;

-- PASO 2: poblar desde empleado (migración de datos existentes)
UPDATE `objetivo_calidad` oc
INNER JOIN `empleado` e ON e.nombre_completo = oc.responsable
   AND e.estado_empleado = 'ACTIVO'
SET oc.id_responsable = e.id_empleado
WHERE oc.id_responsable IS NULL AND oc.responsable IS NOT NULL;

-- PASO 3: agregar FK e índice
ALTER TABLE `objetivo_calidad`
    ADD CONSTRAINT `fk_obj_responsable`
        FOREIGN KEY (`id_responsable`) REFERENCES `empleado` (`id_empleado`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    ADD KEY IF NOT EXISTS `idx_obj_responsable` (`id_responsable`);

-- PASO 4 (FUTURO — solo cuando todas las vistas usen id_responsable):
--   ALTER TABLE objetivo_calidad DROP COLUMN responsable;

-- ────────────────────────────────────────────────────────────────────────
-- DB-014: EN DESARROLLO → EN_DESARROLLO (coordinar con PHP primero)
-- Ejecutar SOLO después de actualizar el código PHP
-- ────────────────────────────────────────────────────────────────────────
-- PASO 1 (PHP): cambiar todas las ocurrencias de 'EN DESARROLLO' en código
-- PASO 2 (datos):
--   UPDATE solicitud SET estado_solicitud='EN_DESARROLLO'
--    WHERE estado_solicitud='EN DESARROLLO';
-- PASO 3 (ENUM):
--   ALTER TABLE solicitud
--     MODIFY COLUMN estado_solicitud
--     ENUM('CREADA','ASIGNADA','EN_DESARROLLO','FINALIZADA','FINALIZADA_SIN_TRAMITE')
--     NOT NULL DEFAULT 'CREADA';
-- PASO 4: actualizar DocumentoModel::reubicar() línea con IN('...','EN DESARROLLO',...)
-- ════════════════════════════════════════════════════════════════════════
-- FIN
-- ════════════════════════════════════════════════════════════════════════
