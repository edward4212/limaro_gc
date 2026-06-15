<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Tareas.
 *
 * Estructura real en BD:
 *   tarea        → (id_tarea, id_solicitud)
 *   tarea_estado → (id_tarea_estado, id_tarea, usuario_tarea_estado VARCHAR,
 *                   fecha_tarea_estado, tarea_estado ENUM, ruta, documento_tarea)
 */
class TareaModel extends Model
{
    protected string $table      = 'tarea';
    protected string $primaryKey = 'id_tarea';

    /**
     * Crear tarea con INSERT directo (el SP create_tarea tiene firma incompatible).
     */
    public function crearViaSP(int $idSolicitud, int $idEmpleado = 0): int
    {
        // Popula columnas normalizadas: estado_actual, id_empleado_actual, fecha_inicio
        $this->query(
            "INSERT INTO tarea
                (id_solicitud, estado_actual, id_empleado_actual, fecha_inicio)
             VALUES (?, 'CREADO', ?, NOW())",
            [$idSolicitud, $idEmpleado ?: null]
        );
        return (int) $this->db->lastInsertId();
    }

    /**
     * Agregar estado de tarea con INSERT directo.
     */
    public function agregarEstado(
        int     $idTarea,
        string  $estado,
        string  $usuario,
        ?string $ruta      = null,
        ?string $documento = null,
        ?string $comentario = null,
        ?int    $idUsuario  = null
    ): void {
        $this->query("
            INSERT INTO tarea_estado
                (id_tarea, id_usuario, usuario_tarea_estado, fecha_tarea_estado,
                 tarea_estado, ruta, documento_tarea, comentario_estado)
            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)
        ", [
            $idTarea,
            $idUsuario,        // FK normalizada
            $usuario,
            $estado,
            $ruta ?? '',
            $documento,
            $comentario,       // columna separada (normalización)
        ]);

        // Actualizar estado_actual en tabla tarea (desnormalización controlada)
        $this->query(
            "UPDATE tarea SET estado_actual = ? WHERE id_tarea = ?",
            [$estado, $idTarea]
        );
    }

    /**
     * Alias de agregarEstado() — firma compatible con TareaController.
     * Acepta el cuarto parámetro $idUsuario que el controlador pasa pero no usa la BD.
     */
    public function agregarEstadoViaSP(
        int     $idTarea,
        string  $estado,
        string  $comentario,
        int     $idUsuario = 0
    ): void {
        $usuario = \App\Core\Auth::get('nombre_completo')
                ?? \App\Core\Auth::get('usuario')
                ?? 'sistema';
        // Pasa id_usuario (FK normalizada) y comentario separado
        $this->agregarEstado(
            $idTarea, $estado, $usuario,
            null, null,
            $comentario,
            $idUsuario ?: null
        );
    }

    /**
     * Solicitudes asignadas a un empleado listas para iniciar tarea.
     */
    public function asignadasAEmpleado(int $idEmpleado): array
    {
        return $this->query("
            SELECT s.id_solicitud, s.tipo_solicitud, s.solicitud AS descripcion,
                   s.prioridad, s.estado_solicitud, s.fecha_solicitud,
                   s.codigo_documento,
                   sa.rol_asignacion, sa.fecha_asignacion,
                   sa.id AS id_asignacion,
                   td.tipo_documento, e.nombre_completo AS solicitante,
                   (SELECT t.id_tarea FROM tarea t
                     WHERE t.id_solicitud = s.id_solicitud LIMIT 1) AS id_tarea
            FROM solicitud_asignacion sa
            INNER JOIN solicitud      s  ON s.id_solicitud      = sa.id_solicitud
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            WHERE sa.id_empleado = ?
              AND COALESCE(sa.estado, 'ACTIVA') = 'ACTIVA'
            ORDER BY s.fecha_solicitud DESC
        ", [$idEmpleado])->fetchAll();
    }

    /**
     * Tareas en un estado dado para un empleado según su rol.
     */
    public function porEstadoYRol(int $idEmpleado, string $estado, string $rol): array
    {
        $rolNorm = strtoupper($rol); // solicitud_asignacion.rol_asignacion es ENUM en MAYUS
        // PERF-002: reemplazado las 2 subqueries correlacionadas de archivo por
        // LEFT JOIN con subquery de agregación — de N*2 queries a 1 query fija.
        return $this->query("
            SELECT DISTINCT
                   t.id_tarea, t.id_solicitud,
                   s.tipo_solicitud, s.solicitud AS descripcion, s.prioridad,
                   s.codigo_documento,
                   s.codigo_documento AS nombre_documento,
                   te.tarea_estado    AS estado_actual,
                   te.fecha_tarea_estado AS fecha_estado,
                   td.tipo_documento, e.nombre_completo AS solicitante,
                   ar_max.id_archivo,
                   ar_max.nombre_original AS archivo_nombre
            FROM tarea t
            INNER JOIN solicitud      s  ON s.id_solicitud       = t.id_solicitud
            INNER JOIN tipo_documento td ON td.id_tipo_documento  = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado         = s.id_empleado
            INNER JOIN solicitud_asignacion sa
                    ON sa.id_solicitud    = s.id_solicitud
                   AND sa.id_empleado     = ?
                   AND sa.rol_asignacion  = ?
                   AND sa.estado          = 'ACTIVA'
                   AND sa.id = (
                       SELECT MAX(sa2.id) FROM solicitud_asignacion sa2
                       WHERE sa2.id_solicitud   = s.id_solicitud
                         AND sa2.id_empleado    = ?
                         AND sa2.rol_asignacion = ?
                         AND sa2.estado         = 'ACTIVA'
                   )
            INNER JOIN tarea_estado te ON te.id_tarea = t.id_tarea
                AND te.id_tarea_estado = (
                    SELECT MAX(te2.id_tarea_estado) FROM tarea_estado te2
                    WHERE te2.id_tarea = t.id_tarea
                )
            LEFT JOIN (
                SELECT ar.id_archivo, ar.nombre_original, ar.id_referencia
                FROM archivo ar
                INNER JOIN (
                    SELECT id_referencia, MAX(id_archivo) AS max_id
                    FROM archivo WHERE modulo = 'TAREA'
                    GROUP BY id_referencia
                ) top ON top.id_referencia = ar.id_referencia
                      AND top.max_id       = ar.id_archivo
                WHERE ar.modulo = 'TAREA'
            ) ar_max ON ar_max.id_referencia = t.id_tarea
            WHERE te.tarea_estado = ?
            ORDER BY s.fecha_solicitud DESC
        ", [$idEmpleado, $rolNorm, $idEmpleado, $rolNorm, $estado])->fetchAll();
    }

    /**
     * Obtener tarea con detalle completo.
     */
    public function detalle(int $idTarea): ?array
    {
        $tarea = $this->query("
            SELECT t.id_tarea, t.id_solicitud, t.estado_actual,
                   t.id_empleado_actual, t.fecha_inicio, t.fecha_limite, t.fecha_fin,
                   s.tipo_solicitud, s.solicitud AS descripcion, s.prioridad,
                   s.id_tipo_documento, s.codigo_documento,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   s.funcionario_asignado,
                   td.tipo_documento, td.sigla_tipo_documento,
                   e_sol.nombre_completo AS solicitante,
                   mp.macroproceso,
                   pr.proceso, pr.sigla_proceso,
                   sp.subproceso,
                   d.objetivo_documento
            FROM tarea t
            INNER JOIN solicitud      s     ON s.id_solicitud        = t.id_solicitud
            INNER JOIN tipo_documento td    ON td.id_tipo_documento   = s.id_tipo_documento
            INNER JOIN empleado       e_sol ON e_sol.id_empleado      = s.id_empleado
            LEFT  JOIN documento      d     ON d.codigo               = s.codigo_documento
            LEFT  JOIN proceso        pr    ON pr.id_proceso          = d.id_proceso
            LEFT  JOIN macroproceso   mp    ON mp.id_macroproceso     = pr.id_macroproceso
            LEFT  JOIN subproceso     sp    ON sp.id_subproceso       = d.id_subproceso
            WHERE t.id_tarea = ?
            LIMIT 1
        ", [$idTarea])->fetch();

        if (!$tarea) {
            return null;
        }

        $asigns = $this->query("
            SELECT sa.*, e.nombre_completo,
                   u.id_usuario
            FROM solicitud_asignacion sa
            INNER JOIN empleado e ON e.id_empleado = sa.id_empleado
            LEFT  JOIN usuario  u ON u.id_empleado = e.id_empleado
            WHERE sa.id_solicitud = ?
        ", [$tarea['id_solicitud']])->fetchAll();

        $tarea['elaborador']            = null;
        $tarea['elaborador_id_usuario'] = null;
        $tarea['revisor']               = null;
        $tarea['revisor_id_usuario']    = null;
        $tarea['aprobador']             = null;
        $tarea['aprobador_id_usuario']  = null;

        // HU-V01/V02: Para determinar elaborador y revisor usamos la última
        // asignación de cada rol (ACTIVA o COMPLETADA) — no solo ACTIVA, porque
        // al momento de aprobar ya están COMPLETADAS.
        // Para aprobador sí usamos solo ACTIVA (puede estar en proceso).
        foreach ($asigns as $a) {
            $rol    = strtolower($a['rol_asignacion'] ?? '');
            $estado = $a['estado'] ?? 'ACTIVA';

            // Elaborador: última COMPLETADA o ACTIVA (la más reciente gana)
            if ($rol === 'elaborador' && in_array($estado, ['ACTIVA','COMPLETADA'])) {
                if (!$tarea['elaborador'] || $estado === 'COMPLETADA') {
                    $tarea['elaborador']            = $a['nombre_completo'];
                    $tarea['elaborador_id_usuario'] = $a['id_usuario'] ?? null;
                }
            }
            // Revisor: misma lógica
            if ($rol === 'revisor' && in_array($estado, ['ACTIVA','COMPLETADA'])) {
                if (!$tarea['revisor'] || $estado === 'COMPLETADA') {
                    $tarea['revisor']            = $a['nombre_completo'];
                    $tarea['revisor_id_usuario'] = $a['id_usuario'] ?? null;
                }
            }
            // Aprobador: solo ACTIVA (quién está aprobando ahora)
            if ($rol === 'aprobador' && $estado === 'ACTIVA') {
                $tarea['aprobador']            = $a['nombre_completo'];
                $tarea['aprobador_id_usuario'] = $a['id_usuario'] ?? null;
            }
        }
        $tarea['asignaciones'] = $asigns; // historial completo para TareaService

        $tarea['estados'] = $this->query("
            SELECT te.*,
                   -- CA: alias para compatibilidad con _detalle_tarea.php
                   te.fecha_tarea_estado              AS fecha_estado,
                   COALESCE(te.comentario_estado,
                       te.usuario_tarea_estado)        AS descripcion,
                   -- Usuario normalizado (normalizacion FIX-03)
                   COALESCE(e.nombre_completo, te.usuario_tarea_estado) AS nombre_completo,
                   u.usuario
            FROM tarea_estado te
            LEFT JOIN usuario  u ON u.id_usuario  = te.id_usuario
            LEFT JOIN empleado e ON e.id_empleado = u.id_empleado
            WHERE te.id_tarea = ?
            ORDER BY te.id_tarea_estado ASC
        ", [$idTarea])->fetchAll();

        $tarea['archivos'] = $this->query("
            SELECT * FROM archivo
            WHERE modulo = 'TAREA' AND id_referencia = ?
        ", [$idTarea])->fetchAll();

        return $tarea;
    }

    /**
     * Estado actual de una tarea.
     */
    public function estadoActual(int $idTarea): ?array
    {
        return $this->query("
            SELECT * FROM tarea_estado
            WHERE id_tarea = ?
            ORDER BY id_tarea_estado DESC
            LIMIT 1
        ", [$idTarea])->fetch() ?: null;
    }

    /**
     * Tareas finalizadas de un empleado.
     */
    public function finalizadas(int $idEmpleado): array
    {
        return $this->query("
            SELECT DISTINCT t.id_tarea, t.id_solicitud,
                   s.tipo_solicitud, s.solicitud AS descripcion,
                   s.codigo_documento,
                   td.tipo_documento,
                   te.fecha_tarea_estado AS fecha_finalizacion,
                   e.nombre_completo AS solicitante
            FROM tarea t
            INNER JOIN solicitud s  ON s.id_solicitud = t.id_solicitud
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado e   ON e.id_empleado  = s.id_empleado
            INNER JOIN tarea_estado te ON te.id_tarea = t.id_tarea
                AND te.tarea_estado = 'FINALIZADO'
            INNER JOIN solicitud_asignacion sa ON sa.id_solicitud = s.id_solicitud
            WHERE sa.id_empleado = ?
            ORDER BY te.fecha_tarea_estado DESC
        ", [$idEmpleado])->fetchAll();
    }

    /**
     * Conteo de tareas pendientes del usuario (para KPI dashboard).
     */

    /**
     * Tareas finalizadas paginadas (PERF-001).
     */
    public function finalizadasPaginado(
        int     $idEmpleado,
        int     $pagina     = 1,
        int     $porPagina  = 50,
        ?string $desde      = null,
        ?string $hasta      = null
    ): array {
        $params = [$idEmpleado];
        $filtro = '';
        if ($desde) { $filtro .= " AND te.fecha_tarea_estado >= ?"; $params[] = $desde; }
        if ($hasta) { $filtro .= " AND te.fecha_tarea_estado <= ?"; $params[] = $hasta . ' 23:59:59'; }

        return $this->paginar("
            SELECT DISTINCT t.id_tarea, t.id_solicitud,
                   s.tipo_solicitud, s.solicitud AS descripcion,
                   s.codigo_documento,
                   td.tipo_documento,
                   te.fecha_tarea_estado AS fecha_finalizacion,
                   e.nombre_completo AS solicitante
            FROM tarea t
            INNER JOIN solicitud      s  ON s.id_solicitud      = t.id_solicitud
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            INNER JOIN tarea_estado   te ON te.id_tarea          = t.id_tarea
                AND te.tarea_estado = 'FINALIZADO'
            INNER JOIN solicitud_asignacion sa ON sa.id_solicitud = s.id_solicitud
            WHERE sa.id_empleado = ? {$filtro}
            ORDER BY te.fecha_tarea_estado DESC
        ", $params, $pagina, $porPagina);
    }

    public function pendientesUsuario(int $idEmpleado): int
    {
        return (int) $this->query("
            SELECT COUNT(DISTINCT t.id_tarea)
            FROM tarea t
            INNER JOIN solicitud_asignacion sa ON sa.id_solicitud = t.id_solicitud
                                              AND sa.id_empleado  = ?
            INNER JOIN tarea_estado te ON te.id_tarea = t.id_tarea
                AND te.id_tarea_estado = (
                    SELECT MAX(te2.id_tarea_estado) FROM tarea_estado te2
                    WHERE te2.id_tarea = t.id_tarea
                )
            WHERE te.tarea_estado NOT IN ('FINALIZADO')
        ", [$idEmpleado])->fetchColumn();
    }

    /** Conteo de tareas por estado para un empleado */
    public function resumenEstados(int $idEmpleado): array
    {
        $rows = $this->query(
            "SELECT estado_actual AS estado, COUNT(*) AS total
             FROM tarea WHERE id_empleado_actual = ?
             GROUP BY estado_actual",
            [$idEmpleado]
        )->fetchAll();
        return array_column($rows, 'total', 'estado');
    }

    /** Conteo global de tareas por estado */
    public function resumenEstadosGlobal(): array
    {
        $rows = $this->query(
            "SELECT estado_actual AS estado, COUNT(*) AS total
             FROM tarea GROUP BY estado_actual"
        )->fetchAll();
        return array_column($rows, 'total', 'estado');
    }

    /** Todas las tareas del sistema con datos de solicitud */
    public function todasLasTareas(): array
    {
        return $this->query("
            SELECT t.id_tarea, t.id_solicitud, t.estado_actual, t.id_empleado_actual,
                   s.tipo_solicitud, s.prioridad, s.codigo_documento,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   td.tipo_documento, td.sigla_tipo_documento,
                   e_res.nombre_completo  AS responsable_actual,
                   sa.rol_asignacion      AS rol_actual,
                   (SELECT te2.fecha_tarea_estado
                    FROM tarea_estado te2
                    WHERE te2.id_tarea = t.id_tarea
                    ORDER BY te2.id_tarea_estado DESC LIMIT 1
                   ) AS fecha_ultimo_estado
            FROM tarea t
            INNER JOIN solicitud      s    ON s.id_solicitud      = t.id_solicitud
            INNER JOIN tipo_documento td   ON td.id_tipo_documento = s.id_tipo_documento
            LEFT  JOIN documento      d    ON d.codigo             = s.codigo_documento
            LEFT  JOIN empleado       e_res ON e_res.id_empleado   = t.id_empleado_actual
            LEFT  JOIN solicitud_asignacion sa ON sa.id_solicitud  = s.id_solicitud
                                             AND sa.id_empleado    = t.id_empleado_actual
                                             AND sa.estado         = 'ACTIVA'
                                             AND sa.id = (
                                                 SELECT MAX(sa2.id) FROM solicitud_asignacion sa2
                                                 WHERE sa2.id_solicitud = s.id_solicitud
                                                   AND sa2.id_empleado  = t.id_empleado_actual
                                             )
            ORDER BY t.id_tarea DESC
        ")->fetchAll();
    }

    /** Tareas asignadas a un empleado */
    public function misTareas(int $idEmpleado): array
    {
        return $this->query("
            SELECT DISTINCT t.id_tarea, t.id_solicitud, t.estado_actual,
                   s.tipo_solicitud, s.prioridad, s.codigo_documento,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   td.tipo_documento,
                   e_sol.nombre_completo AS solicitante,
                   sa.rol_asignacion, sa.estado AS estado_asignacion,
                   (SELECT te2.fecha_tarea_estado
                    FROM tarea_estado te2
                    WHERE te2.id_tarea = t.id_tarea
                    ORDER BY te2.id_tarea_estado DESC LIMIT 1
                   ) AS fecha_ultimo_estado
            FROM tarea t
            INNER JOIN solicitud      s    ON s.id_solicitud       = t.id_solicitud
            INNER JOIN tipo_documento td   ON td.id_tipo_documento  = s.id_tipo_documento
            INNER JOIN empleado       e_sol ON e_sol.id_empleado    = s.id_empleado
            LEFT  JOIN documento      d    ON d.codigo              = s.codigo_documento
            LEFT  JOIN solicitud_asignacion sa ON sa.id_solicitud   = s.id_solicitud
                                             AND sa.id_empleado     = ?
                                             AND sa.estado          = 'ACTIVA'
            WHERE t.id_empleado_actual = ?
            ORDER BY t.id_tarea DESC
        ", [$idEmpleado, $idEmpleado])->fetchAll();
    }

}
