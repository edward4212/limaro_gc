<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\MacroprocesoModel;

/**
 * CRUD de Macroprocesos.
 */
class MacroprocesoController extends Controller
{
    private MacroprocesoModel $model;

    public function __construct()
    {
        $this->model = new MacroprocesoModel();
    }

    /** GET /macroprocesos */
    public function index(): void
    {
        $this->view('empresa/macroprocesos/index', [
            'pageTitle'    => 'Macroprocesos',
            'macroprocesos' => $this->model->listar(),
        ]);
    }

    /** GET /macroprocesos/crear */
    public function crear(): void
    {
        $this->view('empresa/macroprocesos/form', [
            'pageTitle' => 'Crear Macroproceso',
            'item'      => null,
        ]);
    }

    /** POST /macroprocesos/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data   = Request::only(['macroproceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'macroproceso' => 'required|max:200',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/macroprocesos/crear');
            return;
        }

        $this->model->crear(
            strtoupper(trim($data['macroproceso'])),
            trim($data['objetivo'] ?? ''),
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('macroprocesos', 'CREAR', 'macroproceso', null, null, $data);
        $this->redirectSuccess('/macroprocesos', 'Macroproceso creado exitosamente.');
    }

    /** GET /macroprocesos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) {
            $this->abort(404, 'Macroproceso no encontrado.');
        }

        $this->view('empresa/macroprocesos/form', [
            'pageTitle' => 'Editar Macroproceso',
            'item'      => $item,
        ]);
    }

    /** POST /macroprocesos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $data  = Request::only(['macroproceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'macroproceso' => 'required|max:200',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            Session::setOldInput($data);
            $this->redirect("/macroprocesos/editar/$id");
            return;
        }

        $this->model->actualizar($id,
            strtoupper(trim($data['macroproceso'])),
            trim($data['objetivo'] ?? ''),
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('macroprocesos', 'EDITAR', 'macroproceso', $id, $antes, $data);
        $this->redirectSuccess('/macroprocesos', 'Macroproceso actualizado.');
    }

    /** POST /macroprocesos/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $this->model->inactivar($id);
        registrarAuditoria('macroprocesos', 'ELIMINAR', 'macroproceso', $id, $antes, null);
        $this->redirectSuccess('/macroprocesos', 'Macroproceso inactivado.');
    }
}
