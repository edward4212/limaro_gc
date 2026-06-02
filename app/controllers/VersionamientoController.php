<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\VersionamientoModel;
use App\Models\DocumentoModel;

/**
 * Controlador de Versionamiento.
 */
class VersionamientoController extends Controller
{
    private VersionamientoModel $model;
    private DocumentoModel      $docModel;

    public function __construct()
    {
        $this->model    = new VersionamientoModel();
        $this->docModel = new DocumentoModel();
    }

    /** GET /versionamiento */
    public function index(): void
    {
        $this->view('documentos/versionamiento', [
            'pageTitle' => 'Versionamiento',
            'versiones' => $this->model->listar(),
        ]);
    }

    /** GET /versionamiento/documento/{id} */
    public function detalle(int $id): void
    {
        $doc = $this->docModel->find($id);
        if (!$doc) $this->abort(404, 'Documento no encontrado.');

        $this->view('documentos/versionamiento_detalle', [
            'pageTitle' => 'Historial: ' . $doc['nombre_documento'],
            'documento' => $doc,
            'versiones' => $this->model->porDocumento($id),
        ]);
    }

    /** GET /versionamiento/nueva/{id} */
    public function nueva(int $id): void
    {
        $doc = $this->docModel->find($id);
        if (!$doc) $this->abort(404);

        $ultimaVersion = $this->model->ultimaVersion($id);
        $siguiente     = $this->model->maxVersion($id) + 1;

        $this->view('documentos/versionamiento_form', [
            'pageTitle'     => 'Nueva Versión — ' . $doc['nombre_documento'],
            'documento'     => $doc,
            'ultimaVersion' => $ultimaVersion,
            'siguiente'     => $siguiente,
        ]);
    }

    /** POST /versionamiento/nueva/{id} */
    public function guardar(int $id): void
    {
        Csrf::verify();

        $doc = $this->docModel->find($id);
        if (!$doc) $this->abort(404);

        $data = Request::only([
            'descripcion_version', 'usuario_creacion',
            'usuario_revision', 'usuario_aprobacion',
            'fecha_aprobacion', 'estado_version',
        ]);

        $errors = $this->validate($data, [
            'descripcion_version' => 'required',
            'usuario_creacion'    => 'required|max:200',
            'estado_version'      => 'required',
        ]);

        if ($errors) {
            Session::flash('error', 'Descripción, elaborador y estado son obligatorios.');
            $this->redirect("/versionamiento/nueva/$id");
            return;
        }

        $estado       = $data['estado_version'];
        $siguienteVer = $this->model->maxVersion($id) + 1;
        $rutaArchivo  = null;

        // ── Subir archivo ────────────────────────────────────────────
        if (Request::hasFile('archivo') && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            try {
                $info = $this->docModel->infoCarpeta($id);
                if ($info) {
                    $carpeta = crearCarpetaVersion(
                        $info['macroproceso'],
                        $info['proceso'],
                        $info['nombre_subproceso'] ?? null,
                        $info['tipo_documento'],
                        $doc['nombre_documento'],
                        $siguienteVer
                    );
                    $destino = rutaArchivoEnVersion($carpeta, $_FILES['archivo']['name']);
                    if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $destino)) {
                        throw new \RuntimeException('No se pudo mover el archivo al servidor.');
                    }
                    $rutaArchivo = rutaRelativaAlmacenamiento($destino);
                }
            } catch (\RuntimeException $e) {
                Session::flash('error', 'Error al subir archivo: Error interno. Contacte al administrador.');
                $this->redirect("/versionamiento/nueva/$id");
                return;
            }
        }

        try {
            $idVersion = $this->model->crearVersionManual(
                $id,
                $siguienteVer,
                trim($data['descripcion_version']),
                $rutaArchivo,
                $estado,
                trim($data['usuario_creacion']),
                trim($data['usuario_revision']    ?? ''),
                trim($data['usuario_aprobacion']  ?? ''),
                !empty($data['fecha_aprobacion'])  ? $data['fecha_aprobacion'] : null
            );

            if ($estado === 'VIGENTE') {
                $this->model->obsoletizarAnteriores($id, $idVersion);
            }

            registrarAuditoria('versionamiento', 'CREAR_VERSION', 'versionamiento', $idVersion, null, [
                'id_documento' => $id, 'numero_version' => $siguienteVer, 'estado' => $estado,
            ]);

            $this->redirectSuccess(
                "/versionamiento/documento/$id",
                "Versión V$siguienteVer creada como <strong>$estado</strong>."
            );

        } catch (\Throwable $e) {
            Session::flash('error', 'Error al guardar: Error interno. Contacte al administrador.');
            $this->redirect("/versionamiento/nueva/$id");
        }
    }

    /** POST /versionamiento/cambiar-estado/{id} */
    public function cambiarEstado(int $idVersion): void
    {
        Csrf::verify();
        $nuevoEstado = Request::post('estado_version', '');
        $idDocumento = (int) Request::post('id_documento', 0);

        if (!in_array($nuevoEstado, ['VIGENTE', 'OBSOLETO', 'CREADO'], true)) {
            Session::flash('error', 'Estado no válido.');
            $this->redirect("/versionamiento/documento/$idDocumento");
            return;
        }

        $antes = $this->model->find($idVersion);

        if ($nuevoEstado === 'VIGENTE') {
            $this->model->obsoletizarAnteriores($idDocumento, $idVersion);
        }

        $this->model->actualizarEstado(
            $idVersion,
            $nuevoEstado,
            $nuevoEstado === 'OBSOLETO' ? date('Y-m-d H:i:s') : null
        );

        registrarAuditoria('versionamiento', 'CAMBIAR_ESTADO', 'versionamiento', $idVersion, $antes, [
            'estado_nuevo' => $nuevoEstado,
        ]);

        $this->redirectSuccess(
            "/versionamiento/documento/$idDocumento",
            "Estado actualizado a <strong>$nuevoEstado</strong>."
        );
    }

    /** GET /versionamiento/descargar/{id} */
    public function descargar(int $id): void
    {
        $doc  = $this->docModel->find($id);
        if (!$doc) $this->abort(404);
        $info = $this->docModel->infoCarpeta($id);
        if (!$info) $this->abort(404);

        try {
            $zipPath        = zipCarpetaDocumento(
                $info['macroproceso'], $info['proceso'],
                $info['nombre_subproceso'] ?? null,
                $info['tipo_documento'],    $doc['nombre_documento']
            );
            $nombreDescarga = sanitizarSegmentoCarpeta($doc['nombre_documento']) . '.zip';
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Pragma: no-cache');
            readfile($zipPath);
            unlink($zipPath);
            exit;
        } catch (\Throwable $e) {
            Session::flash('error', 'No se pudo generar la descarga: Error interno. Contacte al administrador.');
            $this->redirect('/versionamiento/documento/' . $id);
        }
    }
}
