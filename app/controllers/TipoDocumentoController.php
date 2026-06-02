<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\TipoDocumentoModel;

/**
 * CRUD de Tipos de Documento.
 */
class TipoDocumentoController extends Controller
{
    private TipoDocumentoModel $model;

    public function __construct()
    {
        $this->model = new TipoDocumentoModel();
    }

    public function index(): void
    {
        $this->view('empresa/tipo_documentos/index', [
            'pageTitle' => 'Tipos de Documento',
            'tipos'     => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        $this->view('empresa/tipo_documentos/form', [
            'pageTitle' => 'Crear Tipo de Documento',
            'item'      => null,
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data   = Request::only(['tipo_documento', 'sigla_tipo_documento', 'estado']);
        $errors = $this->validate($data, [
            'tipo_documento'       => 'required|max:100',
            'sigla_tipo_documento' => 'required|max:5',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            Session::setOldInput($data);
            $this->redirect('/tipos-documento/crear');
            return;
        }

        $this->model->crear(
            $data['tipo_documento'],
            $data['sigla_tipo_documento'],
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('tipo_documentos', 'CREAR', 'tipo_documento', null, null, $data);
        $this->redirectSuccess('/tipos-documento', 'Tipo de documento creado.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('empresa/tipo_documentos/form', [
            'pageTitle' => 'Editar Tipo de Documento',
            'item'      => $item,
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes  = $this->model->find($id);
        $data   = Request::only(['tipo_documento', 'sigla_tipo_documento', 'estado']);
        $errors = $this->validate($data, [
            'tipo_documento'       => 'required|max:100',
            'sigla_tipo_documento' => 'required|max:5',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect("/tipos-documento/editar/$id");
            return;
        }

        $this->model->actualizar($id, $data['tipo_documento'], $data['sigla_tipo_documento'], $data['estado'] ?? 'ACTIVO');
        registrarAuditoria('tipo_documentos', 'EDITAR', 'tipo_documento', $id, $antes, $data);
        $this->redirectSuccess('/tipos-documento', 'Tipo de documento actualizado.');
    }

    public function eliminar(int $id): void
    {
        Csrf::verify();
        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/tipos-documento', 'Tipo de documento inactivado.');
    }
}
