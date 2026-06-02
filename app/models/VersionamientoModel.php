<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Versionamiento de documentos.
 */
class VersionamientoModel extends Model
{
    protected string $table      = 'versionamiento';
    protected string $primaryKey = 'id_versionamiento';

    /**
     * Historial de versiones por documento (timeline completo).
     */
    /**
     * Listar documentos con su versión máxima (para el índice de versionamiento).
     */
    public function listar(): array
    {
        return $this->query("
            SELECT d.id_documento, d.codigo, d.nombre_documento,
                   p.proceso, m.macroproceso,
                   td.tipo_documento, td.sigla_tipo_documento AS sigla_tipo,
                   v.numero_version  AS max_version,
                   v.estado_version,
                   v.fecha_aprobacion
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
            LEFT  JOIN versionamiento v  ON v.id_versionamiento = (
                SELECT id_versionamiento FROM versionamiento
                WHERE id_documento = d.id_documento
                ORDER BY numero_version DESC LIMIT 1
            )
            WHERE COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
            ORDER BY d.codigo
        ")->fetchAll();
    }

    public function porDocumento(int $idDocumento): array
    {
        return $this->query("
            SELECT v.*,
                   v.usuario_creacion   AS elaborador,
                   v.usuario_revision   AS revisor,
                   v.usuario_aprobacion AS aprobador,
                   v.documento          AS archivo,
                   ar.id_archivo, ar.nombre_original AS archivo_nombre
            FROM versionamiento v
            LEFT  JOIN archivo  ar ON ar.modulo = 'VERSIONAMIENTO'
                                   AND ar.id_referencia = v.id_versionamiento
            WHERE v.id_documento = ?
            ORDER BY v.numero_version DESC
        ", [$idDocumento])->fetchAll();
    }

    /**
     * Última versión de un documento.
     */
    public function ultimaVersion(int $idDocumento): ?array
    {
        $row = $this->query("
            SELECT * FROM versionamiento
            WHERE id_documento = ?
            ORDER BY numero_version DESC
            LIMIT 1
        ", [$idDocumento])->fetch();
        return $row ?: null;
    }

    /**
     * Crear nueva versión directamente en versionamiento (no usa el SP, porque
     * createVersionamiento también recrea el documento). Se usa para versiones > 0.
     *
     * @param string $estado     ENUM OBSOLETO|CREADO|VIGENTE
     */
    public function crearVersion(
        int     $idDocumento,
        int     $numeroVersion,
        ?string $descripcion,
        ?string $rutaArchivo,
        string  $estado,
        ?string $usuarioCreacion  = null,
        ?string $usuarioRevision  = null,
        ?string $usuarioAprobacion = null,
        ?string $fechaAprobacion  = null
    ): int {
        $this->query("
            INSERT INTO versionamiento
                (numero_version, id_documento, descripcion_version,
                 usuario_creacion, fecha_creacion,
                 usuario_revision, fecha_revision,
                 usuario_aprobacion, fecha_aprobacion,
                 documento, estado_version)
            VALUES (?, ?, ?, ?, NOW(), ?, NULL, ?, ?, ?, ?)
        ", [
            $numeroVersion,
            $idDocumento,
            $descripcion,
            $usuarioCreacion,
            $usuarioRevision,
            $usuarioAprobacion,
            $fechaAprobacion,
            $rutaArchivo,
            $estado,
        ]);
        $idVersion = (int) $this->db->lastInsertId();

        // Crear carpeta Vn en disco para esta versión
        try {
            $info = $this->query("
                SELECT m.macroproceso, p.proceso, td.tipo_documento,
                       d.nombre_documento, s.subproceso AS nombre_subproceso
                FROM documento d
                INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
                INNER JOIN macroproceso   m  ON m.id_macroproceso    = p.id_macroproceso
                INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
                LEFT  JOIN subproceso     s  ON s.id_subproceso      = d.id_subproceso
                WHERE d.id_documento = ?
                LIMIT 1
            ", [$idDocumento])->fetch();

            if ($info) {
                crearCarpetaVersion(
                    $info['macroproceso'],
                    $info['proceso'],
                    $info['nombre_subproceso'] ?? null,
                    $info['tipo_documento'],
                    $info['nombre_documento'],
                    $numeroVersion
                );
            }
        } catch (\Throwable $e) {
            error_log("Advertencia: no se pudo crear carpeta V{$numeroVersion} — " . $e->getMessage());
        }

        return $idVersion;
    }

    /**
     * Crear versión manual desde el módulo de versionamiento (sin flujo solicitud/tarea).
     * La carpeta en disco ya fue creada por el controller antes de llamar este método.
     */
    public function crearVersionManual(
        int     $idDocumento,
        int     $numeroVersion,
        string  $descripcion,
        ?string $rutaArchivo,
        string  $estado,
        string  $usuarioCreacion,
        string  $usuarioRevision  = '',
        string  $usuarioAprobacion = '',
        ?string $fechaAprobacion  = null
    ): int {
        $this->query("
            INSERT INTO versionamiento
                (numero_version, id_documento, descripcion_version,
                 usuario_creacion, fecha_creacion,
                 usuario_revision, fecha_revision,
                 usuario_aprobacion, fecha_aprobacion,
                 documento, estado_version)
            VALUES (?, ?, ?, ?, NOW(), ?, NULL, ?, ?, ?, ?)
        ", [
            $numeroVersion,
            $idDocumento,
            $descripcion,
            $usuarioCreacion,
            $usuarioRevision  ?: null,
            $usuarioAprobacion ?: null,
            $fechaAprobacion,
            $rutaArchivo,
            $estado,
        ]);

        return (int) $this->db->lastInsertId();
    }

    
    public function actualizarFlujo(int $idVersion, array $campos): void
    {
        if (empty($campos)) return;
        $sets = [];
        $vals = [];
        foreach ($campos as $k => $v) {
            $sets[] = "`$k` = ?";
            $vals[] = $v;
        }
        $vals[] = $idVersion;
        $this->query(
            "UPDATE versionamiento SET " . implode(', ', $sets) . " WHERE id_versionamiento = ?",
            $vals
        );
    }

    /**
     * Actualizar estado de una versión.
     */
    public function actualizarEstado(int $idVersion, string $estado, ?string $fechaObsoleto = null): void
    {
        if ($fechaObsoleto) {
            $this->query("
                UPDATE versionamiento
                SET estado_version = ?, fecha_obsoleto = ?
                WHERE id_versionamiento = ?
            ", [$estado, $fechaObsoleto, $idVersion]);
        } else {
            $this->query("
                UPDATE versionamiento
                SET estado_version = ?
                WHERE id_versionamiento = ?
            ", [$estado, $idVersion]);
        }
    }

    /**
     * Obsoletizar versiones anteriores de un documento.
     */
    public function obsoletizarAnteriores(int $idDocumento, int $excepto): void
    {
        $this->query("
            UPDATE versionamiento
            SET estado_version = 'OBSOLETO', fecha_obsoleto = NOW()
            WHERE id_documento = ?
              AND id_versionamiento <> ?
              AND estado_version = 'VIGENTE'
        ", [$idDocumento, $excepto]);
    }

    /**
     * Listado general con datos de documento para la vista /versionamiento.
     */
    // public function listar(): array
    // {
    //     return $this->query("
    //         SELECT v.*,
    //               d.codigo, d.codigo AS codigo_documento, d.nombre_documento,
    //               td.tipo_documento, p.proceso
    //         FROM versionamiento v
    //         INNER JOIN documento      d  ON d.id_documento      = v.id_documento
    //         INNER JOIN tipo_documento td ON td.id_tipo_documento = d.id_tipo_documento
    //         INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
    //         ORDER BY d.codigo, v.numero_version DESC
    //     ")->fetchAll();
    // }

    /**
     * Número máximo de versión para un documento.
     */
    public function maxVersion(int $idDocumento): int
    {
        return (int) $this->query(
            "SELECT IFNULL(MAX(numero_version), 0) FROM versionamiento WHERE id_documento = ?",
            [$idDocumento]
        )->fetchColumn();
    }
}
