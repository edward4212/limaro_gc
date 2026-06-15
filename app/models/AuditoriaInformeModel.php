<?php
namespace App\Models;
use App\Core\Model;

class AuditoriaInformeModel extends Model
{
    protected string $table = 'auditoria_informe';

    public function siguienteCodigo(): string
    {
        $anio = date('Y');
        $row  = $this->query(
            "SELECT COUNT(*) AS total FROM auditoria_informe WHERE YEAR(fecha_registro) = ?",
            [$anio]
        )->fetch();
        return 'IN-' . $anio . '-' . str_pad((int)($row['total']??0)+1, 3, '0', STR_PAD_LEFT);
    }

    public function listar(array $f = []): array
    {
        $where = ['1=1']; $p = [];
        if (!empty($f['anio']))   { $where[] = 'YEAR(i.fecha_registro) = ?'; $p[] = $f['anio']; }
        if (!empty($f['estado'])) { $where[] = 'i.estado = ?';               $p[] = $f['estado']; }

        return $this->query("
            SELECT i.*,
                   e.nombre_completo  AS auditor_nombre,
                   pg.codigo          AS programa_codigo,
                   pl.codigo          AS plan_codigo,
                   pl.titulo          AS plan_titulo
            FROM auditoria_informe i
            LEFT JOIN empleado        e  ON e.id_empleado  = i.id_auditor_lider
            LEFT JOIN auditoria_programa pg ON pg.id       = i.id_programa
            LEFT JOIN auditoria_plan     pl ON pl.id       = i.id_plan
            WHERE " . implode(' AND ', $where) . "
            ORDER BY i.fecha_registro DESC
        ", $p)->fetchAll();
    }

    public function detalle(int $id): ?array
    {
        return $this->query("
            SELECT i.*,
                   e.nombre_completo  AS auditor_nombre,
                   pg.codigo          AS programa_codigo,
                   pl.codigo          AS plan_codigo, pl.titulo AS plan_titulo
            FROM auditoria_informe i
            LEFT JOIN empleado        e  ON e.id_empleado = i.id_auditor_lider
            LEFT JOIN auditoria_programa pg ON pg.id      = i.id_programa
            LEFT JOIN auditoria_plan     pl ON pl.id      = i.id_plan
            WHERE i.id = ?
        ", [$id])->fetch() ?: null;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        $this->query("UPDATE auditoria_informe SET estado=? WHERE id=?", [$estado, $id]);
    }

    public function informeDelPrograma(int $idPrograma): ?array
    {
        return $this->query(
            "SELECT id, codigo, estado FROM auditoria_informe WHERE id_programa = ? LIMIT 1",
            [$idPrograma]
        )->fetch() ?: null;
    }
}
