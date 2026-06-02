<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\RolModel;

/**
 * CRUD de Roles + Matriz de permisos.
 */
class RolController extends Controller
{
    private RolModel $model;

    public function __construct()
    {
        $this->model = new RolModel();
    }

    public function index(): void
    {
        $this->view('seguridad/roles/index', [
            'pageTitle' => 'Roles del Sistema',
            'roles'     => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        $this->view('seguridad/roles/form', ['pageTitle' => 'Crear Rol', 'item' => null]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['rol', 'estado']);
        $errors = $this->validate($data, ['rol' => 'required|max:100']);
        if ($errors) {
            Session::flash('error', 'El nombre del rol es obligatorio.');
            $this->redirect('/roles/crear');
            return;
        }

        $this->model->insert([
            'rol'    => strtoupper(trim($data['rol'])),
            'estado' => $data['estado'] ?? 'ACTIVO',
        ]);
        $this->redirectSuccess('/roles', 'Rol creado exitosamente.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('seguridad/roles/form', ['pageTitle' => 'Editar Rol', 'item' => $item]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $data = Request::only(['rol', 'estado']);
        $this->model->update($id, [
            'rol'    => strtoupper(trim($data['rol'])),
            'estado' => $data['estado'] ?? 'ACTIVO',
        ]);
        $this->redirectSuccess('/roles', 'Rol actualizado.');
    }

    public function eliminar(int $id): void
    {
        Csrf::verify();
        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/roles', 'Rol inactivado.');
    }

    /** GET /roles/permisos/{id} */
    public function permisos(int $id): void
    {
        $rol      = $this->model->find($id);
        if (!$rol) $this->abort(404);
        $modulos  = $this->model->modulosArbol();
        $permisos = $this->model->permisosDelRol($id);

        $this->view('seguridad/roles/permisos', [
            'pageTitle' => 'Permisos: ' . $rol['rol'],
            'rol'       => $rol,
            'modulos'   => $modulos,
            'permisos'  => $permisos,
        ]);
    }

    /** POST /roles/permisos/{id} */
    public function guardarPermisos(int $id): void
    {
        Csrf::verify();
        $permisosPost = $_POST['permisos'] ?? [];
        $this->model->guardarPermisos($id, $permisosPost);

        // Limpiar módulos de sesión para todos los usuarios con este rol (se recargarán)
        $this->redirectSuccess('/roles', 'Permisos actualizados. Los usuarios con este rol verán los cambios al próximo login.');
    }
}
