<?php
namespace App\Models;
use App\Core\Model;

class FodaModel extends Model
{
    protected string $table      = 'foda';
    protected string $primaryKey = 'id';

    public function listar(string $tipo = '', string $impacto = ''): array
    {
        $where = ["f.estado = 'ACTIVO'"];
        $params = [];
        if ($tipo)    { $where[] = "f.tipo = ?";    $params[] = $tipo; }
        if ($impacto) { $where[] = "f.impacto = ?"; $params[] = $impacto; }
        $sql = "WHERE " . implode(" AND ", $where);

        return $this->query("
            SELECT f.*, u.usuario AS registrado_por
            FROM foda f
            LEFT JOIN usuario u ON u.id_usuario = f.id_usuario
            $sql
            ORDER BY FIELD(f.tipo,'FORTALEZA','OPORTUNIDAD','DEBILIDAD','AMENAZA'), f.impacto
        ", $params)->fetchAll();
    }

    public function resumen(): array
    {
        $rows = $this->query(
            "SELECT tipo, COUNT(*) AS total FROM foda WHERE estado='ACTIVO' GROUP BY tipo"
        )->fetchAll();
        $r = ['FORTALEZA'=>0,'OPORTUNIDAD'=>0,'DEBILIDAD'=>0,'AMENAZA'=>0];
        foreach ($rows as $row) $r[$row['tipo']] = (int)$row['total'];
        return $r;
    }

    /**
     * Resumen por tipo para el reporte de contexto.
     * Devuelve array [{tipo, total}] compatible con el reporte.
     */
    public function resumenPorTipo(): array
    {
        return $this->query(
            "SELECT tipo, COUNT(*) AS total FROM foda GROUP BY tipo"
        )->fetchAll();
    }

}