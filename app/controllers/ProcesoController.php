<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\ProcesoModel;
use App\Models\MacroprocesoModel;

/**
 * CRUD de Procesos.
 */
class ProcesoController extends Controller
{
    private ProcesoModel     $model;
    private MacroprocesoModel $macroModel;

    public function __construct()
    {
        $this->model      = new ProcesoModel();
        $this->macroModel = new MacroprocesoModel();
    }

    /** GET /procesos */
    public function index(): void
    {
        // Ajax para select dependiente de macroproceso
        if (Request::get('ajax') === '1') {
            $idMacro = (int) Request::get('id_macroproceso', 0);
            $this->json($this->model->porMacroproceso($idMacro));
            return;
        }

        $this->view('empresa/procesos/index', [
            'pageTitle' => 'Procesos',
            'procesos'  => $this->model->listar(),
        ]);
    }

    /** GET /procesos/crear */
    public function crear(): void
    {
        $this->view('empresa/procesos/form', [
            'pageTitle'   => 'Crear Proceso',
            'item'        => null,
            'macroprocesos' => $this->macroModel->activos(),
        ]);
    }

    /** POST /procesos/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data   = Request::only(['id_macroproceso', 'proceso', 'sigla_proceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'id_macroproceso' => 'required|integer',
            'proceso'         => 'required|max:200',
            'sigla_proceso'   => 'required|max:10',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/procesos/crear');
            return;
        }

        $this->model->crear(
            (int) $data['id_macroproceso'],
            strtoupper(trim($data['proceso'])),
            $data['sigla_proceso'],
            trim($data['objetivo'] ?? ''),
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('procesos', 'CREAR', 'proceso', null, null, $data);
        $this->redirectSuccess('/procesos', 'Proceso creado exitosamente.');
    }

    /** GET /procesos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        $this->view('empresa/procesos/form', [
            'pageTitle'     => 'Editar Proceso',
            'item'          => $item,
            'macroprocesos' => $this->macroModel->activos(),
        ]);
    }

    /** POST /procesos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes  = $this->model->find($id);
        $data   = Request::only(['id_macroproceso', 'proceso', 'sigla_proceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'id_macroproceso' => 'required|integer',
            'proceso'         => 'required|max:200',
            'sigla_proceso'   => 'required|max:10',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect("/procesos/editar/$id");
            return;
        }

        $this->model->actualizar($id,
            (int) $data['id_macroproceso'],
            strtoupper(trim($data['proceso'])),
            $data['sigla_proceso'],
            trim($data['objetivo'] ?? ''),
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('procesos', 'EDITAR', 'proceso', $id, $antes, $data);
        $this->redirectSuccess('/procesos', 'Proceso actualizado.');
    }

    /** POST /procesos/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('procesos', 'ELIMINAR', 'proceso', $id, $antes, null);
        $this->redirectSuccess('/procesos', 'Proceso inactivado.');
    }
}
