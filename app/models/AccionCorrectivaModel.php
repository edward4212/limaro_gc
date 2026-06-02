<?php
namespace App\Models;
use App\Core\Model;

class AccionCorrectivaModel extends Model
{
    protected string $table      = 'accion_correctiva';
    protected string $primaryKey = 'id';

    public function listar(): array
    {
        return $this->query("
            SELECT ac.*,
                   DATEDIFF(COALESCE(ac.fecha_cierre, CURDATE()), ac.fecha_planificada) AS dias_vencida
            FROM accion_correctiva ac
            ORDER BY ac.estado ASC, ac.fecha_planificada ASC
        ")->fetchAll();
    }

    public function resumenEstados(): array
    {
        return $this->query(
            "SELECT estado, COUNT(*) AS total FROM accion_correctiva GROUP BY estado"
        )->fetchAll();
    }

    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $max  = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo,'-',-1) AS UNSIGNED)),0)
             FROM accion_correctiva WHERE codigo LIKE ?",
            ["AC-$anio-%"]
        )->fetchColumn();
        return "AC-$anio-" . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
