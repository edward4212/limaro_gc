<?php
namespace App\Models;
use App\Core\Model;
class AuditoriaInternaModel extends Model
{
    protected string $table      = 'auditoria_programa';
    protected string $primaryKey = 'id';

    public function listar(): array {
        return $this->query("SELECT p.*, COUNT(h.id) AS total_hallazgos,
            SUM(h.tipo='NO_CONFORMIDAD') AS nc, SUM(h.estado='CERRADO') AS cerrados
            FROM auditoria_programa p LEFT JOIN auditoria_hallazgo h ON h.id_programa = p.id
            GROUP BY p.id ORDER BY p.anio DESC, p.fecha_inicio DESC")->fetchAll();
    }
    public function resumenEstados(): array {
        return $this->query("SELECT estado, COUNT(*) AS total FROM auditoria_programa GROUP BY estado")->fetchAll();
    }
    public function hallazgos(int $id): array {
        return $this->query("SELECT * FROM auditoria_hallazgo WHERE id_programa = ? ORDER BY tipo, estado", [$id])->fetchAll();
    }
    public function crear(array $data, mixed $idUsuario): int {
        $data['id_usuario'] = $idUsuario;
        return $this->insert($data);
    }
    public function crearHallazgo(int $idPrograma, array $data): int {
        $data['id_programa'] = $idPrograma;
        return $this->query("INSERT INTO auditoria_hallazgo
            (id_programa,tipo,clausula_iso,proceso_auditado,descripcion,evidencia,accion_correctiva,responsable,fecha_cierre,estado)
            VALUES (?,?,?,?,?,?,?,?,?,?)", [
            $idPrograma, $data['tipo']??'OBSERVACION', $data['clausula_iso']??null,
            $data['proceso_auditado']??null, $data['descripcion'],
            $data['evidencia']??null, $data['accion_correctiva']??null,
            $data['responsable']??null, $data['fecha_cierre']??null,
            $data['estado']??'ABIERTO'
        ]) ? (int)$this->db->lastInsertId() : 0;
    }

    public function contarHallazgosAbiertos(int $idPrograma): int
    {
        return (int) $this->query(
            "SELECT COUNT(*) FROM auditoria_hallazgo
             WHERE id_programa = ? AND estado = 'ABIERTO'",
            [$idPrograma]
        )->fetchColumn();
    }

}