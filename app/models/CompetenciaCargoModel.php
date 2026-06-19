<?php
namespace App\Models;
use App\Core\Model;

class CompetenciaCargoModel extends Model
{
    protected string $table      = 'competencia_cargo';
    protected string $primaryKey = 'id';

    /** Lista solo los cargos activos que tienen manual de funciones cargado (filtro real pedido),
     *  junto con su perfil de competencias (si ya se definió) y el id_archivo para enlazar
     *  directo al documento, sin pasar por la vista genérica de Manual de Funciones. */
    public function listarConCargos(): array
    {
        return $this->query("
            SELECT c.id_cargo, c.cargo, c.estado AS estado_cargo,
                   (SELECT COUNT(*) FROM empleado e WHERE e.id_cargo = c.id_cargo AND e.estado_empleado = 'ACTIVO') AS total_empleados,
                   cc.id AS id_competencia, cc.formacion_academica, cc.experiencia,
                   cc.formacion_entrenamiento, cc.habilidades,
                   COALESCE(cc.fecha_actualizacion, cc.fecha_registro) AS fecha_actualizacion,
                   ar.id_archivo, ar.nombre_original AS manual_nombre
            FROM cargo c
            LEFT JOIN competencia_cargo cc ON cc.id_cargo = c.id_cargo
            INNER JOIN archivo ar ON ar.modulo = 'CARGO' AND ar.id_referencia = c.id_cargo
            WHERE c.estado = 'ACTIVO'
            ORDER BY c.cargo
        ")->fetchAll();
    }

    public function porCargo(int $idCargo): ?array
    {
        return $this->query(
            "SELECT * FROM competencia_cargo WHERE id_cargo = ?", [$idCargo]
        )->fetch() ?: null;
    }

    /** Inserta o actualiza el perfil del cargo (relación 1 a 1: un cargo solo tiene un perfil). */
    public function guardarPerfil(int $idCargo, array $data): void
    {
        $existente = $this->porCargo($idCargo);
        $data['id_cargo'] = $idCargo;
        if ($existente) {
            $this->update((int) $existente['id'], $data);
        } else {
            $this->insert($data);
        }
    }
}
