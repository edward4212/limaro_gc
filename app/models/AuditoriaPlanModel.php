<?php
namespace App\Models;
use App\Core\Model;

class AuditoriaPlanModel extends Model
{
    protected string $table = 'auditoria_plan';

    /** Siguiente código PAI-YYYY-NNN */
    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $row  = $this->query(
            "SELECT COUNT(*) AS total FROM auditoria_plan WHERE anio = ?",
            [$anio]
        )->fetch();
        $n = (int)($row['total'] ?? 0) + 1;
        return "PL-{$anio}-" . str_pad($n, 3, '0', STR_PAD_LEFT);
    }

    /** Listar con filtros */
    public function listar(array $filtros = []): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filtros['anio']))   { $where[] = 'p.anio = ?';    $params[] = $filtros['anio']; }
        if (!empty($filtros['estado'])) { $where[] = 'p.estado = ?';  $params[] = $filtros['estado']; }

        return $this->query("
            SELECT p.*,
                   e.nombre_completo AS auditor_nombre,
                   (SELECT COUNT(*) FROM auditoria_plan_actividad a
                    WHERE a.id_plan = p.id) AS total_actividades,
                   (SELECT COUNT(*) FROM auditoria_plan_actividad a
                    WHERE a.id_plan = p.id AND a.estado = 'COMPLETADA') AS actividades_completadas,
                   GROUP_CONCAT(pr.proceso ORDER BY pr.proceso SEPARATOR ', ') AS procesos
            FROM auditoria_plan p
            LEFT JOIN empleado e   ON e.id_empleado = p.id_auditor_lider
            LEFT JOIN auditoria_plan_proceso pp ON pp.id_plan = p.id
            LEFT JOIN proceso pr ON pr.id_proceso = pp.id_proceso
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.id
            ORDER BY p.anio DESC, p.id DESC
        ", $params)->fetchAll();
    }

    /** Detalle con procesos */
    public function detalle(int $id): ?array
    {
        $plan = $this->query("
            SELECT p.*, e.nombre_completo AS auditor_nombre
            FROM auditoria_plan p
            LEFT JOIN empleado e ON e.id_empleado = p.id_auditor_lider
            WHERE p.id = ?
        ", [$id])->fetch() ?: null;

        if (!$plan) return null;

        $plan['procesos'] = $this->query("
            SELECT pp.id_proceso, pr.proceso, pr.sigla_proceso
            FROM auditoria_plan_proceso pp
            INNER JOIN proceso pr ON pr.id_proceso = pp.id_proceso
            WHERE pp.id_plan = ?
        ", [$id])->fetchAll();

        return $plan;
    }

    /** Guardar procesos auditados */
    public function guardarProcesos(int $idPlan, array $idProcesos): void
    {
        $db = \App\Core\Database::getInstance();
        $db->prepare("DELETE FROM auditoria_plan_proceso WHERE id_plan = ?")->execute([$idPlan]);
        foreach ($idProcesos as $idP) {
            $db->prepare("INSERT IGNORE INTO auditoria_plan_proceso (id_plan, id_proceso) VALUES (?,?)")
               ->execute([$idPlan, (int)$idP]);
        }
    }

    /** Cambiar estado con validación de rol */
    public function cambiarEstado(int $id, string $estado): bool
    {
        return (bool)$this->query(
            "UPDATE auditoria_plan SET estado = ? WHERE id = ?",
            [$estado, $id]
        );
    }

    /** Buscar programa asociado a un plan */
    public function programaDelPlan(int $idPlan): ?array
    {
        $r = $this->query(
            "SELECT id, codigo, estado, id_plan FROM auditoria_programa WHERE id_plan = ? LIMIT 1",
            [$idPlan]
        )->fetch();
        return $r ?: null;
    }

    /** Sincronizar estado del programa con el estado del plan */
    public function sincronizarEstadoPrograma(int $idPlan, string $estado): void
    {
        $this->query(
            "UPDATE auditoria_programa SET estado = ? WHERE id_plan = ?",
            [$estado, $idPlan]
        );
    }


    /** Contar actividades del cronograma */
    public function totalActividades(int $idPlan): int
    {
        $row = $this->query(
            "SELECT COUNT(*) AS total FROM auditoria_plan_actividad WHERE id_plan = ?",
            [$idPlan]
        )->fetch();
        return (int)($row['total'] ?? 0);
    }


    /** Planes disponibles para crear programa: sin programa asignado */
    public function sinPrograma(): array
    {
        return $this->query("
            SELECT p.id, p.codigo, p.titulo, p.anio, p.estado
            FROM auditoria_plan p
            WHERE p.estado NOT IN ('CANCELADO','FINALIZADO')
              AND NOT EXISTS (
                  SELECT 1 FROM auditoria_programa pg
                  WHERE pg.id_plan = p.id
              )
            ORDER BY p.anio DESC, p.id DESC
        ")->fetchAll();
    }


    /** Devolver programa vinculado a BORRADOR */
    public function devolverProgramaABorrador(int $idPlan): void
    {
        $this->query(
            "UPDATE auditoria_programa SET estado = 'BORRADOR' WHERE id_plan = ?",
            [$idPlan]
        );
    }


    /**
     * Procesos vinculados a un plan de auditoría — para el AJAX de HallazgoController.
     * Eliminado de HallazgoController::procesosPorPrograma() (era SQL directo).
     */
    public function procesosDePlan(int $idPlan): array
    {
        return $this->query("
            SELECT pp.id_proceso, pr.proceso, pr.sigla_proceso
            FROM auditoria_plan_proceso pp
            INNER JOIN proceso pr ON pr.id_proceso = pp.id_proceso
            WHERE pp.id_plan = ?
            ORDER BY pr.proceso
        ", [$idPlan])->fetchAll();
    }

}