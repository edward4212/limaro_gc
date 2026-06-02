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

        $this->view('empresa/documentos/form', [
            'pageTitle'     => 'Editar Documento',
            'item'          => $item,
            'tipos'         => $this->tipoModel->activos(),
            'macroprocesos' => $this->macroModel->activos(),
            'procesos'      => $this->procesoModel->activos(),
            'subprocesos'   => $this->subprocesoModel->activos(),
        ]);
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
    $idProceso   = (int) Request::get('id_proceso', 0);
    $idTipo      = (int) Request::get('id_tipo', 0);
    $idSubproceso = (int) Request::get('id_subproceso', 0) ?: null;

    if (!$idProceso || !$idTipo) {
        $this->json(['error' => 'Parámetros incompletos.'], 400);
        return;
    }

    $documentos = $this->model->vigentesParaExplorador($idProceso, $idTipo, $idSubproceso);

    $this->json([
        'documentos' => $documentos,
        'total'      => count($documentos),
    ]);
}
}