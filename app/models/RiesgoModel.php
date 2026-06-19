<?php
namespace App\Models;
use App\Core\Model;

class RiesgoModel extends Model
{
    protected string $table      = 'riesgo';
    protected string $primaryKey = 'id';

    /** Matriz de derivación de nivel de riesgo a partir de probabilidad x impacto. */
    private const MATRIZ = [
        'ALTO'  => ['ALTO' => 'ALTO',  'MEDIO' => 'ALTO',  'BAJO' => 'MEDIO'],
        'MEDIO' => ['ALTO' => 'ALTO',  'MEDIO' => 'MEDIO', 'BAJO' => 'BAJO'],
        'BAJO'  => ['ALTO' => 'MEDIO', 'MEDIO' => 'BAJO',  'BAJO' => 'BAJO'],
    ];

    /** Deriva el nivel de riesgo (ALTO/MEDIO/BAJO) combinando probabilidad e impacto. */
    public function derivarNivel(string $probabilidad, string $impacto): string
    {
        return self::MATRIZ[$probabilidad][$impacto] ?? 'MEDIO';
    }

    public function listar(array $filtros = []): array
    {
        $where  = [];
        $params = [];
        if (!empty($filtros['id_proceso'])) {
            $where[] = 'r.id_proceso = ?';
            $params[] = (int) $filtros['id_proceso'];
        }
        if (!empty($filtros['estado'])) {
            $where[] = 'r.estado = ?';
            $params[] = $filtros['estado'];
        }
        if (!empty($filtros['nivel_riesgo_inherente'])) {
            $where[] = 'r.nivel_riesgo_inherente = ?';
            $params[] = $filtros['nivel_riesgo_inherente'];
        }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        return $this->query("
            SELECT r.*,
                   p.proceso AS proceso_nombre,
                   e.nombre_completo AS responsable_nombre
            FROM riesgo r
            INNER JOIN proceso p ON p.id_proceso = r.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = r.id_responsable
            {$sqlWhere}
            ORDER BY FIELD(r.nivel_riesgo_inherente,'ALTO','MEDIO','BAJO'), r.fecha_identificacion DESC
        ", $params)->fetchAll();
    }

    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT r.*,
                   p.proceso AS proceso_nombre,
                   e.nombre_completo AS responsable_nombre,
                   u.usuario AS usuario_registro_nombre
            FROM riesgo r
            INNER JOIN proceso p ON p.id_proceso = r.id_proceso
            LEFT JOIN empleado e ON e.id_empleado = r.id_responsable
            LEFT JOIN usuario u ON u.id_usuario = r.id_usuario_registro
            WHERE r.id = ?
        ", [$id])->fetch() ?: null;
    }

    /** Resumen para dashboard/reportes: cuántos riesgos hay por nivel inherente y por estado. */
    public function resumenPorNivel(): array
    {
        return $this->query(
            "SELECT nivel_riesgo_inherente AS nivel, COUNT(*) AS total
             FROM riesgo GROUP BY nivel_riesgo_inherente"
        )->fetchAll();
    }

    public function resumenPorEstado(): array
    {
        return $this->query(
            "SELECT estado, COUNT(*) AS total FROM riesgo GROUP BY estado"
        )->fetchAll();
    }

    /** Matriz de calor: cuenta de riesgos por cada combinación probabilidad x impacto (inherente). */
    public function matrizCalor(): array
    {
        return $this->query(
            "SELECT probabilidad_inherente, impacto_inherente, COUNT(*) AS total
             FROM riesgo GROUP BY probabilidad_inherente, impacto_inherente"
        )->fetchAll();
    }

    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $max  = (int) $this->query(
            "SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(codigo,'-',-1) AS UNSIGNED)),0)
             FROM riesgo WHERE codigo LIKE ?",
            ["SR-$anio-%"]
        )->fetchColumn();
        return "SR-$anio-" . str_pad((string)($max + 1), 3, '0', STR_PAD_LEFT);
    }
}
