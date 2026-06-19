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
                   ROUND((SELECT SUM(m.cumple) / NULLIF(COUNT(m.id),0) * 100 FROM objetivo_medicion m WHERE m.id_objetivo = o.id) , 1) AS pct_cumplimiento
            FROM objetivo_calidad o
            LEFT JOIN proceso p ON p.id_proceso = o.id_proceso
            WHERE o.estado = 'ACTIVO'
            ORDER BY o.codigo
        ")->fetchAll();
    }

    /** HU-E01: detalle con nombre del responsable vía JOIN (no existe columna 'responsable' en BD) */
    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT o.*,
                   e.nombre_completo AS responsable_nombre,
                   e.estado_empleado AS responsable_estado,
                   c.cargo            AS responsable_cargo
            FROM objetivo_calidad o
            LEFT JOIN empleado e ON e.id_empleado = o.id_responsable
            LEFT JOIN cargo    c ON c.id_cargo    = e.id_cargo
            WHERE o.id = ?
            LIMIT 1
        ", [$id])->fetch() ?: null;
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
                (id_objetivo, periodo, valor_obtenido, valor_meta, cumple,
                 observacion, registrado_por, id_usuario)
            VALUES (?,?,?,?,?,?,?,?)
        ", [
            $id,
            $data['periodo'],
            $data['valor_obtenido'] ?? null,
            $data['valor_meta']     ?? null,
            $data['cumple'],
            $data['observacion']    ?? null,
            $data['registrado_por'] ?? null,
            $data['id_usuario']     ?? null,
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
