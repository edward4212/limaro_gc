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
        Session::clearOldInput();
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

        $id = $this->model->insert([
            'rol'    => strtoupper(trim($data['rol'])),
            'estado' => $data['estado'] ?? 'ACTIVO',
        ]);
        // Auto-asignar ver=1 a todos los módulos activos para el nuevo rol
        try {
            $this->model->autoAsignarModulos($id);
        } catch (\Throwable $e) {
            error_log('[RolController] auto-permisos: ' . $e->getMessage());
        }

        $this->redirectSuccess('/roles', 'Rol creado. Se asignó acceso de lectura a todos los módulos. Ajuste los permisos adicionales desde Roles → Permisos.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        // CA-1/CA-2: bloquear renombrar roles predefinidos
        if (in_array($id, [1,2,3,4,5,6,7,8,10,11])) {
            Session::flash('error', 'Los roles predefinidos del sistema no pueden renombrarse.');
            $this->redirect('/roles');
            return;
        }
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
        // CA-1 HU-035: bloquear inactivar roles predefinidos
        if (in_array($id, [1,2,3,4,5,6,7,8,10,11])) {
            Session::flash('error', 'Los roles predefinidos del sistema no pueden inactivarse.');
            $this->redirect('/roles');
            return;
        }

        // CA-3: no inactivar si tiene usuarios activos asignados
        $activos = $this->model->tieneUsuariosActivos($id);

        if ($activos > 0) {
            $this->redirectError('/roles',
                "No se puede inactivar: el rol tiene <strong>{$activos}</strong> usuario(s) activo(s) asignado(s).");
            return;
        }

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
        // CA-4: ADMINISTRADOR siempre tiene acceso total — permisos inmutables
        if ($id === 1) {
            Session::flash('error', 'Los permisos del rol ADMINISTRADOR son inmutables.');
            $this->redirect('/roles/permisos/1');
            return;
        }
        $permisosPost = $_POST['permisos'] ?? [];
        $this->model->guardarPermisos($id, $permisosPost);

        // Limpiar módulos de sesión para todos los usuarios con este rol (se recargarán)
        $this->redirectSuccess('/roles', 'Permisos actualizados. Los usuarios con este rol verán los cambios al próximo login.');
    }

    /**
     * GET /roles/sincronizar/{id}
     * Agrega ver=1 a todos los módulos activos que le faltan al rol.
     */
    public function sincronizar(int $id): void
    {
        $rol = $this->model->find($id);
        if (!$rol) $this->abort(404);

        try {
            $agregados = $this->model->sincronizarModulos($id);

            registrarAuditoria('roles', 'SINCRONIZAR', 'rol', $id, null,
                ['modulos_agregados' => $agregados]);

            $this->redirectSuccess(
                "/roles/permisos/$id",
                "Sincronización completada. Se agregaron <strong>$agregados</strong> módulo(s) con acceso de lectura."
            );
        } catch (\Throwable $e) {
            error_log('[RolController] sincronizar: ' . $e->getMessage());
            $this->redirectError("/roles/permisos/$id", 'Error al sincronizar módulos.');
        }
    }

}