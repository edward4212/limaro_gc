<?php
namespace App\Models;
use App\Core\Model;

class AuditoriaInformeDistribucionModel extends Model
{
    protected string $table = 'auditoria_informe_distribucion';

    public function porInforme(int $idInforme): array
    {
        return $this->query(
            "SELECT d.*, e.nombre_completo AS empleado_nombre
             FROM auditoria_informe_distribucion d
             LEFT JOIN empleado e ON e.id_empleado = d.id_empleado
             WHERE d.id_informe = ?
             ORDER BY d.id ASC",
            [$idInforme]
        )->fetchAll();
    }
}
