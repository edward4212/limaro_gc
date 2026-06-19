<?php
namespace App\Models;
use App\Core\Model;

class CapacitacionEmpleadoModel extends Model
{
    protected string $table      = 'capacitacion_empleado';
    protected string $primaryKey = 'id';

    public function porEmpleado(int $idEmpleado): array
    {
        return $this->query("
            SELECT ce.*, u.usuario AS usuario_registro_nombre
            FROM capacitacion_empleado ce
            LEFT JOIN usuario u ON u.id_usuario = ce.id_usuario_registro
            WHERE ce.id_empleado = ?
            ORDER BY ce.fecha_finalizacion DESC
        ", [$idEmpleado])->fetchAll();
    }

    /** Listado general con filtros, para la vista de seguimiento de capacitaciones de toda la cooperativa. */
    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];
        if (!empty($filtros['tipo'])) {
            $where[] = 'ce.tipo = ?';
            $params[] = $filtros['tipo'];
        }
        if (!empty($filtros['resultado'])) {
            $where[] = 'ce.resultado = ?';
            $params[] = $filtros['resultado'];
        }
        if (!empty($filtros['id_cargo'])) {
            $where[] = 'e.id_cargo = ?';
            $params[] = (int) $filtros['id_cargo'];
        }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        return $this->query("
            SELECT ce.*, e.nombre_completo AS empleado_nombre, c.cargo AS cargo_nombre
            FROM capacitacion_empleado ce
            INNER JOIN empleado e ON e.id_empleado = ce.id_empleado
            LEFT JOIN cargo c ON c.id_cargo = e.id_cargo
            {$sqlWhere}
            ORDER BY ce.fecha_finalizacion DESC
        ", $params)->fetchAll();
    }

    public function resumenPorResultado(): array
    {
        return $this->query(
            "SELECT resultado, COUNT(*) AS total FROM capacitacion_empleado GROUP BY resultado"
        )->fetchAll();
    }
}
