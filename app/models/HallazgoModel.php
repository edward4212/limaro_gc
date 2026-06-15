<?php
namespace App\Models;
use App\Core\Model;

class HallazgoModel extends Model
{
    protected string $table      = 'auditoria_hallazgo';
    protected string $primaryKey = 'id';

    /** CA-1: Listar todos los hallazgos con datos del programa */
    public function listar(?string $estado = null, ?string $tipo = null): array
    {
        $where  = '';
        $params = [];
        $conds  = [];

        if ($estado) { $conds[] = "h.estado = ?";       $params[] = $estado; }
        if ($tipo)   { $conds[] = "h.tipo = ?";         $params[] = $tipo; }
        if ($conds)  { $where = "WHERE " . implode(" AND ", $conds); }

        return $this->query("
            SELECT h.*,
                   p.descripcion AS programa,
                   p.anio,
                   p.auditor_lider,
                   p.id_auditor_lider,
                   COALESCE(ea.nombre_completo, p.auditor_lider) AS auditor_lider_nombre,
                   p.estado AS estado_programa,
                   COALESCE(er.nombre_completo, h.responsable) AS responsable_nombre,
                   ac.codigo AS codigo_ac,
                   ac.estado AS estado_ac
            FROM auditoria_hallazgo h
            INNER JOIN auditoria_programa p  ON p.id  = h.id_programa
            LEFT  JOIN accion_correctiva  ac ON ac.id = h.id_accion_correctiva
            LEFT  JOIN empleado er ON er.id_empleado = h.id_responsable
            LEFT  JOIN empleado ea ON ea.id_empleado = p.id_auditor_lider
            $where
            ORDER BY
                FIELD(h.estado,'ABIERTO','EN_TRATAMIENTO','CERRADO'),
                h.fecha_registro DESC
        ", $params)->fetchAll();
    }

    /** Detalle de un hallazgo */
    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT h.*,
                   p.descripcion AS programa,
                   p.anio, p.auditor_lider, p.id_auditor_lider,
                   COALESCE(ea.nombre_completo, p.auditor_lider) AS auditor_lider_nombre,
                   COALESCE(er.nombre_completo, h.responsable)   AS responsable_nombre,
                   ac.codigo AS codigo_ac, ac.estado AS estado_ac,
                   ac.descripcion_nc AS ac_descripcion
            FROM auditoria_hallazgo h
            INNER JOIN auditoria_programa p  ON p.id  = h.id_programa
            LEFT  JOIN accion_correctiva  ac ON ac.id = h.id_accion_correctiva
            LEFT  JOIN empleado er ON er.id_empleado = h.id_responsable
            LEFT  JOIN empleado ea ON ea.id_empleado = p.id_auditor_lider
            WHERE h.id = ?
            LIMIT 1
        ", [$id])->fetch() ?: null;
    }

    /** CA-2: Actualizar estado y campos del hallazgo */
    public function actualizarHallazgo(int $id, array $data): bool
    {
        $campos = array_filter([
            'estado'           => $data['estado']            ?? null,
            'accion_correctiva'=> $data['accion_correctiva'] ?? null,
            'responsable'      => $data['responsable']       ?? null,
            'fecha_cierre'     => !empty($data['fecha_cierre']) ? $data['fecha_cierre'] : null,
            'evidencia'        => $data['evidencia']          ?? null,
            'id_accion_correctiva' => !empty($data['id_accion_correctiva'])
                                    ? (int)$data['id_accion_correctiva'] : null,
        ], fn($v) => $v !== null);

        if (empty($campos)) return false;
        return $this->update($id, $campos);
    }

    /** Resumen de estados para el encabezado */
    public function resumen(): array
    {
        $rows = $this->query("
            SELECT estado, COUNT(*) AS total
            FROM auditoria_hallazgo
            GROUP BY estado
        ")->fetchAll();
        $r = ['ABIERTO' => 0, 'EN_TRATAMIENTO' => 0, 'CERRADO' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $r[$row['estado']] = (int)$row['total'];
            $r['total'] += (int)$row['total'];
        }
        return $r;
    }

    /** KPIs para el panel de hallazgos */
    public function kpis(): array
    {
        $row = $this->query("
            SELECT
                COUNT(*)                                       AS total,
                SUM(estado = 'ABIERTO')                        AS abiertos,
                SUM(estado = 'EN_PROCESO')                     AS en_proceso,
                SUM(estado = 'CERRADO')                        AS cerrados,
                SUM(tipo = 'NO_CONFORMIDAD')                   AS no_conformidades,
                SUM(tipo = 'OBSERVACION')                      AS observaciones,
                SUM(tipo = 'OPORTUNIDAD')                      AS oportunidades,
                SUM(tipo = 'FORTALEZA')                        AS fortalezas,
                SUM(estado='ABIERTO' AND fecha_cierre < CURDATE()) AS vencidos
            FROM auditoria_hallazgo
        ")->fetch();
        return $row ?: [];
    }

    /** Hallazgos agrupados por proceso */
    public function porProceso(): array
    {
        return $this->query("
            SELECT h.proceso_auditado, h.id_proceso,
                   pr.proceso AS proceso_nombre,
                   COUNT(*)                      AS total,
                   SUM(h.estado='ABIERTO')        AS abiertos,
                   SUM(h.estado='CERRADO')        AS cerrados
            FROM auditoria_hallazgo h
            LEFT JOIN proceso pr ON pr.id_proceso = h.id_proceso
            GROUP BY h.proceso_auditado, h.id_proceso, pr.proceso
            ORDER BY total DESC
        ")->fetchAll();
    }

    /** Cambiar estado inline */
    public function cambiarEstado(int $id, string $estado): void
    {
        $data = ['estado' => $estado];
        if ($estado === 'CERRADO') $data['fecha_cierre'] = date('Y-m-d');
        $this->update($id, $data);
    }

    /** Listar con filtros avanzados */
    public function listarFiltrado(array $f = []): array
    {
        $where = ['1=1']; $p = [];
        if (!empty($f['estado']))  { $where[] = 'h.estado = ?';  $p[] = $f['estado']; }
        if (!empty($f['tipo']))    { $where[] = 'h.tipo = ?';    $p[] = $f['tipo']; }
        if (!empty($f['id_plan'])) { $where[] = 'h.id_plan = ?'; $p[] = $f['id_plan']; }
        if (!empty($f['proceso'])) { $where[] = 'h.proceso_auditado LIKE ?'; $p[] = '%'.$f['proceso'].'%'; }

        return $this->query("
            SELECT h.*,
                   p.codigo  AS programa_codigo,
                   pl.codigo AS plan_codigo,
                   pr.proceso AS proceso_nombre,
                   e.nombre_completo AS responsable_nombre,
                   ac.codigo AS ac_codigo
            FROM auditoria_hallazgo h
            LEFT JOIN auditoria_programa p  ON p.id  = h.id_programa
            LEFT JOIN auditoria_plan     pl ON pl.id = h.id_plan
            LEFT JOIN proceso pr ON pr.id_proceso = h.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = h.id_responsable
            LEFT JOIN accion_correctiva ac ON ac.id = h.id_accion_correctiva
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                FIELD(h.estado,'ABIERTO','EN_PROCESO','CERRADO'),
                h.fecha_registro DESC
        ", $p)->fetchAll();
    }


    /** Alias de insert() para compatibilidad con versiones anteriores */
    public function create(array $data): int
    {
        return $this->insert($data);
    }

}