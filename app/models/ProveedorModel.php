<?php
namespace App\Models;
use App\Core\Model;

class ProveedorModel extends Model
{
    protected string $table      = 'proveedor';
    protected string $primaryKey = 'id';

    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];
        if (!empty($filtros['estado'])) {
            $where[] = 'p.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['tipo_vinculo'])) {
            $where[] = 'p.tipo_vinculo = ?';
            $params[] = $filtros['tipo_vinculo'];
        }
        if (!empty($filtros['busqueda'])) {
            $where[] = '(p.razon_social LIKE ? OR p.numero_documento LIKE ?)';
            $b = '%' . $filtros['busqueda'] . '%';
            $params[] = $b;
            $params[] = $b;
        }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        return $this->query("
            SELECT p.*,
                   (SELECT pe.resultado FROM proveedor_evaluacion pe
                    WHERE pe.id_proveedor = p.id
                    ORDER BY pe.fecha_evaluacion DESC LIMIT 1) AS ultimo_resultado,
                   (SELECT COUNT(*) FROM proveedor_evaluacion pe2
                    WHERE pe2.id_proveedor = p.id) AS total_evaluaciones
            FROM proveedor p
            {$sqlWhere}
            ORDER BY p.razon_social ASC
        ", $params)->fetchAll();
    }

    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT p.*,
                   u1.usuario AS usuario_verifico_nombre,
                   u2.usuario AS usuario_registro_nombre
            FROM proveedor p
            LEFT JOIN usuario u1 ON u1.id_usuario = p.id_usuario_verifico
            LEFT JOIN usuario u2 ON u2.id_usuario = p.id_usuario_registro
            WHERE p.id = ?
        ", [$id])->fetch() ?: null;
    }

    public function evaluaciones(int $idProveedor): array
    {
        return $this->query("
            SELECT pe.*, u.usuario AS evaluador_nombre
            FROM proveedor_evaluacion pe
            LEFT JOIN usuario u ON u.id_usuario = pe.id_usuario_evaluador
            WHERE pe.id_proveedor = ?
            ORDER BY pe.fecha_evaluacion DESC
        ", [$idProveedor])->fetchAll();
    }

    public function resumenPorEstado(): array
    {
        return $this->query(
            "SELECT estado, COUNT(*) AS total FROM proveedor GROUP BY estado"
        )->fetchAll();
    }

    /** Deriva el resultado cualitativo a partir del promedio (mismos rangos del PV-FO-4 original). */
    public function derivarResultado(float $promedio): string
    {
        if ($promedio >= 4.5) return 'EXCELENTE';
        if ($promedio >= 3.9) return 'BUENO';
        if ($promedio >= 3.0) return 'REGULAR';
        return 'NO_CONFIABLE';
    }

    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $max  = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo,'-',-1) AS UNSIGNED)),0)
             FROM proveedor WHERE codigo LIKE ?",
            ["PV-$anio-%"]
        )->fetchColumn();
        return "PV-$anio-" . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }

}
