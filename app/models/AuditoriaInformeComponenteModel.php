<?php
namespace App\Models;
use App\Core\Model;

class AuditoriaInformeComponenteModel extends Model
{
    protected string $table = 'auditoria_informe_componente';

    public function porInforme(int $idInforme): array
    {
        return $this->query(
            "SELECT * FROM auditoria_informe_componente
             WHERE id_informe = ? ORDER BY orden ASC, id ASC",
            [$idInforme]
        )->fetchAll();
    }

    public function resumenCalificaciones(int $idInforme): array
    {
        $row = $this->query("
            SELECT
                COUNT(*)                                AS total,
                SUM(calificacion = 'EFECTIVO')          AS efectivos,
                SUM(calificacion = 'PARCIALMENTE')      AS parciales,
                SUM(calificacion = 'INEFECTIVO')        AS inefectivos,
                SUM(calificacion = 'NO_EVALUADO')       AS no_evaluados
            FROM auditoria_informe_componente
            WHERE id_informe = ?
        ", [$idInforme])->fetch();
        return $row ?: [];
    }
}
