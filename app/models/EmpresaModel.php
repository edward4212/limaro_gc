<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modelo de Empresa.
 */
class EmpresaModel extends Model
{
    protected string $table      = 'empresa';
    protected string $primaryKey = 'id_empresa';

    /**
     * Obtener datos de la empresa principal (id=1).
     */
    public function getDatos(): ?array
    {
        return $this->find(1);
    }

    /**
     * Actualizar datos empresa.
     */
    public function actualizar(array $data): bool
    {
        return $this->update(1, $data);
    }

    /**
     * Obtener un campo específico del único registro de empresa.
     * Solo permite campos predefinidos para evitar inyección de nombre de columna.
     */
    public function primerRegistro(string $campo): ?array
    {
        $permitidos = ['logo','organigrama','mapa_procesos','nombre_empresa',
                       'mision','vision','politica_calidad','URL'];
        if (!in_array($campo, $permitidos, true)) {
            return null;
        }
        return $this->query(
            "SELECT `{$campo}` AS ruta FROM empresa ORDER BY id_empresa LIMIT 1"
        )->fetch() ?: null;
    }

}