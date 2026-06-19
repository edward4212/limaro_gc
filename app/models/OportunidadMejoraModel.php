<?php
namespace App\Models;
use App\Core\Model;

class OportunidadMejoraModel extends Model
{
    protected string $table      = 'oportunidad_mejora';
    protected string $primaryKey = 'id';

    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];
        if (!empty($filtros['estado'])) {
            $where[] = 'om.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['id_proceso'])) {
            $where[] = 'om.id_proceso = ?';
            $params[] = (int) $filtros['id_proceso'];
        }
        if (!empty($filtros['solo_mias']) && !empty($filtros['id_empleado_actual'])) {
            $where[] = 'om.id_empleado_propone = ?';
            $params[] = (int) $filtros['id_empleado_actual'];
        }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        return $this->query("
            SELECT om.*, p.proceso AS proceso_nombre,
                   e.nombre_completo AS propone_nombre
            FROM oportunidad_mejora om
            LEFT JOIN proceso p ON p.id_proceso = om.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = om.id_empleado_propone
            {$sqlWhere}
            ORDER BY om.fecha_registro DESC
        ", $params)->fetchAll();
    }

    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT om.*, p.proceso AS proceso_nombre,
                   e.nombre_completo AS propone_nombre,
                   u.usuario AS evalua_nombre,
                   ac.codigo AS ac_codigo, ac.estado AS ac_estado
            FROM oportunidad_mejora om
            LEFT JOIN proceso p ON p.id_proceso = om.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = om.id_empleado_propone
            LEFT JOIN usuario u ON u.id_usuario = om.id_usuario_evalua
            LEFT JOIN accion_correctiva ac ON ac.id = om.id_accion_correctiva
            WHERE om.id = ?
        ", [$id])->fetch() ?: null;
    }

    public function resumenPorEstado(): array
    {
        return $this->query(
            "SELECT estado, COUNT(*) AS total FROM oportunidad_mejora GROUP BY estado"
        )->fetchAll();
    }

    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $max  = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo,'-',-1) AS UNSIGNED)),0)
             FROM oportunidad_mejora WHERE codigo LIKE ?",
            ["OM-$anio-%"]
        )->fetchColumn();
        return "OM-$anio-" . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
