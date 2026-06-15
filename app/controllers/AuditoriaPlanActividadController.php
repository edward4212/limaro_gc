<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{AuditoriaPlanActividadModel, AuditoriaPlanModel, ProcesoModel, EmpleadoModel};

class AuditoriaPlanActividadController extends Controller
{
    private AuditoriaPlanActividadModel $model;
    private AuditoriaPlanModel          $planModel;

    public function __construct()
    {
        $this->model     = new AuditoriaPlanActividadModel();
        $this->planModel = new AuditoriaPlanModel();
    }

    /** GET /auditoria/plan/{id}/actividades */
    public function index(int $idPlan): void
    {
        $plan = $this->planModel->detalle($idPlan);
        if (!$plan) $this->abort(404);

        // Solo los procesos vinculados al plan (auditoria_plan_proceso)
        $procesos = !empty($plan['procesos'])
            ? $plan['procesos']
            : (new ProcesoModel())->activos();

        $this->view('sgc/auditoria_plan/actividades', [
            'pageTitle'   => 'Cronograma — ' . $plan['codigo'],
            'plan'        => $plan,
            'actividades' => $this->model->porPlan($idPlan),
            'procesos'    => $procesos,
            'empleados'   => (new EmpleadoModel())->activos(),
        ]);
    }

    /** POST /auditoria/plan/{id}/actividades */
    public function guardar(int $idPlan): void
    {
        Csrf::verify();
        $plan = $this->planModel->find($idPlan);
        if (!$plan) $this->abort(404);
        if ($plan['estado'] !== 'BORRADOR') {
            Session::flash('error', 'Solo se pueden modificar actividades cuando el plan está en BORRADOR. Estado actual: ' . $plan['estado'] . '.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
            return;
        }

        $data = Request::only(['fecha','hora_inicio','hora_fin','actividad',
                                'auditado','id_proceso_actividad','id_auditor','orden']);
        $data['id_plan'] = $idPlan;
        $data['estado']  = 'PENDIENTE';
        // orden por defecto: siguiente al último
        if (empty($data['orden'])) {
            $data['orden'] = count($this->model->porPlan($idPlan)) + 1;
        }

        if (!empty($data['hora_inicio']) && !empty($data['hora_fin'])) {
            $data['duracion_minutos'] = $this->model->calcularDuracion(
                $data['hora_inicio'], $data['hora_fin']
            );
        }

        if (empty($data['fecha']) || empty($data['actividad'])) {
            Session::flash('error', 'Fecha y descripción de la actividad son requeridos.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
            return;
        }

        try {
            $this->model->insert($data);
            $this->redirectSuccess("/auditoria/plan/{$idPlan}/actividades", 'Actividad agregada correctamente.');
        } catch (\Throwable $e) {
            error_log('[Actividad] insert: ' . $e->getMessage());
            Session::flash('error', 'Error al guardar la actividad. Verifique los datos ingresados.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
        }
    }

    /** POST /auditoria/plan/{id}/actividades/editar/{actId} */
    public function actualizar(int $idPlan, int $act_id): void
    {
        Csrf::verify();
        $plan = $this->planModel->find($idPlan);
        if (!$plan || in_array($plan['estado'], ['CANCELADO','FINALIZADO'])) {
            Session::flash('error', 'No se puede modificar actividades de un plan ' . ($plan['estado'] ?? '') . '.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
            return;
        }
        $data = Request::only(['fecha','hora_inicio','hora_fin','actividad',
                                'auditado','id_proceso_actividad','id_auditor','orden','estado']);
        if (!empty($data['hora_inicio']) && !empty($data['hora_fin'])) {
            $data['duracion_minutos'] = $this->model->calcularDuracion(
                $data['hora_inicio'], $data['hora_fin']
            );
        }
        try {
            $this->model->update($act_id, $data);
            $this->redirectSuccess("/auditoria/plan/{$idPlan}/actividades", 'Actividad actualizada.');
        } catch (\Throwable $e) {
            error_log('[Actividad] update: ' . $e->getMessage());
            Session::flash('error', 'Error al actualizar la actividad.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
        }
    }

    /** POST /auditoria/plan/{id}/actividades/eliminar/{act_id} */
    public function eliminar(int $idPlan, int $act_id): void
    {
        Csrf::verify();
        $plan = $this->planModel->find($idPlan);
        if (!$plan || in_array($plan['estado'], ['CANCELADO','FINALIZADO'])) {
            Session::flash('error', 'No se pueden eliminar actividades de un plan ' . ($plan['estado'] ?? '') . '.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
            return;
        }
        try {
            $this->model->delete($act_id);
            $this->redirectSuccess("/auditoria/plan/{$idPlan}/actividades", 'Actividad eliminada.');
        } catch (\Throwable $e) {
            error_log('[Actividad] delete: ' . $e->getMessage());
            Session::flash('error', 'Error al eliminar la actividad.');
            $this->redirect("/auditoria/plan/{$idPlan}/actividades");
        }
    }

    /** GET fallback: redirigir al cronograma */
    public function redirigir(int $idPlan, int $act_id = 0): void
    {
        $this->redirect("/auditoria/plan/{$idPlan}/actividades");
    }

}