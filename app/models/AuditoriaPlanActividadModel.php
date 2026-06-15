<?php
namespace App\Models;
use App\Core\{Model, Database};

class AuditoriaPlanActividadModel extends Model
{
    protected string $table = 'auditoria_plan_actividad';

    public function porPlan(int $idPlan): array
    {
        return $this->query("
            SELECT a.*,
                   p.proceso AS proceso_nombre,
                   e.nombre_completo AS auditor_nombre_completo
            FROM auditoria_plan_actividad a
            LEFT JOIN proceso  p ON p.id_proceso  = a.id_proceso_actividad
            LEFT JOIN empleado e ON e.id_empleado = a.id_auditor
            WHERE a.id_plan = ?
            ORDER BY a.fecha ASC, a.hora_inicio ASC, a.orden ASC
        ", [$idPlan])->fetchAll();
    }

    public function calcularDuracion(?string $horaInicio, ?string $horaFin): ?int
    {
        if (!$horaInicio || !$horaFin) return null;
        $ini = strtotime("1970-01-01 $horaInicio");
        $fin = strtotime("1970-01-01 $horaFin");
        return $fin > $ini ? (int)(($fin - $ini) / 60) : null;
    }
}
