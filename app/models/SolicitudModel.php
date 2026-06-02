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

    public function misRadicadas(int $idEmpleado): array
    {
        return $this->query("
            SELECT s.*, td.tipo_documento,
                   s.codigo_documento,
                   s.codigo_documento AS nombre_documento
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
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
                   s.codigo_documento AS nombre_documento
            FROM solicitud s
            INNER JOIN tipo_documento td ON td.id_tipo_documento = s.id_tipo_documento
            INNER JOIN empleado       e  ON e.id_empleado        = s.id_empleado
            WHERE s.estado_solicitud = ?
            ORDER BY s.fecha_solicitud DESC
        ", [$estado])->fetchAll();
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
                   s.codigo_documento AS nombre_documento,
                   s.funcionario_asignado AS asignado_a
            FROM solicitud s
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
        $this->query("
            INSERT INTO solicitud_asignacion
                (id_solicitud, id_empleado, rol_asignacion, asignado_por)
            VALUES (?, ?, ?, ?)
        ", [$idSolicitud, $idEmpleado, $rolAsignacion, $asignadoPor]);

        $nombre = $this->query(
            "SELECT nombre_completo FROM empleado WHERE id_empleado = ?",
            [$idEmpleado]
        )->fetchColumn() ?: (string)$idEmpleado;

        $this->query("
            UPDATE solicitud
            SET estado_solicitud     = 'ASIGNADA',
                funcionario_asignado = ?,
                fecha_asignacion     = NOW()
            WHERE id_solicitud = ?
        ", [$nombre, $idSolicitud]);
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
}
