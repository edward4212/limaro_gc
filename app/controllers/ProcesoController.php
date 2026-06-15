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

        $procesos   = $this->model->listar();
        $conteosDoc = [];
        foreach ($procesos as $p) {
            $conteosDoc[$p['id_proceso']] = $this->model->contarDocumentos($p['id_proceso']);
        }
        $this->view('empresa/procesos/index', [
            'pageTitle'          => 'Procesos',
            'procesos'           => $procesos,
            'conteosDocumentos'  => $conteosDoc,
        ]);
    }

    /** GET /procesos/crear */
    public function crear(): void
    {
        Session::clearOldInput();
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
            'sigla_proceso'   => 'required|max:2',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/procesos/crear');
            return;
        }

        // HU-002: validar sigla duplicada dentro del mismo macroproceso
        $sigla   = strtoupper(trim($data['sigla_proceso']));

        // CA-3: validar máximo 2 caracteres (server-side)
        if (mb_strlen($sigla) > 2) {
            Session::flash('error', "La sigla no puede tener más de <strong>2 caracteres</strong>. Valor ingresado: \"$sigla\"");;
            $this->redirect('/procesos/crear');
            return;
        }
        $idMacro = (int)$data['id_macroproceso'];
        if ($this->model->existeSigla($sigla, $idMacro)) {
            Session::flash('error', "La sigla <strong>\"$sigla\"</strong> ya existe en este macroproceso.");
            Session::setOldInput($data);
            $this->redirect('/procesos/crear');
            return;
        }

        $this->model->crear(
            $idMacro,
            strtoupper(trim($data['proceso'])),
            $sigla,
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
            'sigla_proceso'   => 'required|max:2',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect("/procesos/editar/$id");
            return;
        }

        // HU-002: validar sigla duplicada excluyendo el registro actual
        $sigla   = strtoupper(trim($data['sigla_proceso']));

        // CA-3: validar máximo 2 caracteres (server-side)
        if (mb_strlen($sigla) > 2) {
            Session::flash('error', "La sigla no puede tener más de <strong>2 caracteres</strong>. Valor ingresado: \"$sigla\"");;
            $this->redirect('/procesos/crear');
            return;
        }
        $idMacro = (int)$data['id_macroproceso'];
        if ($this->model->existeSigla($sigla, $idMacro, $id)) {
            Session::flash('error', "La sigla <strong>\"$sigla\"</strong> ya existe en este macroproceso.");
            $this->redirect("/procesos/editar/$id");
            return;
        }

        $this->model->actualizar($id,
            $idMacro,
            strtoupper(trim($data['proceso'])),
            $sigla,
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
        if (!$antes) $this->abort(404);

        // CA-1, CA-2, CA-3: bloquear si tiene documentos activos
        $nDocs = $this->model->contarDocumentos($id);
        if ($nDocs > 0) {
            Session::flash('error',
                "No se puede inactivar el proceso <strong>{$antes['proceso']}</strong>: "
                . "tiene <strong>{$nDocs} documento(s) activo(s)</strong> vinculado(s). "
                . "Reasigne o inactivar primero los documentos del proceso."
            );
            $this->redirect('/procesos');
            return;
        }

        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('procesos', 'ELIMINAR', 'proceso', $id, $antes, null);
        $this->redirectSuccess('/procesos', 'Proceso inactivado correctamente.');
    }
}
