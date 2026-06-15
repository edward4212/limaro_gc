<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\ModuloModel;

/**
 * ModuloController
 *
 * Gestión del árbol de módulos del sistema.
 * Toda la lógica de BD está en ModuloModel (corrige SEC-001: SQL injection
 * por interpolación directa de $id y $excluirId en queries).
 */
class ModuloController extends Controller
{
    private ModuloModel $model;

    public function __construct()
    {
        $this->model = new ModuloModel();
    }

    public function index(): void
    {
        $this->view('seguridad/modulos/index', [
            'pageTitle' => 'Módulos del Sistema',
            'modulos'   => $this->model->listarConJerarquia(),
        ]);
    }

    public function crear(): void
    {
        $this->view('seguridad/modulos/form', [
            'pageTitle' => 'Nuevo Módulo',
            'item'      => null,
            'jerarquia' => $this->model->construirArbol(
                $this->model->activosParaJerarquia()
            ),
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['nombre','codigo','url','icono','id_padre','orden','estado']);

        if (empty(trim($data['nombre'] ?? ''))) {
            Session::flash('error', 'El nombre es obligatorio.');
            $this->redirect('/modulos/crear');
            return;
        }

        $idNuevo = $this->model->crear(
            strtolower(preg_replace('/\s+/', '_', trim($data['codigo'] ?? $data['nombre']))),
            trim($data['nombre']),
            trim($data['icono']  ?? 'bi-circle'),
            trim($data['url']    ?? '') ?: null,
            ((int)($data['id_padre'] ?? 0)) ?: null,
            (int)($data['orden']  ?? 0),
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('seguridad', 'CREAR', 'modulo', $idNuevo, null, ['nombre' => $data['nombre']]);
        $this->redirectSuccess('/modulos', 'Módulo creado. Asigne permisos desde Roles → Permisos.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->porId($id);
        if (!$item) $this->abort(404);

        $this->view('seguridad/modulos/form', [
            'pageTitle' => 'Editar Módulo',
            'item'      => $item,
            'jerarquia' => $this->model->construirArbol(
                $this->model->activosParaJerarquia($id)
            ),
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $data = Request::only(['nombre','codigo','url','icono','id_padre','orden','estado']);

        $this->model->actualizar(
            $id,
            strtolower(preg_replace('/\s+/', '_', trim($data['codigo'] ?? ''))),
            trim($data['nombre']  ?? ''),
            trim($data['icono']   ?? 'bi-circle'),
            trim($data['url']     ?? '') ?: null,
            ((int)($data['id_padre'] ?? 0)) ?: null,
            (int)($data['orden']  ?? 0),
            $data['estado'] ?? 'ACTIVO'
        );

        registrarAuditoria('seguridad', 'EDITAR', 'modulo', $id, null, ['nombre' => $data['nombre'] ?? '']);
        $this->redirectSuccess('/modulos', 'Módulo actualizado.');
    }
}
