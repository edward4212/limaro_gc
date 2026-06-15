<?php
namespace App\Models;
use App\Core\Model;

/**
 * Modelo centralizado de Reportes.
 * Cada método retorna los datos para un reporte específico.
 */
class ReporteModel extends Model
{
    protected string $table      = 'documento';
    protected string $primaryKey = 'id_documento';

    // ═══════════════════════════════════════════════
    // DOCUMENTOS
    // ═══════════════════════════════════════════════

    public function listadoMaestroVigentes(array $filtros = []): array
    {
        $where = ["v.estado_version = 'VIGENTE'", "COALESCE(d.estado,'ACTIVO') = 'ACTIVO'"];
        $params = [];

        if (!empty($filtros['id_proceso'])) {
            $where[]  = 'd.id_proceso = ?';
            $params[] = $filtros['id_proceso'];
        }
        if (!empty($filtros['id_tipo'])) {
            $where[]  = 'd.id_tipo_documento = ?';
            $params[] = $filtros['id_tipo'];
        }
        if (!empty($filtros['desde'])) {
            $where[]  = 'v.fecha_aprobacion >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]  = 'v.fecha_aprobacion <= ?';
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }

        $sql = "SELECT d.codigo, d.nombre_documento,
                   td.tipo_documento, td.sigla_tipo_documento,
                   m.macroproceso, p.proceso, p.sigla_proceso,
                   s.subproceso AS nombre_subproceso,
                   v.numero_version, v.usuario_creacion AS elaboro,
                   v.usuario_revision AS reviso,
                   v.usuario_aprobacion AS aprobo,
                   v.fecha_creacion, v.fecha_aprobacion,
                   IF(v.documento IS NOT NULL AND v.documento != '', 'Sí', 'No') AS tiene_archivo
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso   = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento= d.id_tipo_documento
            LEFT  JOIN subproceso     s  ON s.id_subproceso     = d.id_subproceso
            INNER JOIN versionamiento v  ON v.id_documento      = d.id_documento
                AND v.estado_version = 'VIGENTE'
                AND v.numero_version = (
                    SELECT MAX(v2.numero_version) FROM versionamiento v2
                    WHERE v2.id_documento = d.id_documento AND v2.estado_version = 'VIGENTE'
                )
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.macroproceso, p.proceso, d.codigo";

        return $this->query($sql, $params)->fetchAll();
    }

    public function listadoMaestroObsoletos(array $filtros = []): array
    {
        $where  = ["v.estado_version = 'OBSOLETO'"];
        $params = [];

        if (!empty($filtros['desde'])) {
            $where[]  = 'v.fecha_obsoleto >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]  = 'v.fecha_obsoleto <= ?';
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }

        $sql = "SELECT d.codigo, d.nombre_documento,
                   td.sigla_tipo_documento, m.macroproceso, p.proceso,
                   v.numero_version, v.fecha_aprobacion, v.fecha_obsoleto,
                   v.usuario_aprobacion AS aprobo
            FROM documento d
            INNER JOIN proceso        p  ON p.id_proceso        = d.id_proceso
            INNER JOIN macroproceso   m  ON m.id_macroproceso   = p.id_macroproceso
            INNER JOIN tipo_documento td ON td.id_tipo_documento= d.id_tipo_documento
            INNER JOIN versionamiento v  ON v.id_documento      = d.id_documento
            WHERE " . implode(' AND ', $where) . "
            ORDER BY v.fecha_obsoleto DESC";

        return $this->query($sql, $params)->fetchAll();
    }

    public function documentosPorProceso(): array
    {
        return $this->query("
            SELECT m.macroproceso, p.proceso,
                   COUNT(DISTINCT d.id_documento) AS total,
                   SUM(CASE WHEN ev.estado_version = 'VIGENTE' THEN 1 ELSE 0 END) AS vigentes,
                   SUM(CASE WHEN ev.estado_version = 'OBSOLETO' THEN 1 ELSE 0 END) AS obsoletos,
                   SUM(CASE WHEN ev.estado_version = 'CREADO' THEN 1 ELSE 0 END) AS en_creacion
            FROM proceso p
            INNER JOIN macroproceso m ON m.id_macroproceso = p.id_macroproceso
            LEFT  JOIN documento    d ON d.id_proceso      = p.id_proceso
                AND COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
            LEFT  JOIN versionamiento ev ON ev.id_documento = d.id_documento
                AND ev.numero_version = (
                    SELECT MAX(v2.numero_version) FROM versionamiento v2
                    WHERE v2.id_documento = d.id_documento
                )
            WHERE p.estado = 'ACTIVO'
            GROUP BY p.id_proceso
            ORDER BY m.macroproceso, p.proceso
        ")->fetchAll();
    }

    public function versionamientoHistorial(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['id_proceso'])) {
            $where[]  = 'd.id_proceso = ?';
            $params[] = $filtros['id_proceso'];
        }
        if (!empty($filtros['desde'])) {
            $where[]  = 'v.fecha_creacion >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]  = 'v.fecha_creacion <= ?';
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }

        return $this->query("
            SELECT d.codigo, d.nombre_documento, p.proceso,
                   v.numero_version, v.estado_version,
                   v.descripcion_version, v.usuario_creacion,
                   v.usuario_revision, v.usuario_aprobacion,
                   v.fecha_creacion, v.fecha_aprobacion, v.fecha_obsoleto
            FROM versionamiento v
            INNER JOIN documento d ON d.id_documento = v.id_documento
            INNER JOIN proceso   p ON p.id_proceso   = d.id_proceso
            WHERE " . implode(' AND ', $where) . "
            ORDER BY d.codigo, v.numero_version DESC
        ", $params)->fetchAll();
    }

    // ═══════════════════════════════════════════════
    // SOLICITUDES
    // ═══════════════════════════════════════════════

    public function solicitudesPorEstado(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[]  = 's.estado_solicitud = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['tipo'])) {
            $where[]  = 's.tipo_solicitud = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['desde'])) {
            $where[]  = 's.fecha_solicitud >= ?';
            $params[] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[]  = 's.fecha_solicitud <= ?';
            $params[] = $filtros['hasta'] . ' 23:59:59';
        }

        return $this->query("
            SELECT s.id_solicitud, s.tipo_solicitud, s.prioridad,
                   s.estado_solicitud, s.codigo_documento,
                   s.fecha_solicitud, s.fecha_solucion,
                   e.nombre_completo AS solicitante,
                   td.tipo_documento,
                   TIMESTAMPDIFF(HOUR, s.fecha_solicitud,
                       COALESCE(s.fecha_solucion, NOW())) AS horas_transcurridas,
                   s.funcionario_asignado
            FROM solicitud s
            INNER JOIN empleado      e  ON e.id_empleado      = s.id_empleado
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            WHERE " . implode(' AND ', $where) . "
            ORDER BY s.fecha_solicitud DESC
        ", $params)->fetchAll();
    }

    public function resumenSolicitudes(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filtros['desde'])) { $where[] = 's.fecha_solicitud >= ?'; $params[] = $filtros['desde']; }
        if (!empty($filtros['hasta'])) { $where[] = 's.fecha_solicitud <= ?'; $params[] = $filtros['hasta'] . ' 23:59:59'; }

        return $this->query("
            SELECT s.estado_solicitud AS estado,
                   s.tipo_solicitud   AS tipo,
                   COUNT(*) AS total,
                   AVG(TIMESTAMPDIFF(HOUR, s.fecha_solicitud,
                       COALESCE(s.fecha_solucion, NOW()))) AS promedio_horas
            FROM solicitud s
            WHERE " . implode(' AND ', $where) . "
            GROUP BY s.estado_solicitud, s.tipo_solicitud
            ORDER BY total DESC
        ", $params)->fetchAll();
    }

    // ═══════════════════════════════════════════════
    // TAREAS
    // ═══════════════════════════════════════════════

    public function tareasPorEstado(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filtros['estado'])) { $where[] = 'te.tarea_estado = ?'; $params[] = $filtros['estado']; }
        if (!empty($filtros['desde']))  { $where[] = 'te_ini.fecha_tarea_estado >= ?'; $params[] = $filtros['desde']; }
        if (!empty($filtros['hasta']))  { $where[] = 'te_ini.fecha_tarea_estado <= ?'; $params[] = $filtros['hasta'] . ' 23:59:59'; }

        return $this->query("
            SELECT t.id_tarea, s.id_solicitud, s.tipo_solicitud,
                   s.codigo_documento, s.funcionario_asignado,
                   COALESCE(d.nombre_documento, s.codigo_documento) AS nombre_documento,
                   td.tipo_documento,
                   te.tarea_estado, te.usuario_tarea_estado,
                   te.fecha_tarea_estado,
                   te_ini.fecha_tarea_estado              AS fecha_creacion,
                   e.nombre_completo                      AS solicitante,
                   TIMESTAMPDIFF(MINUTE,
                       te_ini.fecha_tarea_estado,
                       COALESCE(te_fin.fecha_tarea_estado, NOW())
                   )                                      AS minutos_transcurridos,
                   ROUND(
                       TIMESTAMPDIFF(MINUTE,
                           te_ini.fecha_tarea_estado,
                           COALESCE(te_fin.fecha_tarea_estado, NOW())
                       ) / 60.0, 1)                       AS horas_transcurridas,
                   ROUND(
                       TIMESTAMPDIFF(MINUTE,
                           te_ini.fecha_tarea_estado,
                           COALESCE(te_fin.fecha_tarea_estado, NOW())
                       ) / 1440.0, 2)                     AS dias_calendario
            FROM tarea t
            INNER JOIN solicitud      s    ON s.id_solicitud      = t.id_solicitud
            INNER JOIN empleado       e    ON e.id_empleado        = s.id_empleado
            INNER JOIN tipo_documento td   ON td.id_tipo_documento = s.id_tipo_documento
            LEFT  JOIN documento      d    ON d.codigo             = s.codigo_documento
            -- Estado actual (último)
            INNER JOIN tarea_estado   te   ON te.id_tarea = t.id_tarea
                AND te.id_tarea_estado = (
                    SELECT MAX(te2.id_tarea_estado)
                    FROM tarea_estado te2 WHERE te2.id_tarea = t.id_tarea
                )
            -- Estado inicial (creación)
            LEFT  JOIN tarea_estado   te_ini ON te_ini.id_tarea = t.id_tarea
                AND te_ini.id_tarea_estado = (
                    SELECT MIN(te3.id_tarea_estado)
                    FROM tarea_estado te3 WHERE te3.id_tarea = t.id_tarea
                )
            -- Estado final (si finalizado)
            LEFT  JOIN tarea_estado   te_fin ON te_fin.id_tarea = t.id_tarea
                AND te_fin.tarea_estado = 'FINALIZADO'
                AND te_fin.id_tarea_estado = (
                    SELECT MAX(te4.id_tarea_estado)
                    FROM tarea_estado te4
                    WHERE te4.id_tarea = t.id_tarea AND te4.tarea_estado = 'FINALIZADO'
                )
            WHERE " . implode(' AND ', $where) . "
            ORDER BY te.fecha_tarea_estado DESC
        ", $params)->fetchAll();
    }

    // ═══════════════════════════════════════════════
    // SGC - CALIDAD
    // ═══════════════════════════════════════════════

    public function cumplimientoObjetivos(string $anio = ''): array
    {
        // Filtro de año en el ON para NO convertir el LEFT JOIN en INNER JOIN
        // Se usa parámetro preparado para evitar inyección SQL
        $joinCond = $anio
            ? "ON om.id_objetivo = oc.id AND om.periodo LIKE ?"
            : "ON om.id_objetivo = oc.id";
        $params = $anio ? ["{$anio}%"] : [];

        return $this->query("
            SELECT oc.id, oc.codigo, oc.objetivo, oc.meta, oc.frecuencia,
                   COALESCE(e.nombre_completo, '') AS responsable,
                   oc.id_proceso,
                   COALESCE(p.proceso, '—') AS proceso,
                   COUNT(om.id)                                        AS total_mediciones,
                   COALESCE(SUM(om.cumple), 0)                        AS mediciones_cumplidas,
                   ROUND(AVG(om.valor_obtenido), 2)                   AS promedio_obtenido,
                   ROUND(AVG(om.valor_meta), 2)                       AS promedio_meta,
                   ROUND(SUM(om.cumple) / NULLIF(COUNT(om.id),0) * 100, 1) AS pct_cumplimiento
            FROM objetivo_calidad oc
            LEFT JOIN objetivo_medicion om $joinCond
            LEFT JOIN proceso p ON p.id_proceso = oc.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = oc.id_responsable
            WHERE oc.estado = 'ACTIVO'
            GROUP BY oc.id
            ORDER BY oc.codigo
        ", $params)->fetchAll();
    }

    public function hallazgosAuditoria(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filtros['anio']))   { $where[] = 'ap.anio = ?'; $params[] = $filtros['anio']; }
        if (!empty($filtros['tipo']))   { $where[] = 'h.tipo = ?';  $params[] = $filtros['tipo']; }
        if (!empty($filtros['estado'])) { $where[] = 'h.estado = ?';$params[] = $filtros['estado']; }

        return $this->query("
            SELECT ap.anio, ap.descripcion AS programa,
                   h.tipo, h.clausula_iso, h.proceso_auditado,
                   h.descripcion,
                   COALESCE(er.nombre_completo, h.responsable) AS responsable,
                   h.fecha_cierre, h.estado,
                   h.accion_correctiva
            FROM auditoria_hallazgo h
            INNER JOIN auditoria_programa ap ON ap.id = h.id_programa
            LEFT  JOIN empleado er ON er.id_empleado = h.id_responsable
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ap.anio DESC, h.tipo, h.estado
        ", $params)->fetchAll();
    }

    public function accionesCorrectivas(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filtros['estado'])) { $where[] = 'ac.estado = ?'; $params[] = $filtros['estado']; }
        if (!empty($filtros['origen'])) { $where[] = 'ac.origen = ?'; $params[] = $filtros['origen']; }
        if (!empty($filtros['anio']))   { $where[] = 'YEAR(ac.fecha_registro) = ?'; $params[] = $filtros['anio']; }

        return $this->query("
            SELECT ac.codigo, ac.origen, ac.descripcion_nc,
                   COALESCE(e.nombre_completo, ac.responsable) AS responsable, ac.fecha_planificada, ac.fecha_cierre,
                   ac.estado, ac.eficaz,
                   DATEDIFF(COALESCE(ac.fecha_cierre, CURDATE()), ac.fecha_planificada) AS dias_diferencia,
                   ac.causa_raiz, ac.accion_correctiva
            FROM accion_correctiva ac
            LEFT JOIN empleado e ON e.id_empleado = ac.id_responsable
            WHERE " . implode(' AND ', $where) . "
            ORDER BY ac.estado, ac.fecha_planificada
        ", $params)->fetchAll();
    }

    // ═══════════════════════════════════════════════
    // SEGURIDAD / AUDITORÍA
    // ═══════════════════════════════════════════════

    public function auditoriaLogin(array $filtros = []): array
    {
        $where  = ['1=1'];
        $params = [];
        if (!empty($filtros['usuario'])) { $where[] = 'al.usuario LIKE ?'; $params[] = '%'.$filtros['usuario'].'%'; }
        if (!empty($filtros['resultado'])) { $where[] = 'al.resultado = ?'; $params[] = $filtros['resultado']; }
        if (!empty($filtros['desde']))   { $where[] = 'al.fecha >= ?'; $params[] = $filtros['desde']; }
        if (!empty($filtros['hasta']))   { $where[] = 'al.fecha <= ?'; $params[] = $filtros['hasta'] . ' 23:59:59'; }

        return $this->query("
            SELECT al.usuario, al.ip, al.resultado, al.fecha,
                   e.nombre_completo
            FROM auditoria_login al
            LEFT JOIN usuario  u ON u.usuario    = al.usuario
            LEFT JOIN empleado e ON e.id_empleado = u.id_empleado
            WHERE " . implode(' AND ', $where) . "
            ORDER BY al.fecha DESC
            LIMIT 1000
        ", $params)->fetchAll();
    }

    public function resumenEjecutivo(): array
    {
        return [
            'documentos' => $this->query("
                SELECT
                    COUNT(DISTINCT d.id_documento) AS total_documentos,
                    SUM(CASE WHEN v.estado_version='VIGENTE' THEN 1 ELSE 0 END) AS vigentes,
                    SUM(CASE WHEN v.estado_version='OBSOLETO' THEN 1 ELSE 0 END) AS obsoletos,
                    SUM(CASE WHEN v.estado_version='CREADO' THEN 1 ELSE 0 END) AS en_creacion,
                    COUNT(DISTINCT d.id_proceso) AS procesos_con_docs
                FROM documento d
                LEFT JOIN versionamiento v ON v.id_documento = d.id_documento
                    AND v.numero_version = (SELECT MAX(v2.numero_version) FROM versionamiento v2 WHERE v2.id_documento = d.id_documento)
                WHERE COALESCE(d.estado,'ACTIVO') = 'ACTIVO'
            ")->fetch(),

            'solicitudes' => $this->query("
                SELECT
                    COUNT(*) AS total,
                    SUM(estado_solicitud='CREADA') AS creadas,
                    SUM(estado_solicitud='EN_DESARROLLO') AS en_desarrollo,
                    SUM(estado_solicitud='FINALIZADA') AS finalizadas,
                    ROUND(AVG(TIMESTAMPDIFF(HOUR,fecha_solicitud,COALESCE(fecha_solucion,NOW()))),1) AS promedio_horas
                FROM solicitud
                WHERE fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            ")->fetch(),

            'sgc' => $this->query("
                SELECT
                    (SELECT COUNT(*) FROM objetivo_calidad WHERE estado='ACTIVO') AS objetivos_activos,
                    (SELECT ROUND(AVG(cumple)*100,1) FROM objetivo_medicion WHERE periodo LIKE ?) AS pct_cumplimiento_obj,
                    (SELECT COUNT(*) FROM auditoria_hallazgo WHERE estado != 'CERRADO') AS hallazgos_abiertos,
                    (SELECT COUNT(*) FROM accion_correctiva WHERE estado NOT IN ('CERRADA','CANCELADA')) AS ac_abiertas,
                    (SELECT COUNT(*) FROM accion_correctiva
                     WHERE fecha_planificada < CURDATE() AND estado NOT IN ('CERRADA','CANCELADA')) AS ac_vencidas
            ", [date('Y') . '%'])->fetch(),
        ];
    }

    /** HU-042: Reporte estado de usuarios */
    public function reporteUsuarios(): array
    {
        return $this->query("
            SELECT u.id_usuario, u.usuario,
                   COALESCE(e.nombre_completo, u.usuario) AS nombre_completo,
                   u.estado, u.fecha_creacion, u.fecha_activacion,
                   u.fecha_vencimiento, u.ultimo_login,
                   u.intentos_fallidos, u.bloqueado_hasta,
                   u.fecha_cambio_clave,
                   GROUP_CONCAT(r.rol ORDER BY r.rol SEPARATOR ', ') AS roles
            FROM usuario u
            LEFT JOIN empleado   e  ON e.id_empleado = u.id_empleado
            LEFT JOIN usuario_rol ur ON ur.id_usuario = u.id_usuario
            LEFT JOIN rol         r  ON r.id_rol      = ur.id_rol
            GROUP BY u.id_usuario
            ORDER BY u.estado, e.nombre_completo
        ")->fetchAll();
    }

    /** HU-042: Reporte historial de cambios de contraseña */
    public function reporteContrasenas(): array
    {
        return $this->query("
            SELECT u.usuario,
                   COALESCE(e.nombre_completo, u.usuario) AS nombre_completo,
                   u.fecha_creacion      AS fecha_registro,
                   u.fecha_cambio_clave  AS ultimo_cambio,
                   u.clave_requiere_reset AS requiere_reset,
                   u.estado
            FROM usuario u
            LEFT JOIN empleado e ON e.id_empleado = u.id_empleado
            ORDER BY u.fecha_cambio_clave DESC
        ")->fetchAll();
    }

}