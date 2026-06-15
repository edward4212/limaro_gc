-- ════════════════════════════════════════════════════════════════════
-- fase4_acuerdos_documento.sql
-- Limaro SGC — Eliminar campo legacy acuerdos.documento
--
-- CONTEXTO: el campo acuerdos.documento (VARCHAR 500) almacenaba la
-- ruta física del archivo directamente. Desde la migración al módulo
-- de archivos (tabla archivo), el controller guarda '' (vacío) y el
-- archivo real va a archivo.modulo='ACUERDO'. La columna es obsoleta.
--
-- EJECUTAR EN ORDEN — no saltar pasos
-- ════════════════════════════════════════════════════════════════════

USE limarocloud_limaro;

-- ────────────────────────────────────────────────────────────────────
-- PASO 1: Verificar huérfanos
-- Acuerdos con ruta en documento que NO tienen registro en archivo
-- Resultado esperado: 0 filas
-- Si hay filas > 0: NO continuar — migrar esos archivos primero
-- ────────────────────────────────────────────────────────────────────
SELECT a.id_acuerdo,
       a.año_acuerdo,
       a.numero_acuerdo,
       a.nombre_acuerdo,
       a.documento AS ruta_legacy
FROM acuerdos a
WHERE a.documento IS NOT NULL
  AND a.documento <> ''
  AND a.id_acuerdo NOT IN (
      SELECT ar.id_referencia
      FROM archivo ar
      WHERE ar.modulo = 'ACUERDO'
  );

-- ────────────────────────────────────────────────────────────────────
-- PASO 2: Verificar que todos los acuerdos con archivo en
-- la tabla archivo tienen el archivo físico accesible
-- (verificación informativa — no bloquea el DROP)
-- ────────────────────────────────────────────────────────────────────
SELECT COUNT(*) AS total_acuerdos,
       SUM(CASE WHEN ar.id_archivo IS NOT NULL THEN 1 ELSE 0 END) AS con_archivo_en_tabla,
       SUM(CASE WHEN a.documento <> '' THEN 1 ELSE 0 END)         AS con_ruta_legacy
FROM acuerdos a
LEFT JOIN archivo ar ON ar.modulo = 'ACUERDO'
    AND ar.id_referencia = a.id_acuerdo;

-- ────────────────────────────────────────────────────────────────────
-- PASO 3: Ejecutar SOLO si el PASO 1 devolvió 0 filas
-- Eliminar la columna obsoleta
-- ────────────────────────────────────────────────────────────────────
-- ALTER TABLE `acuerdos` DROP COLUMN `documento`;

-- ════════════════════════════════════════════════════════════════════
-- DESPUÉS del DROP — ajuste en AcuerdoController
-- Eliminar 'documento' => '' del array en crear() y actualizar()
-- (el código ya fue preparado para funcionar sin esa columna,
--  pero el INSERT fallará si incluye una columna que no existe)
-- ════════════════════════════════════════════════════════════════════
