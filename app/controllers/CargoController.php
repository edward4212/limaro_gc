<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\CargoModel;
use App\Models\ArchivoModel;

/**
 * CRUD de Cargos.
 */
class CargoController extends Controller
{
    private CargoModel   $model;
    private ArchivoModel $archivoModel;

    public function __construct()
    {
        $this->model        = new CargoModel();
        $this->archivoModel = new ArchivoModel();
    }

    public function index(): void
    {
        $this->view('seguridad/cargos/index', [
            'pageTitle' => 'Cargos',
            'cargos'    => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        $this->view('seguridad/cargos/form', ['pageTitle' => 'Crear Cargo', 'item' => null]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['cargo', 'descripcion', 'estado']);
        $errors = $this->validate($data, ['cargo' => 'required|max:150']);
        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect('/cargos/crear');
            return;
        }

        $id = $this->model->crear([
            'cargo'       => strtoupper(trim($data['cargo'])),
            'descripcion' => trim($data['descripcion'] ?? ''),
            'estado'      => $data['estado'] ?? 'ACTIVO',
        ]);

        if (Request::hasFile('manual_funciones')) {
            try {
                $upload = subirArchivo($_FILES['manual_funciones'], 'cargos/manuales', ['application/pdf'], 20971520);
                $this->archivoModel->registrar('CARGO', $id, $upload, Auth::get('usuario'));
                // Guardar nombre original en la tabla cargo
                $this->model->actualizar($id, ['manual_funciones' => $upload['nombre_storage']]);
            } catch (\RuntimeException $e) {
                error_log('[Limaro SGC] Cargo creado, manual no subido: ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
                Session::flash('warning', 'Cargo creado, manual no subido. Revise el log del servidor.');
            }
        }

        $this->redirectSuccess('/cargos', 'Cargo creado exitosamente.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('seguridad/cargos/form', ['pageTitle' => 'Editar Cargo', 'item' => $item]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $data = Request::only(['cargo', 'descripcion', 'estado']);
        $this->model->actualizar($id, [
            'cargo'       => strtoupper(trim($data['cargo'])),
            'descripcion' => trim($data['descripcion'] ?? ''),
            'estado'      => $data['estado'] ?? 'ACTIVO',
        ]);

        if (Request::hasFile('manual_funciones')) {
            try {
                $upload = subirArchivo($_FILES['manual_funciones'], 'cargos/manuales', ['application/pdf'], 20971520);
                $this->archivoModel->registrar('CARGO', $id, $upload, Auth::get('usuario'));
                $this->model->actualizar($id, ['manual_funciones' => $upload['nombre_storage']]);
            } catch (\RuntimeException $e) {
                error_log('[Limaro SGC] Cargo actualizado, manual no reemplazado: ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
                Session::flash('warning', 'Cargo actualizado, manual no reemplazado. Revise el log del servidor.');
            }
        }

        $this->redirectSuccess('/cargos', 'Cargo actualizado.');
    }

    public function eliminar(int $id): void
    {
        Csrf::verify();
        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/cargos', 'Cargo inactivado.');
    }
}
