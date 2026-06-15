<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Solicitudes.
 */
class SolicitudModel extends Model
{
    protected string $table      = 'solicitud';
    protected string $primaryKey = 'id_solicitud';

    private function mapearPrioridad(string $p): string
    {
        return match ($p) {
            'URGENTE_IMPORTANTE'       => 'IMPORTANTE - URGENTE',
            'URGENTE_NO_IMPORTANTE'    => 'NO IMPORTANTE - URGENTE',
            'NO_URGENTE_IMPORTANTE'    => 'IMPORTANTE - NO URGENTE',
            'NO_URGENTE_NO_IMPORTANTE' => 'NO IMPORTANTE - NO URGENTE',
            default => 'IMPORTANTE - NO URGENTE',
        };
    }

    public function crearViaSP(
        string $tipoSolicitud,
        string $prioridad,
        string $descripcion,
        int    $idEmpleado,
        int    $idTipoDocumento,
        ?int   $idDocumento     = null,
        ?string $codigoDocumento = null
    ): int {
        $prioridadReal = $this->mapearPrioridad($prioridad);

        if ($idDocumento && !$codigoDocumento) {
            $codigoDocumento = (string) ($this->query(
                "SELECT codigo FROM documento WHERE id_documento = ? LIMIT 1",
                [$idDocumento]
            )->fetchColumn() ?: '');
        }

        $this->query("
            INSERT INTO solicitud
                (id_empleado, prioridad, id_tipo_documento, tipo_solicitud,
                 estado_solicitud, codigo_documento, solicitud, fecha_solicitud)
            VALUES (?, ?, ?, ?, 'CREADA', ?, ?, NOW())
        ", [
            $idEmpleado,
            $prioridadReal,
            $idTipoDocumento,
            $tipoSolicitud,
            $codigoDocumento ?: '',
            $descripcion,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Agregar comentario a una solicitud con INSERT directo.
     * El SP create_comentario_sol tiene una firma de 20 parámetros incompatible
     * con el uso actual; se usa INSERT directo a solicitud_comentario.
     *
     * NOTA: La tabla solicitud_comentario NO tiene id_empleado; usa usuario_comentario (VARCHAR).
     */
    public function agregarComentario(int $idSolicitud, int $idEmpleado, string $comentario): void
    {
        $usuario = \App\Core\Auth::get('nombre_completo')
                ?? \App\Core\Auth::get('usuario')
                ?? 'sistema';

        $this->query("
            INSERT INTO solicitud_comentario
                (id_solicitud, comentario, usuario_comentario, fecha_comentario, estado)
            VALUES (?, ?, ?, NOW(), 'ACTIVO')
        ", [$idSolicitud, $comentario, $usuario]);
    }


    /**
     * Solicitudes asignadas a un empleado como ELABORADOR (estado ACTIVA).
     * Usada en TareaController::asignadas() para mostrar las solicitudes
     * pendientes de iniciar tarea.
     */
    public function asignadasAEmpleado(int $idEmpleado): array
    {
        return $this->query("
            SELECT s.*,
                   td.tipo_documento,
                   td.sigla_tipo_documento,
                   e.nombre_completo       AS solicitante,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   sa.rol_asignacion,
                   sa.fecha_asignacion,
                   sa.estado               AS estado_asignacion,
                   t.id_tarea
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            LEFT  JOIN documento      d  ON d.codigo             = s.codigo_documento
            INNER JOIN solicitud_asignacion sa
                    ON sa.id_solicitud   = s.id_solicitud
                   AND sa.id_empleado    = ?
                   AND sa.estado         = 'ACTIVA'
            LEFT  JOIN tarea t ON t.id_solicitud = s.id_solicitud
            WHERE s.estado_solicitud NOT IN ('FINALIZADA','FINALIZADA_SIN_TRAMITE','CANCELADA')
            ORDER BY sa.fecha_asignacion ASC
        ", [$idEmpleado])->fetchAll();
    }

    public function misRadicadas(int $idEmpleado): array
    {
        return $this->query("
            SELECT s.*,
                   td.tipo_documento,
                   s.codigo_documento,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   ar.id_archivo,
                   ar.nombre_original AS archivo_nombre,
                   ar.ruta_relativa   AS archivo_ruta,
                   ar.mime_type       AS archivo_mime
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            LEFT  JOIN documento      d  ON d.codigo = s.codigo_documento
            LEFT JOIN archivo ar ON ar.modulo = 'SOLICITUD'
                AND ar.id_referencia = s.id_solicitud
                AND ar.id_archivo = (
                    SELECT MAX(a2.id_archivo) FROM archivo a2
                    WHERE a2.modulo = 'SOLICITUD'
                      AND a2.id_referencia = s.id_solicitud
                )
            WHERE s.id_empleado = ?
            ORDER BY s.fecha_solicitud DESC
        ", [$idEmpleado])->fetchAll();
    }

    public function porEstado(string $estado): array
    {
        return $this->query("
            SELECT s.*, td.tipo_documento,
                   e.nombre_completo AS solicitante,
                   s.codigo_documento,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            LEFT  JOIN documento      d  ON d.codigo = s.codigo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            WHERE s.estado_solicitud = ?
            ORDER BY s.fecha_solicitud DESC
        ", [$estado])->fetchAll();
    }

    public function finalizadasPaginado(
        int     $pagina   = 1,
        int     $porPagina = 50,
        ?string $desde    = null,
        ?string $hasta    = null
    ): array {
        $filtro = '';
        $params = [];
        if ($desde) { $filtro .= " AND s.fecha_solucion >= ?"; $params[] = $desde; }
        if ($hasta) { $filtro .= " AND s.fecha_solucion <= ?"; $params[] = $hasta . ' 23:59:59'; }
        return $this->paginar("
            SELECT s.*, td.tipo_documento, e.nombre_completo AS solicitante
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            WHERE s.estado_solicitud = 'FINALIZADA'
            {$filtro}
            ORDER BY s.fecha_solucion DESC
        ", $params, $pagina, $porPagina);
    }

    public function finalizadas(?string $desde = null, ?string $hasta = null): array
    {
        $sql    = "
            SELECT s.*, td.tipo_documento, e.nombre_completo AS solicitante
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            WHERE s.estado_solicitud = 'FINALIZADA'
        ";
        $params = [];
        if ($desde) { $sql .= " AND s.fecha_solucion >= ?"; $params[] = $desde; }
        if ($hasta) { $sql .= " AND s.fecha_solucion <= ?"; $params[] = $hasta . ' 23:59:59'; }
        $sql .= " ORDER BY s.fecha_solucion DESC";
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Detalle de solicitud — corrige el JOIN roto a empleado en comentarios.
     * solicitud_comentario usa usuario_comentario VARCHAR, no id_empleado FK.
     */
    public function detalle(int $id): ?array
    {
        $sol = $this->query("
            SELECT s.*, td.tipo_documento, e.nombre_completo AS solicitante,
                   s.codigo_documento,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   COALESCE(ea.nombre_completo, NULL) AS asignado_a
            FROM solicitud s
            LEFT  JOIN documento      d  ON d.codigo = s.codigo_documento
            LEFT  JOIN empleado       ea ON ea.id_empleado = s.id_empleado_asignado
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            WHERE s.id_solicitud = ?
            LIMIT 1
        ", [$id])->fetch();

        if (!$sol) return null;

        // ✅ Corregido: solicitud_comentario no tiene id_empleado, usa usuario_comentario
        $sol['comentarios'] = $this->query("
            SELECT sc.id_solicitud_comentario,
                   sc.id_solicitud,
                   sc.comentario,
                   sc.usuario_comentario AS nombre_completo,
                   sc.fecha_comentario,
                   sc.estado
            FROM solicitud_comentario sc
            WHERE sc.id_solicitud = ?
              AND sc.estado = 'ACTIVO'
            ORDER BY sc.fecha_comentario ASC
        ", [$id])->fetchAll();

        $sol['asignaciones'] = $this->query("
            SELECT sa.*, e.nombre_completo
            FROM solicitud_asignacion sa
            LEFT JOIN empleado e ON e.id_empleado = sa.id_empleado
            WHERE sa.id_solicitud = ?
            ORDER BY sa.id ASC
        ", [$id])->fetchAll();

        $sol['archivos'] = $this->query("
            SELECT * FROM archivo
            WHERE modulo = 'SOLICITUD' AND id_referencia = ?
        ", [$id])->fetchAll();

        return $sol;
    }

    public function asignar(
        int    $idSolicitud,
        int    $idEmpleado,
        string $rolAsignacion,
        string $asignadoPor
    ): void {
        // Cancelar asignaciones ACTIVAS previas del mismo rol para evitar duplicados
        // (la UNIQUE uk_sol_emp_rol_activa impide dos activas del mismo empleado/rol)
        $this->query(
            "UPDATE solicitud_asignacion SET estado = 'CANCELADA'
             WHERE id_solicitud = ? AND rol_asignacion = ? AND estado = 'ACTIVA'
               AND id_empleado <> ?",
            [$idSolicitud, strtoupper($rolAsignacion), $idEmpleado]
        );

        // INSERT IGNORE: si ya existe la combinación activa, no falla
        $this->query("
            INSERT IGNORE INTO solicitud_asignacion
                (id_solicitud, id_empleado, rol_asignacion, asignado_por, estado)
            VALUES (?, ?, ?, ?, 'ACTIVA')
        ", [$idSolicitud, $idEmpleado, strtoupper($rolAsignacion), $asignadoPor]);

        $nombre = $this->query(
            "SELECT nombre_completo FROM empleado WHERE id_empleado = ?",
            [$idEmpleado]
        )->fetchColumn() ?: (string)$idEmpleado;

        $this->query("
            UPDATE solicitud
            SET estado_solicitud     = 'ASIGNADA',
                funcionario_asignado = ?,
                id_empleado_asignado = ?,
                fecha_asignacion     = NOW()
            WHERE id_solicitud = ?
        ", [$nombre, $idEmpleado, $idSolicitud]);
    }

    public function cambiarEstado(int $id, string $estado, ?string $fechaSolucion = null): void
    {
        if ($fechaSolucion) {
            $this->query(
                "UPDATE solicitud SET estado_solicitud = ?, fecha_solucion = ? WHERE id_solicitud = ?",
                [$estado, $fechaSolucion, $id]
            );
        } else {
            $this->query(
                "UPDATE solicitud SET estado_solicitud = ? WHERE id_solicitud = ?",
                [$estado, $id]
            );
        }
    }

    public function conteoEstados(): array
    {
        return $this->query("
            SELECT estado_solicitud AS estado, COUNT(*) AS total
            FROM solicitud
            GROUP BY estado_solicitud
        ")->fetchAll();
    }

    public function ultimas(int $n = 5): array
    {
        return $this->query("
            SELECT s.id_solicitud, s.tipo_solicitud, s.estado_solicitud,
                   s.fecha_solicitud, td.tipo_documento, e.nombre_completo
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            ORDER BY s.fecha_solicitud DESC
            LIMIT ?
        ", [$n])->fetchAll();
    }

    /**
     * Obtener la asignación activa de un rol en una solicitud.
     */
    public function asignacionActiva(int $idSolicitud, string $rol): ?array
    {
        return $this->query("
            SELECT sa.*, e.nombre_completo
            FROM solicitud_asignacion sa
            INNER JOIN empleado e ON e.id_empleado = sa.id_empleado
            WHERE sa.id_solicitud   = ?
              AND sa.rol_asignacion = ?
              AND sa.estado         = 'ACTIVA'
            ORDER BY sa.fecha_asignacion DESC
            LIMIT 1
        ", [$idSolicitud, $rol])->fetch() ?: null;
    }


    public function resumenEstados(): array
    {
        $rows = $this->query(
            "SELECT estado_solicitud AS estado, COUNT(*) AS total
             FROM solicitud GROUP BY estado_solicitud"
        )->fetchAll();
        return array_column($rows, 'total', 'estado');
    }


    public function todas(): array
    {
        return $this->query("
            SELECT s.*,
                e.nombre_completo  AS solicitante,
                COALESCE(ea.nombre_completo, NULL) AS nombre_asignado,
                -- HU-022: conteo de archivos anexos
                (SELECT COUNT(*) FROM archivo a
                 WHERE a.modulo = 'SOLICITUD' AND a.id_referencia = s.id_solicitud) AS total_anexos
            FROM solicitud s
            LEFT JOIN empleado e  ON e.id_empleado = s.id_empleado
            LEFT JOIN empleado ea ON ea.id_empleado = s.id_empleado_asignado
            ORDER BY s.id_solicitud DESC
        ")->fetchAll();
    }



    /**
     * Listado paginado de todas las solicitudes (PERF-001).
     */
    public function todasPaginado(int $pagina = 1, int $porPagina = 50): array
    {
        return $this->paginar("
            SELECT s.*,
                e.nombre_completo AS solicitante,
                COALESCE(ea.nombre_completo, NULL) AS nombre_asignado,
                (SELECT COUNT(*) FROM archivo a
                 WHERE a.modulo = 'SOLICITUD' AND a.id_referencia = s.id_solicitud) AS total_anexos
            FROM solicitud s
            LEFT JOIN empleado e  ON e.id_empleado  = s.id_empleado
            LEFT JOIN empleado ea ON ea.id_empleado = s.id_empleado_asignado
            ORDER BY s.id_solicitud DESC
        ", [], $pagina, $porPagina);
    }

    /**
     * Listado paginado por estado (PERF-001).
     */
    public function porEstadoPaginado(string $estado, int $pagina = 1, int $porPagina = 50): array
    {
        return $this->paginar("
            SELECT s.*,
                e.nombre_completo AS solicitante
            FROM solicitud s
            LEFT JOIN empleado e ON e.id_empleado = s.id_empleado
            WHERE s.estado_solicitud = ?
            ORDER BY s.id_solicitud DESC
        ", [$estado], $pagina, $porPagina);
    }

    public function resumenPorEmpleado(int $idEmpleado): array
    {
        $rows = $this->query(
            "SELECT estado_solicitud AS estado, COUNT(*) AS total
             FROM solicitud WHERE id_empleado = ?
             GROUP BY estado_solicitud",
            [$idEmpleado]
        )->fetchAll();
        return array_column($rows, 'total', 'estado');
    }


    /** Obtener comentarios de una solicitud (para vistas de tarea) */
    public function comentariosDeSolicitud(int $idSolicitud): array
    {
        if (!$idSolicitud) return [];
        return $this->query("
            SELECT id_solicitud_comentario, comentario,
                   usuario_comentario AS nombre_completo,
                   fecha_comentario, estado
            FROM solicitud_comentario
            WHERE id_solicitud = ? AND estado = 'ACTIVO'
            ORDER BY fecha_comentario ASC
        ", [$idSolicitud])->fetchAll();
    }

    // ── Métodos de escritura centralizados (BUG-004 / HU-M03) ────────────

    /**
     * Inserta un comentario de sistema en solicitud_comentario.
     * Centraliza los 6+ INSERT directos dispersos en los controladores.
     *
     * @param string $tipo  ASIGNACION | REASIGNACION | TAREA | ESTADO | SISTEMA | MANUAL
     */
    public function comentarioSistema(
        int    $idSolicitud,
        string $texto,
        string $tipo   = 'SISTEMA',
        string $autor  = ''
    ): void {
        if (!$idSolicitud || trim($texto) === '') return;

        $autor = $autor !== ''
            ? $autor
            : (\App\Core\Auth::get('nombre_completo')
               ?? \App\Core\Auth::get('usuario')
               ?? 'Sistema');

        $this->query("
            INSERT INTO solicitud_comentario
                (id_solicitud, comentario, usuario_comentario, tipo_comentario, estado)
            VALUES (?, ?, ?, ?, 'ACTIVO')
        ", [$idSolicitud, trim($texto), $autor, strtoupper($tipo)]);
    }

    /**
     * Actualiza id_empleado_asignado + fecha_asignacion en la tabla solicitud.
     * Centraliza los UPDATE dispersos en asignar() y reasignar().
     */
    public function actualizarEmpleadoAsignado(int $idSolicitud, int $idEmpleado, ?string $nombre = null): void
    {
        if ($nombre !== null) {
            $this->query(
                "UPDATE solicitud
                    SET id_empleado_asignado = ?,
                        funcionario_asignado = ?,
                        fecha_asignacion     = NOW()
                  WHERE id_solicitud = ?",
                [$idEmpleado, $nombre, $idSolicitud]
            );
        } else {
            $this->query(
                "UPDATE solicitud
                    SET id_empleado_asignado = ?,
                        fecha_asignacion     = NOW()
                  WHERE id_solicitud = ?",
                [$idEmpleado, $idSolicitud]
            );
        }
    }

    /**
     * Cancela las asignaciones activas de un rol en una solicitud.
     */
    public function cancelarAsignacionRol(int $idSolicitud, string $rol): void
    {
        $this->query(
            "UPDATE solicitud_asignacion
                SET estado = 'CANCELADA'
              WHERE id_solicitud = ? AND rol_asignacion = ? AND estado = 'ACTIVA'",
            [$idSolicitud, strtoupper($rol)]
        );
    }

    /**
     * Completa las asignaciones activas de un rol en una solicitud.
     */
    public function completarAsignacionRol(int $idSolicitud, string $rol): void
    {
        $this->query(
            "UPDATE solicitud_asignacion
                SET estado = 'COMPLETADA'
              WHERE id_solicitud = ? AND rol_asignacion = ? AND estado = 'ACTIVA'",
            [$idSolicitud, strtoupper($rol)]
        );
    }

    /**
     * Reactiva la asignación más reciente de un rol (sin importar su estado).
     * Usado al devolver una tarea al paso anterior.
     */
    public function reactivarAsignacionRol(int $idSolicitud, string $rol): void
    {
        $this->query(
            "UPDATE solicitud_asignacion
                SET estado = 'ACTIVA'
              WHERE id_solicitud = ? AND rol_asignacion = ?
              ORDER BY id DESC LIMIT 1",
            [$idSolicitud, strtoupper($rol)]
        );
    }

    /**
     * Upsert de asignación de un rol a un empleado.
     * Intenta reactivar un registro existente del mismo empleado/rol;
     * si no existe, lo inserta.
     *
     * @return bool  true si se insertó un registro nuevo, false si se reactivó uno existente.
     */
    public function upsertAsignacion(
        int    $idSolicitud,
        int    $idEmpleado,
        string $rol,
        string $asignadoPor
    ): bool {
        $rol = strtoupper($rol);

        $idUsuarioAsignador = \App\Core\Auth::id();
        $stmt = $this->query(
            "UPDATE solicitud_asignacion
                SET estado = 'ACTIVA', asignado_por = ?,
                    id_usuario_asignador = COALESCE(id_usuario_asignador, ?)
              WHERE id_solicitud = ? AND id_empleado = ? AND rol_asignacion = ?
              ORDER BY id DESC LIMIT 1",
            [$asignadoPor, $idUsuarioAsignador, $idSolicitud, $idEmpleado, $rol]
        );

        if ($stmt->rowCount() > 0) {
            return false; // reactivado
        }

        $this->query(
            "INSERT INTO solicitud_asignacion
                (id_solicitud, id_empleado, rol_asignacion, asignado_por,
                 id_usuario_asignador, estado)
             VALUES (?, ?, ?, ?, ?, 'ACTIVA')",
            [$idSolicitud, $idEmpleado, $rol, $asignadoPor, $idUsuarioAsignador]
        );
        return true; // insertado nuevo
    }

    /**
     * Obtiene el empleado actualmente asignado con un rol dado.
     * Incluye datos de contacto para notificaciones.
     */
    public function empleadoPorRol(int $idSolicitud, string $rol): ?array
    {
        return $this->query("
            SELECT sa.id_empleado, e.nombre_completo, e.correo_empleado
              FROM solicitud_asignacion sa
              INNER JOIN empleado e ON e.id_empleado = sa.id_empleado
             WHERE sa.id_solicitud   = ?
               AND sa.rol_asignacion = ?
             ORDER BY sa.id DESC LIMIT 1
        ", [$idSolicitud, strtoupper($rol)])->fetch() ?: null;
    }


    /**
     * Verifica si una solicitud tiene tarea activa o asignación vigente.
     * HU-N04: usado para ocultar "Finalizar sin Trámite".
     */
    public function tieneTareaActiva(int $idSolicitud): bool
    {
        // Tiene tarea creada
        $tarea = $this->query(
            "SELECT id_tarea FROM tarea WHERE id_solicitud = ? LIMIT 1",
            [$idSolicitud]
        )->fetch();
        if ($tarea) return true;
        // Tiene asignación activa (aunque la tarea aún no se inició)
        $asig = $this->query(
            "SELECT id FROM solicitud_asignacion WHERE id_solicitud = ? AND estado = 'ACTIVA' LIMIT 1",
            [$idSolicitud]
        )->fetch();
        return (bool)$asig;
    }

}