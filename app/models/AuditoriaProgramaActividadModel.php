<?php
namespace App\Models;
use App\Core\Model;

class AuditoriaProgramaActividadModel extends Model
{
    protected string $table      = 'auditoria_programa_actividad';
    protected string $primaryKey = 'id';

    public function porPrograma(int $idPrograma): array
    {
        return $this->query("
            SELECT a.*,
                   p.proceso   AS proceso_nombre,
                   e.nombre_completo AS auditor_nombre
            FROM auditoria_programa_actividad a
            LEFT JOIN proceso  p ON p.id_proceso  = a.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = a.id_auditor
            WHERE a.id_programa = ?
            ORDER BY a.orden ASC, a.id ASC
        ", [$idPrograma])->fetchAll();
    }

    /**
     * Eliminar actividades de un programa excepto las que están en $idsConservar.
     * Elimina el Database::getInstance() de AuditoriaProgramaController.
     */
    public function eliminarExcepto(int $idPrograma, array $idsConservar): void
    {
        if (empty($idsConservar)) {
            $this->query(
                "DELETE FROM auditoria_programa_actividad WHERE id_programa = ?",
                [$idPrograma]
            );
            return;
        }
        $ph = implode(',', array_fill(0, count($idsConservar), '?'));
        $params = array_merge([$idPrograma], array_map('intval', $idsConservar));
        $this->query(
            "DELETE FROM auditoria_programa_actividad WHERE id_programa = ? AND id NOT IN ($ph)",
            $params
        );
    }

    /**
     * Eliminar todas las actividades de un programa.
     */
    public function eliminarTodas(int $idPrograma): void
    {
        $this->query(
            "DELETE FROM auditoria_programa_actividad WHERE id_programa = ?",
            [$idPrograma]
        );
    }

}