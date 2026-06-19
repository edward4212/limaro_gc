<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{HallazgoModel, AuditoriaPlanModel, AccionCorrectivaModel};

class AuditoriaHallazgosController extends Controller
{
    private HallazgoModel $model;
    public function __construct() { $this->model = new HallazgoModel(); }

    /** GET /auditoria/hallazgos */
    public function index(): void
    {
        $filtros = [
            'estado'  => Request::get('estado',''),
            'tipo'    => Request::get('tipo',''),
            'proceso' => Request::get('proceso',''),
        ];
        $this->view('sgc/auditoria_hallazgos/index', [
            'pageTitle'  => 'Panel de Hallazgos §9.2',
            'kpis'       => $this->model->kpis(),
            'hallazgos'  => $this->model->listarFiltrado($filtros),
            'porProceso' => $this->model->porProceso(),
            'filtros'    => $filtros,
        ]);
    }

    /** POST /auditoria/hallazgos/{id}/estado — cambio de estado inline */
    public function cambiarEstado(int $id): void
    {
        Csrf::verify();
        $estado = Request::post('estado','');
        $estados = ['ABIERTO','EN_TRATAMIENTO','CERRADO'];
        if (!in_array($estado, $estados)) {
            Session::flash('error', 'Estado inválido.');
            $this->redirect('/auditoria/hallazgos'); return;
        }

        $h = $this->model->find($id);
        if (!$h) { $this->abort(404); return; }

        $this->model->cambiarEstado($id, $estado);

        // Mismo comportamiento que HallazgoController::actualizar() (CA-4):
        // al cerrar un hallazgo con AC vinculada, la AC pasa a VERIFICACION.
        // Antes de este fix, cerrar desde este panel rápido NO sincronizaba
        // la AC, a diferencia de cerrar desde el formulario completo.
        $idAC = (int)($h['id_accion_correctiva'] ?? 0);
        if ($estado === 'CERRADO' && $idAC) {
            try {
                $acModel = new AccionCorrectivaModel();
                $acModel->update($idAC, [
                    'estado'       => 'VERIFICACION',
                    'fecha_cierre' => date('Y-m-d'),
                ]);
                Session::flash('info',
                    "Acción Correctiva #{$idAC} actualizada a estado <strong>VERIFICACION</strong>.");
            } catch (\Throwable $e) {
                error_log('[AuditoriaHallazgosController::cambiarEstado] update AC: ' . $e->getMessage());
            }
        }

        Session::flash('success', 'Estado actualizado correctamente.');
        $this->redirect('/auditoria/hallazgos');
    }

    /** POST /auditoria/hallazgos/{id}/generar-ac — HU-AI-18 */
    public function generarAC(int $id): void
    {
        Csrf::verify();
        $h = $this->model->find($id);
        if (!$h) $this->abort(404);
        if (!empty($h['id_accion_correctiva'])) {
            Session::flash('warning', 'Este hallazgo ya tiene una Acción Correctiva vinculada.');
            $this->redirect('/auditoria/hallazgos'); return;
        }

        // Crear la AC pre-poblada con datos del hallazgo
        $acModel = new AccionCorrectivaModel();
        try {
            $idAC = $acModel->insert([
                'codigo'           => $acModel->siguienteCodigo(),
                'origen'           => 'AUDITORIA',
                'id_hallazgo'      => $id,
                'descripcion_nc'   => 'Hallazgo: ' . ($h['descripcion'] ?? ''),
                'causa_raiz'       => $h['causa'] ?? '',
                'accion_inmediata' => $h['recomendacion'] ?? '',
                'responsable'      => $h['responsable'] ?? '',
                'id_responsable'   => $h['id_responsable'] ?? null,
                'clausula_iso'     => $h['clausula_iso'] ?? null,
                'id_proceso'       => $h['id_proceso'] ?? null,
                'estado'           => 'ABIERTA',
                'id_usuario'       => Auth::id(),
            ]);
        } catch (\Throwable $e) {
            error_log('[AuditoriaHallazgosController::generarAC] ' . $e->getMessage());
            Session::flash('error', 'No se pudo crear la Acción Correctiva. Verifique los datos del hallazgo.');
            $this->redirect('/auditoria/hallazgos');
            return;
        }

        if ($idAC) {
            $this->model->update($id, ['id_accion_correctiva' => $idAC]);
            registrarAuditoria('auditoria_hallazgo','GENERAR_AC','auditoria_hallazgo',
                $id, null, ['id_accion_correctiva' => $idAC]);
            $this->redirectSuccess('/auditoria/hallazgos',
                'Acción Correctiva creada y vinculada al hallazgo.');
        } else {
            Session::flash('error', 'Error al crear la Acción Correctiva.');
            $this->redirect('/auditoria/hallazgos');
        }
    }
}
