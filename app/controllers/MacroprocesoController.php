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
        $macroprocesos = $this->model->listar();
        // CA-2: pasar conteo de procesos activos por macroproceso
        $conteos = [];
        foreach ($macroprocesos as $mp) {
            $conteos[$mp['id_macroproceso']] = $this->model->contarProcesos($mp['id_macroproceso']);
        }
        $this->view('empresa/macroprocesos/index', [
            'pageTitle'     => 'Macroprocesos',
            'macroprocesos' => $macroprocesos,
            'conteosProcesos' => $conteos,
        ]);
    }

    /** GET /macroprocesos/crear */
    public function crear(): void
    {
        Session::clearOldInput();
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
            Session::flash('error', 'El nombre del macroproceso es obligatorio.');
            Session::setOldInput($data);
            $this->redirect('/macroprocesos/crear');
            return;
        }

        // HU-002: validar nombre duplicado antes del INSERT
        $nombre = strtoupper(trim($data['macroproceso']));
        if ($this->model->existeNombre($nombre)) {
            Session::flash('error', "Ya existe un macroproceso con el nombre \"$nombre\". Ingrese un nombre diferente.");
            Session::setOldInput($data);
            $this->redirect('/macroprocesos/crear');
            return;
        }

        $this->model->crear(
            $nombre,
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
        if (!$item) $this->abort(404, 'Macroproceso no encontrado.');

        $this->view('empresa/macroprocesos/form', [
            'pageTitle' => 'Editar Macroproceso',
            'item'      => $item,
        ]);
    }

    /** POST /macroprocesos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes  = $this->model->find($id);
        $data   = Request::only(['macroproceso', 'objetivo', 'estado']);
        $errors = $this->validate($data, [
            'macroproceso' => 'required|max:200',
        ]);

        if ($errors) {
            Session::flash('error', 'El nombre del macroproceso es obligatorio.');
            Session::setOldInput($data);
            $this->redirect("/macroprocesos/editar/$id");
            return;
        }

        // HU-002: validar nombre duplicado excluyendo el registro actual
        $nombre = strtoupper(trim($data['macroproceso']));
        if ($this->model->existeNombre($nombre, $id)) {
            Session::flash('error', "Ya existe otro macroproceso con el nombre <strong>\"$nombre\"</strong>.");
            Session::setOldInput($data);
            $this->redirect("/macroprocesos/editar/$id");
            return;
        }

        $this->model->actualizar(
            $id,
            $nombre,
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
        if (!$antes) $this->abort(404);

        // CA-1 y CA-3: bloquear si tiene procesos activos vinculados
        $nProcesos = $this->model->contarProcesos($id);
        if ($nProcesos > 0) {
            Session::flash('error',
                "No se puede inactivar el macroproceso <strong>{$antes['macroproceso']}</strong> "
                . "porque tiene <strong>{$nProcesos} proceso(s) activo(s)</strong> asignado(s). "
                . "Inactivar primero los procesos vinculados."
            );
            $this->redirect('/macroprocesos');
            return;
        }

        $this->model->inactivar($id);
        registrarAuditoria('macroprocesos', 'ELIMINAR', 'macroproceso', $id, $antes, null);
        $this->redirectSuccess('/macroprocesos', 'Macroproceso inactivado correctamente.');
    }
}
