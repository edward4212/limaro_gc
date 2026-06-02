<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\EmpresaModel;

/**
 * Controlador de datos de la empresa.
 * Solo existe un registro (id_empresa = 1).
 * Ruta: GET /empresa/editar  |  POST /empresa/editar
 */
class EmpresaController extends Controller
{
    private EmpresaModel $model;

    public function __construct()
    {
        $this->model = new EmpresaModel();
    }

    /** GET /empresa/editar */
    public function editar(): void
    {
        $empresa = $this->model->getDatos();
        if (!$empresa) $this->abort(404);

        $this->view('empresa/empresa/editar', [
            'pageTitle' => 'Datos de la Empresa',
            'empresa'   => $empresa,
        ]);
    }

    /** POST /empresa/editar */
    public function actualizar(): void
    {
        Csrf::verify();

        $empresa = $this->model->getDatos();
        if (!$empresa) $this->abort(404);

        // Campos de texto
        $data = Request::only([
            'nombre_empresa', 'mision', 'vision',
            'politica_calidad', 'objetivos_calidad', 'URL',
        ]);

        $errors = $this->validate($data, [
            'nombre_empresa' => 'required|max:500',
        ]);

        if ($errors) {
            Session::flash('error', 'El nombre de la empresa es obligatorio.');
            $this->redirect('/empresa/editar');
            return;
        }

        // ── Logo ────────────────────────────────────────────────────────
        if (Request::hasFile('logo') && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            try {
                // Eliminar logo anterior si existe en storage
                if (!empty($empresa['logo']) && str_starts_with($empresa['logo'], '/storage/')) {
                    eliminarArchivo($empresa['logo']);
                }
                $upload       = subirArchivo(
                    $_FILES['logo'],
                    'empresa/logo',
                    ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    5242880 // 5 MB
                );
                $data['logo'] = $upload['ruta_relativa'];
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir logo: Error interno. Contacte al administrador.');
                $this->redirect('/empresa/editar');
                return;
            }
        }

        // ── Organigrama ─────────────────────────────────────────────────
        if (Request::hasFile('organigrama') && $_FILES['organigrama']['error'] === UPLOAD_ERR_OK) {
            try {
                if (!empty($empresa['organigrama']) && str_starts_with($empresa['organigrama'], '/storage/')) {
                    eliminarArchivo($empresa['organigrama']);
                }
                $upload              = subirArchivo(
                    $_FILES['organigrama'],
                    'empresa/organigrama',
                    ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                    20971520 // 20 MB
                );
                $data['organigrama'] = $upload['ruta_relativa'];
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir organigrama: Error interno. Contacte al administrador.');
                $this->redirect('/empresa/editar');
                return;
            }
        }

        // ── Mapa de Procesos ─────────────────────────────────────────────
        if (Request::hasFile('mapa_procesos') && $_FILES['mapa_procesos']['error'] === UPLOAD_ERR_OK) {
            try {
                if (!empty($empresa['mapa_procesos']) && str_starts_with($empresa['mapa_procesos'], '/storage/')) {
                    eliminarArchivo($empresa['mapa_procesos']);
                }
                $upload                = subirArchivo(
                    $_FILES['mapa_procesos'],
                    'empresa/mapa_procesos',
                    ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                    20971520 // 20 MB
                );
                $data['mapa_procesos'] = $upload['ruta_relativa'];
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir mapa de procesos: Error interno. Contacte al administrador.');
                $this->redirect('/empresa/editar');
                return;
            }
        }

        try {
            $this->model->actualizar($data);
            registrarAuditoria('empresa', 'EDITAR', 'empresa', 1, $empresa, $data);
            $this->redirectSuccess('/empresa/editar', 'Datos de la empresa actualizados correctamente.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al guardar: Error interno. Contacte al administrador.');
            $this->redirect('/empresa/editar');
        }
    }
}
