<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\DocumentoModel;
use App\Models\TipoDocumentoModel;
use App\Models\ProcesoModel;
use App\Models\MacroprocesoModel;
use App\Models\SubprocesoModel;

/**
 * Controlador de Documentos Registrados.
 * Gestiona CRUD, listado maestro vigentes y obsoletos.
 */
class DocumentoController extends Controller
{
    private DocumentoModel     $model;
    private TipoDocumentoModel $tipoModel;
    private ProcesoModel       $procesoModel;
    private MacroprocesoModel  $macroModel;
    private SubprocesoModel    $subprocesoModel;

    public function __construct()
    {
        $this->model            = new DocumentoModel();
        $this->tipoModel        = new TipoDocumentoModel();
        $this->procesoModel     = new ProcesoModel();
        $this->macroModel       = new MacroprocesoModel();
        $this->subprocesoModel  = new SubprocesoModel();
    }

    /** GET /documentos */
    public function index(): void
    {
        // FIX 3: return después del JSON para no intentar renderizar la vista también
        if (Request::get('ajax') === 'buscar') {
            $q = trim((string) Request::get('q', ''));
            $this->json($this->model->buscar($q));
            return;
        }

        $this->view('empresa/documentos/index', [
            'pageTitle'  => 'Documentos Registrados',
            'documentos' => $this->model->listar(),
        ]);
    }

    /** GET /documentos/vigentes */
    public function vigentes(): void
    {
        $this->view('documentos/vigentes', [
            'pageTitle'  => 'Listado Maestro Vigentes',
            'documentos' => $this->model->vigentes(),
        ]);
    }

    /** GET /documentos/obsoletos */
    public function obsoletos(): void
    {
        $this->view('documentos/obsoletos', [
            'pageTitle'  => 'Listado Maestro Obsoletos',
            'documentos' => $this->model->obsoletos(),
        ]);
    }

    /** GET /documentos/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('empresa/documentos/form', [
            'pageTitle'     => 'Crear Documento',
            'item'          => null,
            'tipos'         => $this->tipoModel->activos(),
            'macroprocesos' => $this->macroModel->activos(),
            'procesos'      => $this->procesoModel->activos(),
            'subprocesos'   => $this->subprocesoModel->activos(),
        ]);
    }

    /** POST /documentos/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data   = Request::only(['id_tipo_documento', 'id_proceso', 'id_subproceso', 'nombre_documento', 'descripcion']);
        $errors = $this->validate($data, [
            'id_tipo_documento' => 'required|integer',
            'id_proceso'        => 'required|integer',
            'nombre_documento'  => 'required|max:300',
        ]);

        // FIX 1: return después del redirect para detener la ejecución
        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/documentos/crear');
            return;
        }

        try {
            $id = $this->model->crear(
                (int) $data['id_tipo_documento'],
                (int) $data['id_proceso'],
                trim($data['nombre_documento']),
                trim($data['descripcion'] ?? ''),
                Auth::id(),
                !empty($data['id_subproceso']) ? (int) $data['id_subproceso'] : null
            );

            registrarAuditoria('documentos', 'CREAR', 'documento', $id, null, $data);
            $this->redirectSuccess('/documentos', 'Documento registrado exitosamente.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al crear el documento: Error interno. Contacte al administrador.');
            Session::setOldInput($data);
            $this->redirect('/documentos/crear');
            return;
        }
    }

    /** GET /documentos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        // CA-1 HU-005: subprocesos filtrados al proceso actual del documento
        // CA-3 HU-008: si falla, retornar array vacío y logear
        try {
            $subprocesosFiltrados = $this->subprocesoModel->porProceso((int)$item['id_proceso']);
        } catch (\Throwable $eS) {
            error_log('[DocumentoController::editar] subprocesos id_proceso=' . $item['id_proceso'] . ' | ' . $eS->getMessage());
            $subprocesosFiltrados = [];
        }

        $this->view('empresa/documentos/form', [
            'pageTitle'     => 'Editar Documento',
            'item'          => $item,
            'tipos'         => $this->tipoModel->activos(),
            'macroprocesos' => $this->macroModel->activos(),
            'procesos'      => $this->procesoModel->activos(),
            'subprocesos'   => $subprocesosFiltrados,
        ]);
    }

    /** GET /documentos/reasignar/{id} — CA-2 HU-005 */
    public function reasignarForm(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        // Verificar que no hay solicitudes activas antes de mostrar el form
        $solicitudesActivas = $this->model->solicitudesActivasDocumento($id);

        // CA-3 HU-009: enriquecer $item con nombre real del proceso
        $itemConProceso = $this->model->conDetalle($item['id_documento']) ?? $item;

        $this->view('empresa/documentos/form_reasignar', [
            'pageTitle'       => 'Reasignar Documento — ' . $item['nombre_documento'],
            'item'            => $itemConProceso,
            'macroprocesos'   => $this->macroModel->activos(),
            'procesos'        => $this->procesoModel->activos(),
            'subprocesos'     => $this->subprocesoModel->activos(),
            'tieneActivas'    => $solicitudesActivas > 0,
        ]);
    }

    /** POST /documentos/reasignar/{id} — CA-2 HU-005 */
    public function reasignar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        if (!$antes) $this->abort(404);

        $data = Request::only(['id_proceso_nuevo', 'id_subproceso_nuevo', 'observacion_reasignacion']);
        $idProcesoNuevo = (int)($data['id_proceso_nuevo'] ?? 0);

        if (!$idProcesoNuevo) {
            Session::flash('error', 'Debe seleccionar el proceso destino.');
            $this->redirect("/documentos/reasignar/$id");
            return;
        }

        $idSubprocesoNuevo = !empty($data['id_subproceso_nuevo']) ? (int)$data['id_subproceso_nuevo'] : null;

        // Bloquear solo si el proceso Y el subproceso son exactamente los mismos
        $mismosProceso    = $idProcesoNuevo === (int)$antes['id_proceso'];
        $mismoSubproceso  = $idSubprocesoNuevo === ((!empty($antes['id_subproceso'])) ? (int)$antes['id_subproceso'] : null);

        if ($mismosProceso && $mismoSubproceso) {
            Session::flash('error', 'El proceso y subproceso destino son los mismos que el actual. Seleccione una ubicación diferente.');
            $this->redirect("/documentos/reasignar/$id");
            return;
        }

        try {
            $this->model->reubicar($id, $idProcesoNuevo, $idSubprocesoNuevo, Auth::get('usuario', 'admin'), $data['observacion_reasignacion'] ?? '');
            registrarAuditoria('documentos', 'REASIGNAR', 'documento', $id, $antes, $data);
            $this->redirectSuccess('/documentos', 'Documento reasignado correctamente. El código fue actualizado.');
        } catch (\Throwable $e) {
            error_log('[Limaro] reasignar documento: ' . $e->getMessage());
            Session::flash('error', 'Error al reasignar. ' . $e->getMessage());
            $this->redirect("/documentos/reasignar/$id");
        }
    }

    /** POST /documentos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        if (!$antes) $this->abort(404);

        $data = Request::only([
            'nombre_documento', 'descripcion', 'estado',
            'id_subproceso',
            'id_proceso_nuevo', 'id_subproceso_nuevo',
            'observacion_reasignacion',
        ]);

        $errors = $this->validate($data, ['nombre_documento' => 'required|max:300']);
        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect("/documentos/editar/$id");
            return;
        }

        // ── ¿Cambia el proceso? ──────────────────────────────────────
        $idProcesoNuevo    = !empty($data['id_proceso_nuevo'])    ? (int)$data['id_proceso_nuevo']    : null;
        $idSubprocesoNuevo = !empty($data['id_subproceso_nuevo']) ? (int)$data['id_subproceso_nuevo'] : null;
        $cambiaProces      = $idProcesoNuevo && $idProcesoNuevo !== (int)$antes['id_proceso'];

        if ($cambiaProces) {
            try {
                $resultado = $this->model->reubicar(
                    $id,
                    $idProcesoNuevo,
                    $idSubprocesoNuevo,
                    Auth::user()['usuario'] ?? 'sistema',
                    trim($data['observacion_reasignacion'] ?? '')
                );
                // Actualizar nombre/descripción/estado en la misma operación
                $this->model->actualizar($id, [
                    'nombre_documento'   => trim($data['nombre_documento']),
                    'objetivo_documento' => trim($data['descripcion'] ?? ''),
                    'estado'             => $data['estado'] ?? 'ACTIVO',
                ]);
                registrarAuditoria('documentos', 'REASIGNAR', 'documento', $id, $antes, $resultado);
                $this->redirectSuccess(
                    '/documentos',
                    "Reasignado correctamente — Código anterior: <strong>{$resultado['codigo_anterior']}</strong> → Nuevo: <strong>{$resultado['codigo_nuevo']}</strong> en proceso {$resultado['proceso_nuevo']}."
                );
            } catch (\RuntimeException $e) {
                Session::flash('error', $e->getMessage());
                $this->redirect("/documentos/editar/$id");
            }
            return;
        }

        // ── Edición simple (sin cambio de proceso) ──────────────────
        $this->model->actualizar($id, [
            'nombre_documento'   => trim($data['nombre_documento']),
            'objetivo_documento' => trim($data['descripcion'] ?? ''),
            'estado'             => $data['estado'] ?? 'ACTIVO',
            'id_subproceso'      => !empty($data['id_subproceso']) ? (int)$data['id_subproceso'] : null,
        ]);
        registrarAuditoria('documentos', 'EDITAR', 'documento', $id, $antes, $data);
        $this->redirectSuccess('/documentos', 'Documento actualizado.');
    }

    /** POST /documentos/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('documentos', 'ELIMINAR', 'documento', $id, null, null);
        $this->redirectSuccess('/documentos', 'Documento inactivado.');
    }

   // ═══════════════════════════════════════════════════════════════
// EXPLORADOR DE DOCUMENTOS
// ═══════════════════════════════════════════════════════════════

/** GET /documentos/explorador */
public function explorador(): void
{
    $this->view('documentos/explorador', [
        'pageTitle' => 'Documentos por Proceso',
        'procesos'  => $this->model->procesoConConteo(),
    ]);
}

/**
 * GET /documentos/explorador/proceso/{id}
 * Retorna JSON con subprocesos (si tiene) O tipos de documento.
 * El cliente decide qué modal mostrar según la respuesta.
 */
public function ajaxProceso(int $id): void
{
    $subprocesos = $this->subprocesoModel->porProceso($id);
    $tipos       = $this->model->tipoConConteo($id, null);

    $this->json([
        'tiene_subprocesos' => count($subprocesos) > 0,
        'subprocesos'       => $subprocesos,
        'tipos'             => $tipos,
        'id_proceso'        => $id,
    ]);
}

/**
 * GET /documentos/explorador/tipo?id_proceso=X&id_tipo=Y[&id_subproceso=Z]
 * Retorna JSON con documentos vigentes para ese proceso/tipo/subproceso.
 */
public function ajaxTipo(): void
{
    $idProceso    = (int) Request::get('id_proceso', 0);
    $idTipo       = (int) Request::get('id_tipo', 0);
    $idSubproceso = (int) Request::get('id_subproceso', 0) ?: null;
    $soloTipos    = Request::get('solo_tipos', 0);

    if (!$idProceso) {
        $this->json(['error' => 'Parámetros incompletos.'], 400);
        return;
    }

    // Modo solo_tipos=1: devuelve tarjetas de tipos filtradas por subproceso
    if ($soloTipos && $idSubproceso) {
        $tipos = $this->model->tipoConConteo($idProceso, $idSubproceso);
        $this->json([
            'tipos'   => $tipos,
            'total'   => count($tipos),
        ]);
        return;
    }

    if (!$idTipo) {
        $this->json(['error' => 'Parámetros incompletos.'], 400);
        return;
    }

    $documentos = $this->model->vigentesParaExplorador($idProceso, $idTipo, $idSubproceso);

    $this->json([
        'documentos' => $documentos,
        'total'      => count($documentos),
    ]);
}

    /**
     * GET /documentos/vigentes/descargar-zip
     * HU-016: genera ZIP con todos los documentos vigentes organizados por proceso/tipo
     */
    public function descargarZipVigentes(): void
    {
        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'La extensión ZIP no está disponible en el servidor.');
            $this->redirect('/documentos/vigentes');
            return;
        }

        $docs = $this->model->vigentes();

        if (empty($docs)) {
            Session::flash('error', 'No hay documentos vigentes para descargar.');
            $this->redirect('/documentos/vigentes');
            return;
        }

        $tmpZip  = tempnam(sys_get_temp_dir(), 'docs_vigentes_') . '.zip';
        $zip     = new \ZipArchive();
        $baseDir = APP_ROOT . '/public/storage/documentos/';

        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Session::flash('error', 'No se pudo crear el archivo ZIP temporal.');
            $this->redirect('/documentos/vigentes');
            return;
        }

        $archivados = 0;
        $sinArchivo = 0;

        foreach ($docs as $d) {
            // Ruta del archivo físico
            $rutaRel = $d['archivo_ruta'] ?? $d['archivo_ruta_legacy'] ?? null;
            if (empty($rutaRel)) { $sinArchivo++; continue; }

            // Ruta absoluta — soportar rutas relativas del storage
            $rutaAbs = str_starts_with($rutaRel, '/')
                ? APP_ROOT . '/public' . $rutaRel
                : $baseDir . $rutaRel;

            if (!file_exists($rutaAbs)) { $sinArchivo++; continue; }

            // CA-2: estructura carpetas: proceso / tipo_documento / codigo-nombre.ext
            $proc    = sanitizarSegmentoCarpeta($d['proceso'] ?? 'SIN_PROCESO');
            $tipo    = sanitizarSegmentoCarpeta($d['tipo_documento'] ?? 'SIN_TIPO');
            $ext     = pathinfo($rutaAbs, PATHINFO_EXTENSION);
            $nombre  = sanitizarSegmentoCarpeta($d['codigo'] . ' ' . $d['nombre_documento']);
            $enZip   = "$proc/$tipo/$nombre.$ext";

            $zip->addFile($rutaAbs, $enZip);
            $archivados++;
        }

        $zip->close();

        if ($archivados === 0) {
            @unlink($tmpZip);
            Session::flash('error', "No se encontraron archivos físicos para descargar ($sinArchivo documentos sin archivo).");
            $this->redirect('/documentos/vigentes');
            return;
        }

        // CA-1 y CA-3: enviar ZIP al cliente
        $nombreDescarga = 'documentos_vigentes_' . date('Ymd') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($tmpZip));
        header('Pragma: no-cache');
        header('Cache-Control: no-store, no-cache');
        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }


    /** GET /documentos/buscar?q=X  — Ajax para autocompletar en solicitudes */
    public function buscar(): void
    {
        $q = trim(Request::get('q', ''));
        if (mb_strlen($q) < 2) {
            $this->json([]);
            return;
        }
        $this->json($this->model->buscar($q));
    }

}