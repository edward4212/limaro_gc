<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\AccionCorrectivaModel;

class AccionCorrectivaController extends Controller
{
    private AccionCorrectivaModel $model;

    public function __construct() { $this->model = new AccionCorrectivaModel(); }

    public function index(): void
    {
        $this->view('sgc/acciones/index', [
            'pageTitle' => 'Acciones Correctivas — §10.2',
            'acciones'  => $this->model->listar(),
            'resumen'   => $this->model->resumenEstados(),
        ]);
    }

    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('sgc/acciones/form', [
            'pageTitle'  => 'Nueva Acción Correctiva',
            'item'       => null,
            'codigo'     => $this->model->siguienteCodigo(),
            'empleados'  => (new \App\Models\EmpleadoModel())->activos(),
            'procesos'   => (new \App\Models\ProcesoModel())->activos(),
            'origenes'   => ['AUDITORIA','QUEJA','RECLAMO','INDICADOR','PROCESO','OTRO'],
            'estados_ac' => ['ABIERTA','EN_TRATAMIENTO','VERIFICACION','CERRADA','CANCELADA'],
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['codigo','origen','descripcion_nc','causa_raiz','accion_inmediata','accion_correctiva','responsable','id_responsable','clausula_iso','id_proceso','fecha_planificada']);
        if (empty($data['descripcion_nc'])) {
            Session::flash('error', 'La descripción de la no conformidad es obligatoria.');
            $this->redirect('/acciones-correctivas/crear');
            return;
        }
        $data['id_usuario']    = Auth::id();
        $data['id_responsable'] = !empty($data['id_responsable']) ? (int)$data['id_responsable'] : null;
        $data['id_proceso']     = !empty($data['id_proceso']) ? (int)$data['id_proceso'] : null;
        if (empty($data['fecha_planificada'])) $data['fecha_planificada'] = null;
        $id = $this->model->insert($data);
        registrarAuditoria('acciones_correctivas','CREAR','accion_correctiva',$id,null,$data);

        // CA-3+4: correo al responsable
        $this->notificarResponsable($data, $id, 'nueva');

        $this->redirectSuccess('/acciones-correctivas', 'Acción correctiva registrada. Se notificó al responsable.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        // CA-2: bloquear si CERRADA o CANCELADA
        if (in_array($item['estado'] ?? '', ['CERRADA','CANCELADA'])) {
            Session::flash('warning',
                'La AC <strong>' . htmlspecialchars($item['codigo']) . '</strong> está ' .
                '<strong>' . $item['estado'] . '</strong> y no puede modificarse.');
            $this->redirect('/acciones-correctivas');
            return;
        }

        $this->view('sgc/acciones/form', [
            'pageTitle'  => 'Editar AC — ' . $item['codigo'],
            'item'       => $item,
            'codigo'     => $item['codigo'],
            'empleados'  => (new \App\Models\EmpleadoModel())->activos(),
            'procesos'   => (new \App\Models\ProcesoModel())->activos(),
            'origenes'   => ['AUDITORIA','QUEJA','RECLAMO','INDICADOR','PROCESO','OTRO'],
            'estados_ac' => ['ABIERTA','EN_TRATAMIENTO','VERIFICACION','CERRADA','CANCELADA'],
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);

        // CA-2: doble protección por POST directo
        if (in_array($antes['estado'] ?? '', ['CERRADA','CANCELADA'])) {
            Session::flash('error', 'No se puede modificar una AC en estado ' . ($antes['estado'] ?? '') . '.');
            $this->redirect('/acciones-correctivas');
            return;
        }

        $data  = Request::only(['origen','descripcion_nc','causa_raiz','accion_inmediata',
            'accion_correctiva','responsable','id_responsable','clausula_iso','id_proceso',
            'fecha_planificada','fecha_cierre','eficacia','eficaz','estado']);
        $data['id_responsable'] = !empty($data['id_responsable']) ? (int)$data['id_responsable'] : null;
        $data['id_proceso']     = !empty($data['id_proceso']) ? (int)$data['id_proceso'] : null;
        $this->model->update($id, $data);
        registrarAuditoria('acciones_correctivas','EDITAR','accion_correctiva',$id,$antes,$data);
        $this->redirectSuccess('/acciones-correctivas', 'Acción correctiva actualizada.');
    }

    private function notificarResponsable(array $data, int $id, string $accion): void
    {
        $idResp = (int)($data['id_responsable'] ?? 0);
        if (!$idResp) return;

        try {
            $emp = (new \App\Models\EmpleadoModel())->find($idResp);

            if (!$emp || !filter_var($emp['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) return;

            $icon  = $accion === 'nueva' ? '🆕' : '✏️';
            $titulo = $accion === 'nueva' ? 'Nueva Acción Correctiva Asignada' : 'AC Actualizada';
            $html  = "<h2 style='color:#dc3545;margin-top:0;'>{$icon} {$titulo}</h2>
                      <p>Hola <strong>" . htmlspecialchars($emp['nombre_completo']) . "</strong>,</p>
                      <p>Tienes una Acción Correctiva asignada en el SGC Limaro:</p>
                      <table style='border-collapse:collapse;font-size:14px;
                                   border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                        <tr style='background:#f8fafc;'>
                          <td style='padding:8px 14px;font-weight:600;'>Código:</td>
                          <td style='padding:8px 14px;'><strong>#$id</strong></td>
                        </tr>
                        <tr>
                          <td style='padding:8px 14px;font-weight:600;'>No Conformidad:</td>
                          <td style='padding:8px 14px;'>" . htmlspecialchars(substr($data['descripcion_nc'] ?? '', 0, 200)) . "</td>
                        </tr>
                        <tr style='background:#f8fafc;'>
                          <td style='padding:8px 14px;font-weight:600;'>Responsable:</td>
                          <td style='padding:8px 14px;'>" . htmlspecialchars($emp['nombre_completo']) . "</td>
                        </tr>
                        <tr>
                          <td style='padding:8px 14px;font-weight:600;'>Fecha planificada:</td>
                          <td style='padding:8px 14px;'>" . (!empty($data['fecha_planificada']) ? date('d/m/Y', strtotime($data['fecha_planificada'])) : '—') . "</td>
                        </tr>
                      </table>
                      <br>
                      <a href='" . APP_URL . "/acciones-correctivas/editar/$id'
                         style='background:#dc3545;color:#fff;padding:10px 24px;border-radius:6px;
                                text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
                         Ver Acción Correctiva →
                      </a>
                      <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático.</p>";

            enviarCorreo(
                [$emp['correo_empleado'] => $emp['nombre_completo']],
                "[SGC Limaro] Acción Correctiva — {$titulo}",
                $html
            );
        } catch (\Throwable $e) {
            error_log('[AccionCorrectivaController] correo: ' . $e->getMessage());
        }
    }


    /** GET /acciones-correctivas/ver/{id} — detalle solo lectura */
    public function ver(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        $this->view('sgc/acciones/form', [
            'pageTitle'  => 'AC ' . $item['codigo'] . ' — Solo Lectura',
            'item'       => $item,
            'codigo'     => $item['codigo'],
            'empleados'  => (new \App\Models\EmpleadoModel())->activos(),
            'procesos'   => (new \App\Models\ProcesoModel())->activos(),
            'origenes'   => ['AUDITORIA','QUEJA','RECLAMO','INDICADOR','PROCESO','OTRO'],
            'estados_ac' => ['ABIERTA','EN_TRATAMIENTO','VERIFICACION','CERRADA','CANCELADA'],
            'soloLectura'=> true,
        ]);
    }

}