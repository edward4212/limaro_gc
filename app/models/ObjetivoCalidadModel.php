<?php
namespace App\Models;
use App\Core\Model;

class ObjetivoCalidadModel extends Model
{
    protected string $table      = 'objetivo_calidad';
    protected string $primaryKey = 'id';

    public function listar(): array
    {
        return $this->query("
            SELECT o.*,
                   p.proceso,
                   (SELECT COUNT(*) FROM objetivo_medicion m WHERE m.id_objetivo = o.id) AS total_mediciones,
                   (SELECT AVG(m.cumple) FROM objetivo_medicion m WHERE m.id_objetivo = o.id AND m.cumple IS NOT NULL) AS pct_cumplimiento
            FROM objetivo_calidad o
            LEFT JOIN proceso p ON p.id_proceso = o.id_proceso
            WHERE o.estado = 'ACTIVO'
            ORDER BY o.codigo
        ")->fetchAll();
    }

    public function mediciones(int $id): array
    {
        return $this->query("
            SELECT * FROM objetivo_medicion WHERE id_objetivo = ?
            ORDER BY periodo DESC
        ", [$id])->fetchAll();
    }

    public function registrarMedicion(int $id, array $data): int
    {
        $data['id_objetivo'] = $id;
        $data['cumple'] = isset($data['valor_obtenido'], $data['valor_meta'])
            ? ((float)$data['valor_obtenido'] >= (float)$data['valor_meta'] ? 1 : 0)
            : null;
        return $this->query("
            INSERT INTO objetivo_medicion
                (id_objetivo, periodo, valor_obtenido, valor_meta, cumple, observacion, registrado_por)
            VALUES (?,?,?,?,?,?,?)
        ", [
            $id,
            $data['periodo'],
            $data['valor_obtenido'] ?? null,
            $data['valor_meta']     ?? null,
            $data['cumple'],
            $data['observacion']    ?? null,
            $data['registrado_por'] ?? null,
        ]) ? (int)$this->db->lastInsertId() : 0;
    }

    public function siguienteCodigo(): string
    {
        $max = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo,'-',-1) AS UNSIGNED)),0) FROM objetivo_calidad WHERE codigo LIKE 'OC-%'"
        )->fetchColumn();
        return 'OC-' . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
