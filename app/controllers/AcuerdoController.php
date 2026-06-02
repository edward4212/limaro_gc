<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\AcuerdoModel;
use App\Models\TipoDocumentoModel;
use App\Models\ArchivoModel;

/**
 * Controlador de Acuerdos.
 */
class AcuerdoController extends Controller
{
    private AcuerdoModel       $model;
    private TipoDocumentoModel $tipoModel;
    private ArchivoModel       $archivoModel;

    public function __construct()
    {
        $this->model        = new AcuerdoModel();
        $this->tipoModel    = new TipoDocumentoModel();
        $this->archivoModel = new ArchivoModel();
    }

    /** GET /acuerdos */
    public function index(): void
    {
        $this->view('empresa/acuerdos/index', [
            'pageTitle' => 'Acuerdos',
            'acuerdos'  => $this->model->listar(),
        ]);
    }

    /** GET /acuerdos/vigentes */
    public function vigentes(): void
    {
        $año = Request::get('año') ? (int) Request::get('año') : null;
        $this->view('documentos/acuerdos_vigentes', [
            'pageTitle' => 'Acuerdos Vigentes',
            'acuerdos'  => $this->model->vigentes($año),
            'años'      => $this->model->años(),
            'filtroAño' => $año,
        ]);
    }

    /** GET /acuerdos/crear */
    public function crear(): void
    {
        // Buscar el tipo 'ACUERDO' automáticamente
        $tipoAcuerdo = $this->tipoModel->buscarPorNombre('ACUERDO');
        $this->view('empresa/acuerdos/form', [
            'pageTitle'    => 'Nuevo Acuerdo',
            'item'         => null,
            'tipos'        => $this->tipoModel->activos(),
            'tipoAcuerdo'  => $tipoAcuerdo,
        ]);
    }

    /** POST /acuerdos/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only([
            'año_acuerdo', 'numero_acuerdo', 'nombre_acuerdo',
            'id_tipo_documento', 'fecha_aprobacion', 'acta_aprobacion',
        ]);

        $errors = $this->validate($data, [
            'año_acuerdo'      => 'required|integer',
            'numero_acuerdo'   => 'required|max:20',
            'nombre_acuerdo'   => 'required|max:300',
            'id_tipo_documento'=> 'required|integer',
            'acta_aprobacion'  => 'required|integer',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/acuerdos/crear');
            return;
        }

        $id = $this->model->crear([
            'año_acuerdo'       => (int) $data['año_acuerdo'],
            'numero_acuerdo'    => trim($data['numero_acuerdo']),
            'nombre_acuerdo'    => trim($data['nombre_acuerdo']),
            'id_tipo_documento' => (int) $data['id_tipo_documento'],
            'fecha_aprobacion'  => $data['fecha_aprobacion'] ?: null,
            'acta_aprobacion'   => (int) ($data['acta_aprobacion'] ?? 0),
            'documento'         => '',
        ]);

        // Subir PDF si se envió
        if (Request::hasFile('archivo_pdf')) {
            try {
                $upload = subirArchivo(
                    $_FILES['archivo_pdf'],
                    'acuerdos',
                    ['application/pdf'],
                    20971520
                );
                $this->archivoModel->registrar('ACUERDO', $id, $upload, Auth::get('usuario'));
            } catch (\RuntimeException $e) {
                error_log('[Limaro SGC] Acuerdo creado, pero el archivo no pudo subirse: ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
                Session::flash('warning', 'Acuerdo creado, pero el archivo no pudo subirse. Revise el log del servidor.');
                $this->redirect('/acuerdos');
            }
        }

        registrarAuditoria('acuerdos', 'CREAR', 'acuerdo', $id, null, $data);
        $this->redirectSuccess('/acuerdos', 'Acuerdo registrado exitosamente.');
    }

    /** GET /acuerdos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $tipoAcuerdo = $this->tipoModel->buscarPorNombre('ACUERDO');
        $this->view('empresa/acuerdos/form', [
            'pageTitle'   => 'Editar Acuerdo',
            'item'        => $item,
            'tipos'       => $this->tipoModel->activos(),
            'tipoAcuerdo' => $tipoAcuerdo,
        ]);
    }

    /** POST /acuerdos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $data  = Request::only([
            'año_acuerdo', 'numero_acuerdo', 'nombre_acuerdo',
            'id_tipo_documento', 'fecha_aprobacion', 'acta_aprobacion',
        ]);

        $this->model->actualizar($id, [
            'año_acuerdo'       => (int) $data['año_acuerdo'],
            'numero_acuerdo'    => trim($data['numero_acuerdo']),
            'nombre_acuerdo'    => trim($data['nombre_acuerdo']),
            'id_tipo_documento' => (int) $data['id_tipo_documento'],
            'fecha_aprobacion'  => $data['fecha_aprobacion'] ?: null,
            'acta_aprobacion'   => (int) ($data['acta_aprobacion'] ?? 0),
        ]);

        // Reemplazar PDF si se envió uno nuevo
        if (Request::hasFile('archivo_pdf')) {
            try {
                $upload = subirArchivo($_FILES['archivo_pdf'], 'acuerdos', ['application/pdf']);
                $this->archivoModel->registrar('ACUERDO', $id, $upload, Auth::get('usuario'));
            } catch (\RuntimeException $e) {
                error_log('[Limaro SGC] Acuerdo actualizado, pero el archivo no pudo reemplazarse: ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
                Session::flash('warning', 'Acuerdo actualizado, pero el archivo no pudo reemplazarse. Revise el log del servidor.');
            }
        }

        registrarAuditoria('acuerdos', 'EDITAR', 'acuerdo', $id, $antes, $data);
        $this->redirectSuccess('/acuerdos', 'Acuerdo actualizado.');
    }

    /** POST /acuerdos/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        // La tabla acuerdos no tiene columna estado, así que hacemos DELETE lógico
        // eliminando el registro físicamente.
        $this->model->delete($id);
        $this->redirectSuccess('/acuerdos', 'Acuerdo eliminado.');
    }
}
