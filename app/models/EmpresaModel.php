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
}
