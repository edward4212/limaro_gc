<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Documentos.
 * Gestiona documentos registrados, versiones vigentes y obsoletos.
 *
 * NOTA: ajustado al esquema real de limarocloud_limaro:
 *  - tabla `documento` usa columnas: codigo, nombre_documento, objetivo_documento
 *  - tabla `versionamiento` usa: usuario_creacion, usuario_revision, usuario_aprobacion (VARCHAR)
 */
class DocumentoModel extends Model
{
    protected string $table      = 'documento';
    protected string $primaryKey = 'id_documento';

    /**
     * Listar documentos con proceso y tipo.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT d.*,
                   d.codigo AS codigo_documento,
                   p.proceso, p.sigla_proceso, td.tipo_documento, td.sigla_tipo_documento,
                   m.macroproceso,
                   s.subproceso AS nombre_subproceso, s.sigla_subproceso,
                   (SELECT v.numero_version FROM versionamiento v
                    WHERE v.id_documento = d.id_documento
                    ORDER BY v.numero_version DESC LIMIT 1) AS version_actual,
                   (SELECT v.estado_version FROM versionamiento v
                    WHERE v.id_documento = d.id_documento
                    ORDER BY v.numero_version DESC LIMIT 1) AS estado_version
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
            LEFT  JOIN subproceso     s  ON s.id_subproceso      = d.id_subproceso
            WHERE COALESCE(d.estado, 'ACTIVO') = 'ACTIVO'
            ORDER BY d.codigo
        ")->fetchAll();
    }

    /**
     * Listado maestro — documentos con versión VIGENTE.
     */
    public function vigentes(): array
    {
        return $this->query("
            SELECT d.id_documento, d.codigo, d.codigo AS codigo_documento,
                   d.nombre_documento, d.objetivo_documento,
                   d.objetivo_documento AS descripcion,
                   p.proceso, p.sigla_proceso, td.tipo_documento, td.sigla_tipo_documento,
                   m.macroproceso,
                   v.numero_version, v.fecha_aprobacion, v.estado_version,
                   v.id_versionamiento, v.documento AS archivo,
                   v.usuario_creacion   AS elaborador,
                   v.usuario_revision   AS revisor,
                   v.usuario_aprobacion AS aprobador
            FROM versionamiento v
            INNER JOIN documento      d  ON d.id_documento      = v.id_documento
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
            WHERE v.estado_version = 'VIGENTE'
              AND COALESCE(d.estado, 'ACTIVO') = 'ACTIVO'
            ORDER BY d.codigo
        ")->fetchAll();
    }

    /**
     * Listado de versiones OBSOLETAS.
     */
    public function obsoletos(): array
    {
        return $this->query("
            SELECT d.id_documento, d.codigo, d.codigo AS codigo_documento,
                   d.nombre_documento,
                   p.proceso, td.tipo_documento,
                   v.numero_version, v.fecha_aprobacion, v.fecha_obsoleto,
                   v.estado_version, v.id_versionamiento, v.documento AS archivo
            FROM versionamiento v
            INNER JOIN documento      d  ON d.id_documento      = v.id_documento
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
            WHERE v.estado_version = 'OBSOLETO'
            ORDER BY d.codigo, v.numero_version DESC
        ")->fetchAll();
    }

    /**
     * Generar código: SIGLA_TIPO-SIGLA_PROCESO-NNN (máx 10 chars).
     * Ej: PR-GC-001
     */
    public function generarCodigo(int $idTipo, int $idProceso): string
    {
        $tipo = $this->query(
            "SELECT sigla_tipo_documento FROM tipo_documento WHERE id_tipo_documento = ? LIMIT 1",
            [$idTipo]
        )->fetchColumn();

        $proc = $this->query(
            "SELECT sigla_proceso FROM proceso WHERE id_proceso = ? LIMIT 1",
            [$idProceso]
        )->fetchColumn();

        if (!$tipo || !$proc) {
            throw new \RuntimeException('Tipo o proceso no válido para generar código.');
        }

        $prefijo = strtoupper($tipo) . '-' . strtoupper($proc) . '-';
        $like    = $prefijo . '%';

        // MAX del consecutivo numérico real, respetando gaps por eliminaciones/reasignaciones
        $maxConsec = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)), 0)
             FROM documento WHERE codigo LIKE ?",
            [$like]
        )->fetchColumn();

        // Loop de seguridad: busca el primer consecutivo libre
        $intentos = 0;
        do {
            $maxConsec++;
            $consecutivo = str_pad((string)$maxConsec, 3, '0', STR_PAD_LEFT);
            $codigo      = $prefijo . $consecutivo;

            if (strlen($codigo) > 10) {
                throw new \RuntimeException(
                    "Código generado '$codigo' supera 10 caracteres. Revise las siglas del tipo o proceso."
                );
            }

            $existe = (int) $this->query(
                "SELECT COUNT(*) FROM documento WHERE codigo = ?",
                [$codigo]
            )->fetchColumn();

            $intentos++;
            if ($intentos > 999) {
                throw new \RuntimeException("No se pudo encontrar consecutivo libre para '$prefijo'.");
            }

        } while ($existe > 0);

        return $codigo;

        return $codigo;
    }

    /**
     * Crear documento + versión 0 en transacción.
     *
     * FIX: se reemplazó CALL createVersionamiento por INSERTs directos
     * porque el SP emite un SELECT interno que confunde a PDO e impide
     * recuperar el lastInsertId() correctamente.
     */
    public function crear(
        int    $idTipo,
        int    $idProceso,
        string $nombre,
        string $objetivo,
        mixed  $usuarioCreacion,
        ?int   $idSubproceso = null
    ): int {
        $codigo  = $this->generarCodigo($idTipo, $idProceso);
        $usuario = (string) $usuarioCreacion;

        // Obtener datos para construir la carpeta ANTES de la transacción
        $macro = $this->query("
            SELECT m.macroproceso, p.proceso, td.tipo_documento,
                   s.subproceso AS nombre_subproceso
            FROM proceso p
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento  = ?
            LEFT  JOIN subproceso     s  ON s.id_subproceso       = ?
            WHERE p.id_proceso = ?
            LIMIT 1
        ", [$idTipo, $idSubproceso ?? 0, $idProceso])->fetch();

        if (!$macro) {
            throw new \RuntimeException('No se encontraron datos de macroproceso/proceso/tipo para crear la carpeta.');
        }

        $this->beginTransaction();

        try {
            $docData = [
                'id_proceso'         => $idProceso,
                'id_tipo_documento'  => $idTipo,
                'codigo'             => $codigo,
                'nombre_documento'   => $nombre,
                'objetivo_documento' => $objetivo,
                'estado'             => 'ACTIVO',
            ];

            if ($idSubproceso !== null) {
                $docData['id_subproceso'] = $idSubproceso;
            }

            $idDocumento = $this->insert($docData);

            $this->query("
                INSERT INTO versionamiento
                    (numero_version, id_documento, descripcion_version,
                     usuario_creacion, fecha_creacion, estado_version)
                VALUES (0, ?, 'Se asigna Código al Documento', ?, NOW(), 'CREADO')
            ", [$idDocumento, $usuario]);

            $this->commit();

        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }

        // Crear carpeta V0 en disco DESPUÉS de confirmar la transacción
        // (si falla no revierte el doc, pero se puede recrear manualmente)
        try {
            crearCarpetaVersion(
                $macro['macroproceso'],
                $macro['proceso'],
                $macro['nombre_subproceso'] ?? null,
                $macro['tipo_documento'],
                $nombre,
                0  // versión 0
            );
        } catch (\Throwable $e) {
            // No interrumpir el flujo si el sistema de archivos falla
            error_log("Advertencia: no se pudo crear carpeta V0 para documento $idDocumento — " . $e->getMessage());
        }

        return $idDocumento;
    }

    /**
     * Actualizar datos básicos del documento.
     */
    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    /**
     * Reubicar un documento a otro proceso/subproceso.
     *
     * Pasos:
     *  1. Verificar que no haya solicitudes activas (CREADA|ASIGNADA|EN DESARROLLO)
     *  2. Obtener datos actuales y nuevos para comparar
     *  3. Generar código nuevo en el proceso destino
     *  4. Actualizar tabla documento (id_proceso, id_subproceso, codigo, codigo_anterior)
     *  5. Actualizar rutas en versionamiento.documento
     *  6. Mover carpeta en disco
     *  7. Registrar en documento_reasignacion
     *
     * @throws \RuntimeException  Si hay tareas activas o falla cualquier paso crítico
     */
    public function reubicar(
        int     $idDocumento,
        int     $idProcesoNuevo,
        ?int    $idSubprocesoNuevo,
        string  $usuario,
        ?string $observacion = null
    ): array {
        // ── 1. Bloquear si hay solicitudes activas ───────────────────
        $activas = (int) $this->query("
            SELECT COUNT(*) FROM solicitud
            WHERE codigo_documento = (SELECT codigo FROM documento WHERE id_documento = ? LIMIT 1)
              AND estado_solicitud IN ('CREADA','ASIGNADA','EN DESARROLLO')
        ", [$idDocumento])->fetchColumn();

        if ($activas > 0) {
            throw new \RuntimeException(
                "No se puede reubicar: el documento tiene $activas solicitud(es) activa(s) en estado CREADA, ASIGNADA o EN DESARROLLO. Finalícelas primero."
            );
        }

        // ── 2. Datos actuales ────────────────────────────────────────
        $actual = $this->query("
            SELECT d.*,
                   m.macroproceso  AS macro_actual,
                   p.proceso       AS proceso_actual,
                   p.id_macroproceso,
                   td.tipo_documento,
                   s.subproceso    AS subproceso_actual
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
            LEFT  JOIN subproceso     s  ON s.id_subproceso      = d.id_subproceso
            WHERE d.id_documento = ?
            LIMIT 1
        ", [$idDocumento])->fetch();

        if (!$actual) {
            throw new \RuntimeException('Documento no encontrado.');
        }

        // ── 3. Datos del destino ─────────────────────────────────────
        $destino = $this->query("
            SELECT p.proceso, p.sigla_proceso, p.id_macroproceso,
                   m.macroproceso,
                   s.subproceso AS nombre_subproceso
            FROM proceso p
            INNER JOIN macroproceso m ON m.id_macroproceso = p.id_macroproceso
            LEFT  JOIN subproceso   s ON s.id_subproceso   = ?
            WHERE p.id_proceso = ?
            LIMIT 1
        ", [$idSubprocesoNuevo ?? 0, $idProcesoNuevo])->fetch();

        if (!$destino) {
            throw new \RuntimeException('Proceso destino no válido.');
        }

        // Si proceso y subproceso no cambian, no hay nada que hacer
        if ((int)$actual['id_proceso'] === $idProcesoNuevo
            && (int)($actual['id_subproceso'] ?? 0) === ($idSubprocesoNuevo ?? 0)) {
            throw new \RuntimeException('El documento ya pertenece a ese proceso y subproceso.');
        }

        // ── 4. Nuevo código ──────────────────────────────────────────
        $codigoNuevo   = $this->generarCodigoParaProceso(
            (int)$actual['id_tipo_documento'],
            $idProcesoNuevo,
            $actual['codigo']  // excluir el código actual del conteo
        );
        $codigoAnterior = $actual['codigo'];

        // ── 5. Paths de carpetas ─────────────────────────────────────
        $carpetaAnterior = carpetaDocumento(
            $actual['macro_actual'],
            $actual['proceso_actual'],
            $actual['subproceso_actual'] ?? null,
            $actual['tipo_documento'],
            $actual['nombre_documento']
        );
        $carpetaNueva = carpetaDocumento(
            $destino['macroproceso'],
            $destino['proceso'],
            $destino['nombre_subproceso'] ?? null,
            $actual['tipo_documento'],
            $actual['nombre_documento']
        );

        // ── 6. Transacción BD ────────────────────────────────────────
        $this->beginTransaction();
        try {
            // Actualizar documento
            $this->update($idDocumento, [
                'id_proceso'      => $idProcesoNuevo,
                'id_subproceso'   => $idSubprocesoNuevo,
                'codigo'          => $codigoNuevo,
                'codigo_anterior' => $codigoAnterior,
            ]);

            // Actualizar rutas en versionamiento si la carpeta cambia
            if ($carpetaAnterior !== $carpetaNueva) {
                $this->query("
                    UPDATE versionamiento
                    SET documento = REPLACE(documento, ?, ?)
                    WHERE id_documento = ?
                      AND documento IS NOT NULL
                ", [
                    '/storage/documentos/' . str_replace(DIRECTORY_SEPARATOR, '/', $carpetaAnterior),
                    '/storage/documentos/' . str_replace(DIRECTORY_SEPARATOR, '/', $carpetaNueva),
                    $idDocumento,
                ]);
            }

            // Registrar reasignación en historial
            $this->query("
                INSERT INTO documento_reasignacion
                    (id_documento, codigo_anterior, codigo_nuevo,
                     id_proceso_anterior, id_proceso_nuevo,
                     carpeta_anterior, carpeta_nueva, usuario, observacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $idDocumento,
                $codigoAnterior,
                $codigoNuevo,
                $actual['id_proceso'],
                $idProcesoNuevo,
                $carpetaAnterior,
                $carpetaNueva,
                $usuario,
                $observacion,
            ]);

            $this->commit();

        } catch (\Throwable $e) {
            $this->rollback();
            throw $e;
        }

        // ── 7. Mover carpeta en disco (fuera de transacción) ─────────
        $baseStorage   = APP_ROOT . '/public/storage/documentos/';
        $origenAbs     = $baseStorage . $carpetaAnterior;
        $destinoAbs    = $baseStorage . $carpetaNueva;

        if (is_dir($origenAbs) && $origenAbs !== $destinoAbs) {
            // Crear carpeta padre destino si no existe
            $padreDestino = dirname($destinoAbs);
            if (!is_dir($padreDestino)) {
                mkdir($padreDestino, 0755, true);
            }

            if (!rename($origenAbs, $destinoAbs)) {
                // No revertir BD, pero avisar
                error_log("Advertencia: no se pudo mover carpeta de '$origenAbs' a '$destinoAbs'");
            }
        }

        return [
            'codigo_anterior' => $codigoAnterior,
            'codigo_nuevo'    => $codigoNuevo,
            'proceso_anterior'=> $actual['proceso_actual'],
            'proceso_nuevo'   => $destino['proceso'],
        ];
    }

    /**
     * Genera el siguiente código disponible para un proceso destino.
     * Usa MAX del sufijo numérico para respetar la secuencia real,
     * sin importar eliminaciones o reasignaciones previas.
     *
     * @param int    $idTipo
     * @param int    $idProceso
     * @param string $excluir   Código actual del documento que se mueve
     *                          (no debe contar como "ocupado" en destino)
     * @return string  Ej: "TH-FO-003"
     * @throws \RuntimeException  Si el código generado supera 10 caracteres
     */
    private function generarCodigoParaProceso(int $idTipo, int $idProceso, string $excluir): string
    {
        $tipo = $this->query(
            "SELECT sigla_tipo_documento FROM tipo_documento WHERE id_tipo_documento = ? LIMIT 1",
            [$idTipo]
        )->fetchColumn();

        $proc = $this->query(
            "SELECT sigla_proceso FROM proceso WHERE id_proceso = ? LIMIT 1",
            [$idProceso]
        )->fetchColumn();

        if (!$tipo || !$proc) {
            throw new \RuntimeException('Tipo o proceso no válido para generar código.');
        }

        $prefijo = strtoupper($tipo) . '-' . strtoupper($proc) . '-';
        $like    = $prefijo . '%';

        // MAX del número al final del código, excluyendo el documento que se está moviendo
        // SUBSTRING_INDEX(codigo, '-', -1) extrae '001', '002', etc.
        $maxConsec = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)), 0)
             FROM documento
             WHERE codigo LIKE ?
               AND codigo <> ?",
            [$like, $excluir]
        )->fetchColumn();

        // Buscar el primer consecutivo libre a partir de MAX+1
        // (loop de seguridad ante gaps o condiciones de carrera)
        $intentos = 0;
        do {
            $maxConsec++;
            $consecutivo = str_pad((string)$maxConsec, 3, '0', STR_PAD_LEFT);
            $codigo      = $prefijo . $consecutivo;

            if (strlen($codigo) > 10) {
                throw new \RuntimeException(
                    "Código generado '$codigo' supera 10 caracteres. Revise las siglas del tipo o proceso."
                );
            }

            $existe = (int) $this->query(
                "SELECT COUNT(*) FROM documento WHERE codigo = ?",
                [$codigo]
            )->fetchColumn();

            $intentos++;
            if ($intentos > 999) {
                throw new \RuntimeException("No se pudo encontrar un consecutivo libre para '$prefijo'.");
            }

        } while ($existe > 0);

        return $codigo;
    }

    /**
     * Datos de jerarquía de un documento para construir su carpeta.
     */
    public function infoCarpeta(int $id): ?array
    {
        $row = $this->query("
            SELECT m.macroproceso, p.proceso, td.tipo_documento,
                   s.subproceso AS nombre_subproceso
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
            LEFT  JOIN subproceso     s  ON s.id_subproceso      = d.id_subproceso
            WHERE d.id_documento = ?
            LIMIT 1
        ", [$id])->fetch();

        return $row ?: null;
    }

    /**
     * Procesos con conteo de documentos vigentes (para tarjetas del explorador).
     */
    public function procesoConConteo(): array
    {
        return $this->query("
            SELECT p.id_proceso, p.proceso, p.sigla_proceso,
                   m.macroproceso, m.id_macroproceso,
                   COUNT(DISTINCT d.id_documento) AS total_documentos
            FROM proceso p
            INNER JOIN macroproceso m ON m.id_macroproceso = p.id_macroproceso
            LEFT  JOIN documento    d ON d.id_proceso = p.id_proceso
                AND COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
                AND EXISTS (
                    SELECT 1 FROM versionamiento v
                    WHERE v.id_documento = d.id_documento
                    AND v.estado_version = 'VIGENTE'
                )
            WHERE p.estado = 'ACTIVO'
            GROUP BY p.id_proceso
            ORDER BY m.macroproceso, p.proceso
        ")->fetchAll();
    }

    /**
     * Subprocesos con conteo de documentos para un proceso dado.
     */
    public function subprocesoConConteo(int $idProceso): array
    {
        return $this->query("
            SELECT s.id_subproceso, s.subproceso, s.sigla_subproceso,
                   COUNT(DISTINCT d.id_documento) AS total_documentos
            FROM subproceso s
            LEFT JOIN documento d ON d.id_proceso = ? AND d.id_subproceso = s.id_subproceso
                AND COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
            WHERE s.id_proceso = ? AND s.estado = 'ACTIVO'
            GROUP BY s.id_subproceso
            ORDER BY s.subproceso
        ", [$idProceso, $idProceso])->fetchAll();
    }

    /**
     * Tipos de documento con conteo para un proceso (y opcionalmente subproceso).
     */
    public function tipoConConteo(int $idProceso, ?int $idSubproceso = null): array
    {
        $params = [$idProceso];
        $subSql = '';
        if ($idSubproceso) {
            $subSql  = 'AND d.id_subproceso = ?';
            $params[] = $idSubproceso;
        }
        return $this->query("
            SELECT td.id_tipo_documento, td.tipo_documento, td.sigla_tipo_documento,
                   COUNT(DISTINCT d.id_documento) AS total_documentos
            FROM tipo_documento td
            INNER JOIN documento d ON d.id_tipo_documento = td.id_tipo_documento
                AND d.id_proceso = ? $subSql
                AND COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
                AND EXISTS (
                    SELECT 1 FROM versionamiento v
                    WHERE v.id_documento = d.id_documento
                    AND v.estado_version = 'VIGENTE'
                )
            WHERE td.estado = 'ACTIVO'
            GROUP BY td.id_tipo_documento
            ORDER BY td.tipo_documento
        ", $params)->fetchAll();
    }

    /**
     * Documentos vigentes con info de versión para el listado del explorador.
     */
    public function vigentesParaExplorador(int $idProceso, int $idTipo, ?int $idSubproceso = null): array
    {
        $params = [$idProceso, $idTipo];
        $subSql = 'AND (d.id_subproceso IS NULL OR 1=1)';
        if ($idSubproceso) {
            $subSql   = 'AND d.id_subproceso = ?';
            $params[] = $idSubproceso;
        }
        return $this->query("
            SELECT d.id_documento, d.codigo, d.nombre_documento,
                   d.objetivo_documento,
                   m.macroproceso, p.proceso, p.sigla_proceso,
                   s.subproceso AS nombre_subproceso,
                   v.numero_version, v.fecha_aprobacion,
                   v.usuario_aprobacion, v.documento AS archivo_ruta,
                   v.descripcion_version
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            LEFT  JOIN subproceso     s  ON s.id_subproceso      = d.id_subproceso
            INNER JOIN versionamiento v  ON v.id_documento       = d.id_documento
                AND v.estado_version = 'VIGENTE'
                AND v.numero_version = (
                    SELECT MAX(v2.numero_version) FROM versionamiento v2
                    WHERE v2.id_documento = d.id_documento AND v2.estado_version = 'VIGENTE'
                )
            WHERE d.id_proceso = ?
              AND d.id_tipo_documento = ?
              $subSql
              AND COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
            ORDER BY d.codigo
        ", $params)->fetchAll();
    }
    
    public function idPorCodigo(string $codigo): int
    {
        return (int) $this->query(
            "SELECT id_documento FROM documento WHERE codigo = ? LIMIT 1",
            [$codigo]
        )->fetchColumn();
    }

    /**
     * Buscar documentos para autocomplete.
     */
    public function buscar(string $termino): array
    {
        return $this->query("
            SELECT d.id_documento, d.codigo, d.codigo AS codigo_documento,
                   d.nombre_documento
            FROM documento d
            WHERE (d.codigo LIKE ? OR d.nombre_documento LIKE ?)
              AND COALESCE(d.estado, 'ACTIVO') = 'ACTIVO'
            LIMIT 20
        ", ["%$termino%", "%$termino%"])->fetchAll();
    }
}