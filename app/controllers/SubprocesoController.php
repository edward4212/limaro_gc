<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\SubprocesoModel;
use App\Models\ProcesoModel;
use App\Models\MacroprocesoModel;

/**
 * CRUD de Subprocesos.
 * Ruta base: /subprocesos
 */
class SubprocesoController extends Controller
{
    private SubprocesoModel  $model;
    private ProcesoModel     $procesoModel;
    private MacroprocesoModel $macroModel;

    public function __construct()
    {
        $this->model        = new SubprocesoModel();
        $this->procesoModel = new ProcesoModel();
        $this->macroModel   = new MacroprocesoModel();
    }

    /**
     * GET /subprocesos
     * También responde Ajax: /subprocesos?ajax=1&id_proceso=X
     */
    public function index(): void
    {
        // Endpoint Ajax para select dependiente en formulario Documento
        if (Request::get('ajax') === '1') {
            $idProceso = (int) Request::get('id_proceso', 0);
            $this->json($this->model->porProceso($idProceso));
            return;
        }

        $this->view('empresa/subprocesos/index', [
            'pageTitle'   => 'Subprocesos',
            'subprocesos' => $this->model->listar(),
        ]);
    }

    /** GET /subprocesos/crear */
    public function crear(): void
    {
        $this->view('empresa/subprocesos/form', [
            'pageTitle'     => 'Crear Subproceso',
            'item'          => null,
            'macroprocesos' => $this->macroModel->activos(),
            'procesos'      => $this->procesoModel->activos(),
        ]);
    }

    /** POST /subprocesos/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data   = Request::only(['id_proceso', 'subproceso', 'sigla_subproceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'id_proceso'       => 'required|integer',
            'subproceso'       => 'required|max:200',
            'sigla_subproceso' => 'required|max:10',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/subprocesos/crear');
            return;
        }

        try {
            $id = $this->model->crear(
                (int) $data['id_proceso'],
                $data['subproceso'],
                $data['sigla_subproceso'],
                $data['objetivo'] ?? '',
                $data['estado']   ?? 'ACTIVO'
            );
            registrarAuditoria('subprocesos', 'CREAR', 'subproceso', $id, null, $data);
            $this->redirectSuccess('/subprocesos', 'Subproceso creado exitosamente.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al crear el subproceso: Error interno. Contacte al administrador.');
            Session::setOldInput($data);
            $this->redirect('/subprocesos/crear');
        }
    }

    /** GET /subprocesos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        $this->view('empresa/subprocesos/form', [
            'pageTitle'     => 'Editar Subproceso',
            'item'          => $item,
            'macroprocesos' => $this->macroModel->activos(),
            'procesos'      => $this->procesoModel->activos(),
        ]);
    }

    /** POST /subprocesos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes  = $this->model->find($id);
        $data   = Request::only(['id_proceso', 'subproceso', 'sigla_subproceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'id_proceso'       => 'required|integer',
            'subproceso'       => 'required|max:200',
            'sigla_subproceso' => 'required|max:10',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect("/subprocesos/editar/$id");
            return;
        }

        try {
            $this->model->actualizar(
                $id,
                (int) $data['id_proceso'],
                $data['subproceso'],
                $data['sigla_subproceso'],
                $data['objetivo'] ?? '',
                $data['estado']   ?? 'ACTIVO'
            );
            registrarAuditoria('subprocesos', 'EDITAR', 'subproceso', $id, $antes, $data);
            $this->redirectSuccess('/subprocesos', 'Subproceso actualizado.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al actualizar: Error interno. Contacte al administrador.');
            $this->redirect("/subprocesos/editar/$id");
        }
    }

    /** POST /subprocesos/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('subprocesos', 'ELIMINAR', 'subproceso', $id, $antes, null);
        $this->redirectSuccess('/subprocesos', 'Subproceso inactivado.');
    }
}
