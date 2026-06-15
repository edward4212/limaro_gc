<?php
namespace App\Models;
use App\Core\Model;

class ParteInteresadaModel extends Model
{
    protected string $table      = 'parte_interesada';
    protected string $primaryKey = 'id';

    public function listar(string $tipo = ''): array
    {
        $where  = "WHERE p.estado = 'ACTIVO'";
        $params = [];
        if ($tipo) { $where .= " AND p.tipo = ?"; $params[] = $tipo; }

        return $this->query("
            SELECT p.*, u.usuario AS registrado_por
            FROM parte_interesada p
            LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
            $where
            ORDER BY p.tipo, p.nivel_influencia DESC, p.nombre
        ", $params)->fetchAll();
    }

    public function resumen(): array
    {
        $rows = $this->query(
            "SELECT tipo, COUNT(*) AS total FROM parte_interesada WHERE estado='ACTIVO' GROUP BY tipo"
        )->fetchAll();
        return array_column($rows, 'total', 'tipo');
    }

    /**
     * Resumen por estado para el reporte de contexto.
     */
    public function resumenPorEstado(): array
    {
        return $this->query(
            "SELECT estado, COUNT(*) AS total FROM parte_interesada GROUP BY estado"
        )->fetchAll();
    }

}