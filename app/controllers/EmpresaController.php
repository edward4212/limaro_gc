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


        // Helper: construye array para subirArchivo soportando base64 (app.js) y multipart
        $prepFile = function(string $campo) {
            $file = $_FILES[$campo] ?? ['error' => UPLOAD_ERR_NO_FILE];
            $file['field_name'] = $campo; // necesario para que subirArchivo busque {campo}_b64
            $tieneNativo = ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
            $tieneB64    = !empty($_POST[$campo . '_b64']);
            return ['file' => $file, 'tiene' => $tieneNativo || $tieneB64];
        };
        // ── Logo ────────────────────────────────────────────────────────
        $fLogo = $prepFile('logo');
        if ($fLogo['tiene']) {
            try {
                if (!empty($empresa['logo']) && str_starts_with($empresa['logo'], '/storage/')) {
                    eliminarArchivo($empresa['logo']);
                }
                $upload       = subirArchivo(
                    $fLogo['file'],
                    'empresa/logo',
                    ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                    5242880
                );
                $data['logo'] = $upload['ruta_relativa'];
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir logo: Error interno. Contacte al administrador.');
                $this->redirect('/empresa/editar');
                return;
            }
        }

        // ── Organigrama ─────────────────────────────────────────────────
        $fOrg = $prepFile('organigrama');
        if ($fOrg['tiene']) {
            try {
                if (!empty($empresa['organigrama']) && str_starts_with($empresa['organigrama'], '/storage/')) {
                    eliminarArchivo($empresa['organigrama']);
                }
                $upload              = subirArchivo(
                    $fOrg['file'],
                    'empresa/organigrama',
                    ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                    20971520
                );
                $data['organigrama'] = $upload['ruta_relativa'];
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir organigrama: Error interno. Contacte al administrador.');
                $this->redirect('/empresa/editar');
                return;
            }
        }

        // ── Mapa de Procesos ─────────────────────────────────────────────
        $fMapa = $prepFile('mapa_procesos');
        if ($fMapa['tiene']) {
            try {
                if (!empty($empresa['mapa_procesos']) && str_starts_with($empresa['mapa_procesos'], '/storage/')) {
                    eliminarArchivo($empresa['mapa_procesos']);
                }
                $upload                = subirArchivo(
                    $fMapa['file'],
                    'empresa/mapa_procesos',
                    ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
                    20971520
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
            return;
        }
    }
}
