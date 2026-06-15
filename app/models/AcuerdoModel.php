<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Acuerdos.
 *
 * Tabla real: `acuerdos` (plural).
 * Columnas: id_acuerdo, id_tipo_documento, año_acuerdo, numero_acuerdo,
 *           nombre_acuerdo, documento (archivo), fecha_aprobacion, acta_aprobacion
 */
class AcuerdoModel extends Model
{
    protected string $table      = 'acuerdos';
    protected string $primaryKey = 'id_acuerdo';

    /**
     * Listar acuerdos con tipo y archivo.
     */
    public function listar(): array
    {
        return $this->query("
            SELECT a.*, td.tipo_documento, td.sigla_tipo_documento,
                   (SELECT ar.id_archivo FROM archivo ar
                    WHERE ar.modulo = 'ACUERDO' AND ar.id_referencia = a.id_acuerdo
                    ORDER BY ar.id_archivo DESC LIMIT 1) AS id_archivo,
                   (SELECT ar.nombre_original FROM archivo ar
                    WHERE ar.modulo = 'ACUERDO' AND ar.id_referencia = a.id_acuerdo
                    ORDER BY ar.id_archivo DESC LIMIT 1) AS archivo_nombre,
                   (SELECT ar.ruta_relativa FROM archivo ar
                    WHERE ar.modulo = 'ACUERDO' AND ar.id_referencia = a.id_acuerdo
                    ORDER BY ar.id_archivo DESC LIMIT 1) AS archivo_ruta
            FROM acuerdos a
            INNER JOIN tipo_documento td ON td.id_tipo_documento = a.id_tipo_documento
            ORDER BY a.año_acuerdo DESC, a.numero_acuerdo DESC
        ")->fetchAll();
    }

    /**
     * Acuerdos vigentes. En esta BD no hay columna `estado`, así que devolvemos
     * todos los acuerdos del año indicado (o todos si no se filtra).
     */
    public function vigentes(?int $año = null): array
    {
        $sql    = "
            SELECT a.*, td.tipo_documento,
                   ar.id_archivo, ar.nombre_original AS archivo_nombre
            FROM acuerdos a
            INNER JOIN tipo_documento td ON td.id_tipo_documento = a.id_tipo_documento
            LEFT JOIN (
                SELECT id_referencia,
                       MAX(id_archivo) AS id_archivo,
                       SUBSTRING_INDEX(GROUP_CONCAT(nombre_original ORDER BY id_archivo DESC), ',', 1) AS nombre_original
                FROM archivo WHERE modulo = 'ACUERDO'
                GROUP BY id_referencia
            ) ar ON ar.id_referencia = a.id_acuerdo
            WHERE 1=1
        ";
        $params = [];
        if ($año) {
            $sql .= " AND a.año_acuerdo = ?";
            $params[] = $año;
        }
        $sql .= " ORDER BY a.año_acuerdo DESC, a.numero_acuerdo DESC";
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Años disponibles (para filtros).
     */
    public function años(): array
    {
        return $this->query(
            "SELECT DISTINCT año_acuerdo AS año FROM acuerdos ORDER BY año_acuerdo DESC"
        )->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function crear(array $data): int
    {
        return $this->insert($data);
    }

    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }
}
