<?php
namespace App\Models;
use App\Core\{Model, Database};

class AuditoriaProgramaModel extends Model
{
    protected string $table = 'auditoria_programa';

    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $row  = $this->query(
            "SELECT COUNT(*) AS total FROM auditoria_programa WHERE anio = ?", [$anio]
        )->fetch();
        return 'PG-' . $anio . '-' . str_pad((int)($row['total']??0)+1, 3, '0', STR_PAD_LEFT);
    }

    public function listar(array $filtros = []): array
    {
        $where = ['1=1']; $params = [];
        if (!empty($filtros['anio']))   { $where[] = 'p.anio = ?';    $params[] = $filtros['anio']; }
        if (!empty($filtros['estado'])) { $where[] = 'p.estado = ?';  $params[] = $filtros['estado']; }
        if (!empty($filtros['id_plan']))  { $where[] = 'p.id_plan = ?'; $params[] = $filtros['id_plan']; }

        return $this->query("
            SELECT p.*,
                   e.nombre_completo AS auditor_nombre,
                   pl.codigo AS plan_codigo, pl.titulo AS plan_titulo,
                   pl.estado AS plan_estado,
                   pr.proceso AS proceso_nombre
            FROM auditoria_programa p
            LEFT JOIN empleado e ON e.id_empleado = p.id_auditor_lider
            LEFT JOIN auditoria_plan pl ON pl.id = p.id_plan
            LEFT JOIN proceso pr ON pr.id_proceso = p.id_proceso
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.anio DESC, p.id DESC
        ", $params)->fetchAll();
    }

    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT p.*,
                   e.nombre_completo  AS auditor_nombre,
                   pl.codigo AS plan_codigo, pl.titulo AS plan_titulo,
                   pl.estado AS plan_estado,
                   pr.proceso AS proceso_nombre
            FROM auditoria_programa p
            LEFT JOIN empleado e ON e.id_empleado = p.id_auditor_lider
            LEFT JOIN auditoria_plan pl ON pl.id = p.id_plan
            LEFT JOIN proceso pr ON pr.id_proceso = p.id_proceso
            WHERE p.id = ?
        ", [$id])->fetch() ?: null;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->query("UPDATE auditoria_programa SET estado = ? WHERE id = ?", [$estado, $id]);
    }

    /** IDs de planes que ya tienen programa asignado */
    public function planesUsados(): array
    {
        $rows = $this->query(
            "SELECT id_plan FROM auditoria_programa WHERE id_plan IS NOT NULL"
        )->fetchAll();
        return array_column($rows, 'id_plan');
    }

    /** Verificar si un plan ya tiene programa */
    public function planYaTienePrograma(int $idPlan): ?array
    {
        return $this->query(
            "SELECT id FROM auditoria_programa WHERE id_plan = ? LIMIT 1",
            [$idPlan]
        )->fetch() ?: null;
    }


    /** Programas disponibles para informe: plan vinculado APROBADO */
    public function conPlanAprobado(): array
    {
        return $this->query("
            SELECT p.*,
                   pl.codigo AS plan_codigo, pl.titulo AS plan_titulo,
                   pl.estado AS plan_estado, pl.tipo_auditoria AS plan_tipo,
                   pl.alcance AS plan_alcance, pl.id_auditor_lider AS plan_id_auditor,
                   e.nombre_completo AS auditor_nombre
            FROM auditoria_programa p
            INNER JOIN auditoria_plan pl ON pl.id = p.id_plan
            LEFT JOIN empleado e ON e.id_empleado = pl.id_auditor_lider
            WHERE pl.estado = 'APROBADO'
              AND NOT EXISTS (
                  SELECT 1 FROM auditoria_informe inf WHERE inf.id_programa = p.id
              )
            ORDER BY p.anio DESC, p.id DESC
        ")->fetchAll();
    }


    /**
     * Programas con plan APROBADO/EN_CURSO/FINALIZADO — para el formulario de hallazgos.
     * Eliminado de HallazgoController::crear() (era SQL directo).
     */
    public function conPlanAprobadoParaHallazgo(): array
    {
        return $this->query("
            SELECT pg.id, pg.codigo AS programa_codigo,
                   pl.codigo AS plan_codigo, pl.titulo AS plan_titulo,
                   pl.id AS id_plan,
                   inf.codigo AS informe_codigo
            FROM auditoria_programa pg
            INNER JOIN auditoria_plan pl ON pl.id = pg.id_plan
            LEFT  JOIN auditoria_informe inf ON inf.id_programa = pg.id
            WHERE pl.estado IN ('APROBADO','EN_CURSO','FINALIZADO')
            ORDER BY pg.anio DESC, pg.id DESC
        ")->fetchAll();
    }

}