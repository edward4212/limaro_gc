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

        $subs = $this->model->listar() ?? [];
        $conteosDoc = [];
        foreach ($subs as $s) {
            $conteosDoc[$s['id_subproceso']] = $this->model->contarDocumentos($s['id_subproceso']);
        }

        $this->view('empresa/subprocesos/index', [
            'pageTitle'          => 'Subprocesos',
            'subprocesos'        => $subs,
            'conteosDocumentos'  => $conteosDoc,
        ]);
    }

    /** GET /subprocesos/crear */
    public function crear(): void
    {
        Session::clearOldInput();
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
            'id_proceso' => 'required|integer',
            'subproceso' => 'required|max:200',
        ]);

        if ($errors) {
            Session::flash('error', 'Proceso y nombre del subproceso son obligatorios.');
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
            error_log('[SubprocesoController::guardar] ' . $e->getMessage());
            Session::flash('error', 'Error al crear el subproceso. Intente nuevamente.');
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
            'id_proceso' => 'required|integer',
            'subproceso' => 'required|max:200',
        ]);

        if ($errors) {
            Session::flash('error', 'Proceso y nombre son obligatorios.');
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
        if (!$antes) $this->abort(404);

        $nDocs = $this->model->contarDocumentos($id);
        if ($nDocs > 0) {
            Session::flash('error',
                "No se puede inactivar el subproceso <strong>{$antes['subproceso']}</strong>: "
                . "tiene <strong>{$nDocs} documento(s) activo(s)</strong> vinculado(s). "
                . "Reasigne o inactivar primero los documentos del subproceso."
            );
            $this->redirect('/subprocesos');
            return;
        }

        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('subprocesos', 'ELIMINAR', 'subproceso', $id, $antes, null);
        $this->redirectSuccess('/subprocesos', 'Subproceso inactivado correctamente.');
    }
}
