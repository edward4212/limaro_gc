<?php

namespace App\Models;

use App\Core\Model;

/**
 * ObjetivoMedicionModel — Mediciones de objetivos de calidad §6.2.
 * BUG-004: tabla objetivo_medicion existía sin modelo PHP.
 */
class ObjetivoMedicionModel extends Model
{
    protected string $table      = 'objetivo_medicion';
    protected string $primaryKey = 'id';

    /**
     * Mediciones de un objetivo específico.
     */
    public function porObjetivo(int $idObjetivo): array
    {
        return $this->query("
            SELECT m.*,
                   COALESCE(e.nombre_completo, m.registrado_por) AS registrador
            FROM objetivo_medicion m
            LEFT JOIN usuario  u ON u.id_usuario  = m.id_usuario
            LEFT JOIN empleado e ON e.id_empleado = u.id_empleado
            WHERE m.id_objetivo = ?
            ORDER BY m.fecha_registro DESC
        ", [$idObjetivo])->fetchAll();
    }

    /**
     * Resumen de cumplimiento de todos los objetivos activos.
     * Usado en reportes §6.2.
     */
    public function resumenCumplimiento(): array
    {
        return $this->query("
            SELECT o.id, o.codigo, o.objetivo, o.meta, o.frecuencia,
                   COUNT(m.id)                                              AS total_mediciones,
                   COALESCE(SUM(m.cumple), 0)                              AS cumplidas,
                   ROUND(SUM(m.cumple) / NULLIF(COUNT(m.id),0) * 100, 1)  AS pct_cumplimiento,
                   ROUND(AVG(m.valor_obtenido), 2)                         AS promedio_obtenido,
                   ROUND(AVG(m.valor_meta), 2)                             AS promedio_meta
            FROM objetivo_calidad o
            LEFT JOIN objetivo_medicion m ON m.id_objetivo = o.id
            WHERE o.estado = 'ACTIVO'
            GROUP BY o.id
            ORDER BY o.codigo
        ")->fetchAll();
    }

    /**
     * Registrar una nueva medición.
     */
    public function registrar(int $idObjetivo, array $data): int
    {
        $this->query("
            INSERT INTO objetivo_medicion
                (id_objetivo, periodo, valor_obtenido, valor_meta,
                 cumple, observacion, registrado_por, id_usuario)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ", [
            $idObjetivo,
            $data['periodo']          ?? null,
            $data['valor_obtenido']   ?? null,
            $data['valor_meta']       ?? null,
            isset($data['cumple']) ? (int)(bool)$data['cumple'] : null,
            $data['observacion']      ?? null,
            $data['registrado_por']   ?? null,
            $data['id_usuario']       ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Últimas N mediciones de todos los objetivos (para dashboard).
     */
    public function ultimas(int $limite = 10): array
    {
        return $this->query("
            SELECT m.*, o.codigo, o.objetivo,
                   COALESCE(e.nombre_completo, m.registrado_por) AS registrador
            FROM objetivo_medicion m
            INNER JOIN objetivo_calidad o ON o.id = m.id_objetivo
            LEFT  JOIN usuario  u ON u.id_usuario  = m.id_usuario
            LEFT  JOIN empleado e ON e.id_empleado = u.id_empleado
            ORDER BY m.fecha_registro DESC
            LIMIT ?
        ", [$limite])->fetchAll();
    }

    /**
     * Eliminar una medición.
     */
    public function eliminar(int $id): void
    {
        $this->query(
            "DELETE FROM objetivo_medicion WHERE id = ?",
            [$id]
        );
    }
}
