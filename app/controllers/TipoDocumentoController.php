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
        $tipos = $this->model->listar() ?? [];
        $conteosDoc = [];
        foreach ($tipos as $t) {
            $conteosDoc[$t['id_tipo_documento']] = $this->model->contarDocumentos($t['id_tipo_documento']);
        }
        $this->view('empresa/tipo_documentos/index', [
            'pageTitle' => 'Tipos de Documento',
            'tipos'            => $tipos,
            'conteosDocumentos' => $conteosDoc,
        ]);
    }

    public function crear(): void
    {
        Session::clearOldInput();
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
            'sigla_tipo_documento' => 'required|max:2',
        ]);

        if ($errors) {
            Session::flash('error', 'Nombre y sigla son obligatorios.');
            Session::setOldInput($data);
            $this->redirect('/tipos-documento/crear');
            return;
        }

        // CA-1/CA-3: validar máximo 2 caracteres server-side
        $sigla  = strtoupper(trim($data['sigla_tipo_documento']));
        if (mb_strlen($sigla) > 2) {
            Session::flash('error', "La sigla no puede tener más de <strong>2 caracteres</strong>. Valor ingresado: \"$sigla\"");
            $this->redirect('/tipos-documento/crear');
            return;
        }
        // HU-002: validar sigla y nombre duplicados antes del INSERT
        $nombre = strtoupper(trim($data['tipo_documento']));

        if ($this->model->existeSigla($sigla)) {
            Session::flash('error', "La sigla <strong>\"$sigla\"</strong> ya está en uso por otro tipo de documento.");
            Session::setOldInput($data);
            $this->redirect('/tipos-documento/crear');
            return;
        }

        if ($this->model->existeNombre($nombre)) {
            Session::flash('error', "Ya existe un tipo de documento con el nombre <strong>\"$nombre\"</strong>.");
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
            'sigla_tipo_documento' => 'required|max:2',
        ]);

        if ($errors) {
            Session::flash('error', 'Nombre y sigla son obligatorios.');
            $this->redirect("/tipos-documento/editar/$id");
            return;
        }

        // CA-2: validar máximo 2 caracteres server-side en edición
        $siglaU = strtoupper(trim($data['sigla_tipo_documento'] ?? ''));
        if (mb_strlen($siglaU) > 2) {
            Session::flash('error', "La sigla no puede tener más de <strong>2 caracteres</strong>. Valor ingresado: \"$siglaU\"");
            $this->redirect("/tipos-documento/editar/$id");
            return;
        }
        // HU-002: validar duplicados excluyendo el registro actual
        $sigla  = strtoupper(trim($data['sigla_tipo_documento']));
        $nombre = strtoupper(trim($data['tipo_documento']));

        if ($this->model->existeSigla($sigla, $id)) {
            Session::flash('error', "La sigla <strong>\"$sigla\"</strong> ya está en uso por otro tipo de documento.");
            $this->redirect("/tipos-documento/editar/$id");
            return;
        }

        if ($this->model->existeNombre($nombre, $id)) {
            Session::flash('error', "Ya existe otro tipo de documento con el nombre <strong>\"$nombre\"</strong>.");
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
        $antes = $this->model->find($id);
        if (!$antes) $this->abort(404);

        $nDocs = $this->model->contarDocumentos($id);
        if ($nDocs > 0) {
            Session::flash('error',
                "No se puede inactivar el tipo de documento <strong>{$antes['tipo_documento']}</strong>: "
                . "tiene <strong>{$nDocs} documento(s) activo(s)</strong> vinculado(s). "
                . "Reasigne o inactivar primero los documentos de este tipo."
            );
            $this->redirect('/tipos-documento');
            return;
        }

        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/tipos-documento', 'Tipo de documento inactivado correctamente.');
    }
}
