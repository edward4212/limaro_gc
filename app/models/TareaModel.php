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
        $this->query(
            "INSERT INTO tarea (id_solicitud) VALUES (?)",
            [$idSolicitud]
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
        ?string $ruta     = null,
        ?string $documento = null
    ): void {
        $this->query("
            INSERT INTO tarea_estado
                (id_tarea, usuario_tarea_estado, fecha_tarea_estado,
                 tarea_estado, ruta, documento_tarea)
            VALUES (?, ?, NOW(), ?, ?, ?)
        ", [$idTarea, $usuario, $estado, $ruta ?? '', $documento]);
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
        $this->agregarEstado($idTarea, $estado, $usuario . ' — ' . $comentario);
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
        $rolNorm = strtolower($rol);
        return $this->query("
            SELECT t.id_tarea, t.id_solicitud,
                   s.tipo_solicitud, s.solicitud AS descripcion, s.prioridad,
                   s.codigo_documento,
                   s.codigo_documento AS nombre_documento,
                   te.tarea_estado  AS estado_actual,
                   te.fecha_tarea_estado AS fecha_estado,
                   td.tipo_documento, e.nombre_completo AS solicitante,
                   ar.id_archivo, ar.nombre_original AS archivo_nombre
            FROM tarea t
            INNER JOIN solicitud      s  ON s.id_solicitud      = t.id_solicitud
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            INNER JOIN solicitud_asignacion sa
                    ON sa.id_solicitud = s.id_solicitud
                   AND sa.id_empleado  = ?
                   AND LOWER(sa.rol_asignacion) = ?
            INNER JOIN tarea_estado   te ON te.id_tarea = t.id_tarea
                AND te.id_tarea_estado = (
                    SELECT MAX(te2.id_tarea_estado) FROM tarea_estado te2
                    WHERE te2.id_tarea = t.id_tarea
                )
            LEFT  JOIN archivo ar ON ar.modulo = 'TAREA'
                                  AND ar.id_referencia = t.id_tarea
            WHERE te.tarea_estado = ?
            ORDER BY s.fecha_solicitud DESC
        ", [$idEmpleado, $rolNorm, $estado])->fetchAll();
    }

    /**
     * Obtener tarea con detalle completo.
     */
    public function detalle(int $idTarea): ?array
    {
        $tarea = $this->query("
            SELECT t.id_tarea, t.id_solicitud,
                   s.tipo_solicitud, s.solicitud AS descripcion, s.prioridad,
                   s.id_tipo_documento, s.codigo_documento,
                   s.codigo_documento AS nombre_documento,
                   s.funcionario_asignado,
                   td.tipo_documento,
                   e_sol.nombre_completo AS solicitante
            FROM tarea t
            INNER JOIN solicitud      s     ON s.id_solicitud      = t.id_solicitud
            INNER JOIN tipo_documento td    ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e_sol ON e_sol.id_empleado    = s.id_empleado
            WHERE t.id_tarea = ?
            LIMIT 1
        ", [$idTarea])->fetch();

        if (!$tarea) {
            return null;
        }

        $asigns = $this->query("
            SELECT sa.*, e.nombre_completo
            FROM solicitud_asignacion sa
            INNER JOIN empleado e ON e.id_empleado = sa.id_empleado
            WHERE sa.id_solicitud = ?
        ", [$tarea['id_solicitud']])->fetchAll();

        $tarea['elaborador'] = null;
        $tarea['revisor']    = null;
        $tarea['aprobador']  = null;
        foreach ($asigns as $a) {
            $rol = strtolower($a['rol_asignacion'] ?? '');
            if ($rol === 'elaborador') $tarea['elaborador'] = $a['nombre_completo'];
            if ($rol === 'revisor')    $tarea['revisor']    = $a['nombre_completo'];
            if ($rol === 'aprobador')  $tarea['aprobador']  = $a['nombre_completo'];
        }
        $tarea['asignaciones'] = $asigns;

        $tarea['estados'] = $this->query("
            SELECT te.id_tarea_estado, te.id_tarea,
                   te.usuario_tarea_estado,
                   te.fecha_tarea_estado,
                   te.tarea_estado, te.ruta, te.documento_tarea
            FROM tarea_estado te
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
}
