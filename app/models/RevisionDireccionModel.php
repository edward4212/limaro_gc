<?php
namespace App\Models;
use App\Core\Model;
class RevisionDireccionModel extends Model
{
    protected string $table      = 'revision_direccion';
    protected string $primaryKey = 'id';

    public function listar(): array {
        return $this->query("SELECT * FROM revision_direccion ORDER BY anio DESC, fecha_revision DESC")->fetchAll();
    }
    public function crear(array $data, mixed $idUsuario): int {
        $data['id_usuario'] = $idUsuario;
        return $this->insert($data);
    }
}
