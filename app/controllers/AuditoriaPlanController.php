<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{AuditoriaPlanModel, ProcesoModel, EmpleadoModel, UsuarioModel};

class AuditoriaPlanController extends Controller
{
    private AuditoriaPlanModel $model;
    private ProcesoModel       $procModel;
    private EmpleadoModel      $empModel;

    public function __construct()
    {
        $this->model     = new AuditoriaPlanModel();
        $this->procModel = new ProcesoModel();
        $this->empModel  = new EmpleadoModel();
    }

    /** GET /auditoria/plan */
    public function index(): void
    {
        $filtros = [
            'anio'   => Request::get('anio', ''),
            'estado' => Request::get('estado', ''),
        ];
        $this->view('sgc/auditoria_plan/index', [
            'pageTitle' => 'Planes de Auditoría',
            'planes'    => $this->model->listar($filtros),
            'filtros'   => $filtros,
        ]);
    }

    /** GET /auditoria/plan/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('sgc/auditoria_plan/form', [
            'pageTitle' => 'Nuevo Plan de Auditoría',
            'item'      => null,
            'codigo'    => $this->model->siguienteCodigo(),
            'procesos'  => $this->procModel->activos(),
            'auditores' => $this->empModel->activos(),
        ]);
    }

    /** POST /auditoria/plan/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only([
            'codigo','titulo','anio','tipo_auditoria','objetivo_general','objetivos_especificos',
            'alcance','criterios','id_auditor_lider','equipo_auditor',
            'fecha_inicio','fecha_fin',
        ]);
        $data['id_auditor_lider'] = ((int)($data['id_auditor_lider'] ?? 0)) ?: null;
        $data['estado']     = 'BORRADOR';
        $data['id_usuario'] = Auth::id();

        $errors = $this->validate($data, [
            'titulo'           => 'required',
            'objetivo_general' => 'required',
            'alcance'          => 'required',
            'anio'             => 'required|integer',
        ]);
        if (!$errors && $data['id_auditor_lider'] !== null && !$this->empModel->esActivo($data['id_auditor_lider'])) {
            $errors = ['id_auditor_lider' => 'El Auditor Líder seleccionado no es válido. Seleccione un empleado activo.'];
        }
        if ($errors) {
            $msg = isset($errors['id_auditor_lider'])
                ? $errors['id_auditor_lider']
                : 'Corrija los campos requeridos.';
            Session::flash('error', $msg);
            Session::setOldInput($data);
            $this->redirect('/auditoria/plan/crear');
            return;
        }

        $idPlan = $this->model->insert($data);
        if (!$idPlan) {
            Session::flash('error', 'Error al guardar el plan.');
            $this->redirect('/auditoria/plan/crear');
            return;
        }

        // Guardar procesos seleccionados
        $procesos = Request::post('id_procesos', []);
        if ($procesos) $this->model->guardarProcesos($idPlan, (array)$procesos);

        registrarAuditoria('auditoria_plan','CREAR','auditoria_plan',$idPlan,null,$data);

        // Correo a Coordinadores de Calidad: plan listo para revisión
        try {
            $um = new UsuarioModel();
            $coordinadores = $um->usuariosPorRol('COORDINADOR CALIDAD');
            foreach ($coordinadores as $coord) {
                if (!filter_var($coord['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) continue;
                $html = "<h2 style='color:#1B3A6B;margin-top:0;'>📋 Nuevo Plan de Auditoría</h2>
                    <p>Se ha creado el plan <strong>" . htmlspecialchars($data['codigo']) . "</strong> y está disponible para revisión.</p>
                    <table style='font-size:13px;border-collapse:collapse;width:100%;'>
                        <tr><td style='padding:4px 8px;color:#6b7280;'>Título:</td><td><strong>" . htmlspecialchars($data['titulo']) . "</strong></td></tr>
                        <tr><td style='padding:4px 8px;color:#6b7280;'>Año:</td><td>" . htmlspecialchars($data['anio']) . "</td></tr>
                        <tr><td style='padding:4px 8px;color:#6b7280;'>Tipo:</td><td>" . htmlspecialchars($data['tipo_auditoria'] ?? '—') . "</td></tr>
                    </table>
                    <br><a href='" . APP_URL . "/auditoria/plan/{$idPlan}' style='background:#1B3A6B;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;display:inline-block;'>
                        Ver Plan →
                    </a>
                    <br><br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC — Mensaje automático</p>";
                enviarCorreo(
                    [$coord['correo_empleado'] => $coord['nombre_completo']],
                    "[SGC] Plan de Auditoría {$data['codigo']} — Pendiente de revisión",
                    $html
                );
            }
        } catch (\Throwable $e) {
            error_log('[AuditoriaPlan] correo crear: ' . $e->getMessage());
        }

        $this->redirectSuccess('/auditoria/plan', 'Plan de Auditoría creado. Se notificó a los Coordinadores de Calidad.');
    }

    /** GET /auditoria/plan/{id} */
    public function ver(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/auditoria_plan/ver', [
            'pageTitle' => 'Plan de Auditoría — ' . $item['codigo'],
            'item'      => $item,
            'programa'  => $this->model->programaDelPlan($id),
        ]);
    }

    /** GET /auditoria/plan/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'BORRADOR') {
            Session::flash('warning', 'El plan solo puede editarse en estado BORRADOR. Estado actual: ' . $item['estado'] . '.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }
        Session::clearOldInput();
        $this->view('sgc/auditoria_plan/form', [
            'pageTitle' => 'Editar Plan — ' . $item['codigo'],
            'item'      => $item,
            'codigo'    => $item['codigo'],
            'procesos'  => $this->procModel->activos(),
            'auditores' => $this->empModel->activos(),
        ]);
    }

    /** POST /auditoria/plan/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'No se puede editar un plan en estado ' . $item['estado'] . '.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }
        $data = Request::only([
            'titulo','anio','tipo_auditoria','objetivo_general','objetivos_especificos',
            'alcance','criterios','id_auditor_lider','equipo_auditor',
            'fecha_inicio','fecha_fin',
        ]);
        $data['id_auditor_lider'] = ((int)($data['id_auditor_lider'] ?? 0)) ?: null;
        if ($data['id_auditor_lider'] !== null && !$this->empModel->esActivo($data['id_auditor_lider'])) {
            Session::flash('error', 'El Auditor Líder seleccionado no es válido. Seleccione un empleado activo.');
            Session::setOldInput($data);
            $this->redirect("/auditoria/plan/editar/{$id}");
            return;
        }
        $this->model->update($id, $data);
        $procesos = Request::post('id_procesos', []);
        $this->model->guardarProcesos($id, (array)$procesos);
        registrarAuditoria('auditoria_plan','EDITAR','auditoria_plan',$id,$item,$data);
        $this->redirectSuccess('/auditoria/plan', 'Plan actualizado correctamente.');
    }

    /** POST /auditoria/plan/aprobar/{id} — CA: solo COORD/ADMIN */
    public function aprobar(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'EN_REVISION') {
            Session::flash('error', 'Solo se pueden aprobar planes en estado EN REVISIÓN.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }
        // Validar actividades en el cronograma
        $totalAct = $this->model->totalActividades($id);
        if ($totalAct === 0) {
            Session::flash('error', 'No se puede aprobar el plan sin actividades en el cronograma.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        // Verificar si tiene programa asociado — obligatorio para aprobar
        $programa = $this->model->programaDelPlan($id);
        if (!$programa) {
            Session::flash('error',
                'No se puede aprobar el plan sin un Programa de Auditoría creado. ' .
                '<a href="' . APP_URL . '/auditoria/programa/crear" class="btn btn-sm btn-warning ms-2">Crear Programa →</a>'
            );
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        $this->model->cambiarEstado($id, 'APROBADO');
        $this->model->sincronizarEstadoPrograma($id, 'APROBADO');
        registrarAuditoria('auditoria_plan','APROBAR','auditoria_plan',$id,
            ['estado'=>'EN_REVISION'],['estado'=>'APROBADO']);

        // Correo al creador
        try {
            $aprobador = Auth::empleadoId() ?? 0;
            $correoAprobador = (new EmpleadoModel())->correoYNombre($aprobador);
            $nombreAprobador = $correoAprobador['nombre_completo'] ?? 'Coordinador';

            // Correo al usuario que creó el plan
            $creador = $this->model->find($id);
            $correoCreador = (new EmpleadoModel())->porIdUsuario((int)($item['id_usuario'] ?? 0));

            $htmlAprobado = "<h2 style='color:#16a34a;margin-top:0;'>✅ Plan de Auditoría Aprobado</h2>
                <p>El plan <strong>" . htmlspecialchars($item['codigo']) . " — " . htmlspecialchars($item['titulo']) . "</strong>
                ha sido <strong>APROBADO</strong> por <em>" . htmlspecialchars($nombreAprobador) . "</em>.</p>
                <a href='" . APP_URL . "/auditoria/plan/{$id}' style='background:#16a34a;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;display:inline-block;'>
                    Ver Plan Aprobado →
                </a>
                <br><br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC — Mensaje automático</p>";

            if ($correoCreador && filter_var($correoCreador['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                enviarCorreo(
                    [$correoCreador['correo_empleado'] => $correoCreador['nombre_completo']],
                    "[SGC] Plan {$item['codigo']} APROBADO ✅",
                    $htmlAprobado
                );
            }
            // Correo al auditor líder si tiene correo
            if (!empty($item['id_auditor_lider'])) {
                $correoAuditor = (new EmpleadoModel())->correoYNombre((int)$item['id_auditor_lider']);
                if ($correoAuditor && filter_var($correoAuditor['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    enviarCorreo(
                        [$correoAuditor['correo_empleado'] => $correoAuditor['nombre_completo']],
                        "[SGC] Plan {$item['codigo']} APROBADO — Puede iniciar ejecución",
                        $htmlAprobado
                    );
                }
            }
        } catch (\Throwable $eC) {
            error_log('[AuditoriaPlan] correo aprobar: ' . $eC->getMessage());
        }

        $this->redirectSuccess('/auditoria/plan', 'Plan aprobado. Programa asociado: ' . $programa['codigo'] . '.');
    }

    /** POST /auditoria/plan/iniciar/{id} — APROBADO → EN_CURSO: marca el inicio real de la ejecución */
    public function iniciarEjecucion(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'APROBADO') {
            Session::flash('error', 'Solo se puede iniciar ejecución en planes con estado APROBADO.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        $this->model->cambiarEstado($id, 'EN_CURSO');
        $this->model->sincronizarEstadoPrograma($id, 'EN_CURSO');
        registrarAuditoria('auditoria_plan','INICIAR_EJECUCION','auditoria_plan',$id,
            ['estado'=>'APROBADO'],['estado'=>'EN_CURSO']);

        // Correo al auditor líder
        try {
            if (!empty($item['id_auditor_lider'])) {
                $correoAuditor = (new EmpleadoModel())->correoYNombre((int)$item['id_auditor_lider']);
                if ($correoAuditor && filter_var($correoAuditor['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $htmlInicio = "<h2 style='color:#1B3A6B;margin-top:0;'>▶️ Ejecución de Auditoría Iniciada</h2>
                        <p>El plan <strong>" . htmlspecialchars($item['codigo']) . " — " . htmlspecialchars($item['titulo']) . "</strong>
                        ha pasado a estado <strong>EN CURSO</strong>. Ya puede registrar el avance de las actividades en el cronograma.</p>
                        <a href='" . APP_URL . "/auditoria/plan/{$id}/actividades' style='background:#1B3A6B;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;display:inline-block;'>
                            Ver Cronograma →
                        </a>
                        <br><br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC — Mensaje automático</p>";
                    enviarCorreo(
                        [$correoAuditor['correo_empleado'] => $correoAuditor['nombre_completo']],
                        "[SGC] Plan {$item['codigo']} — Ejecución iniciada",
                        $htmlInicio
                    );
                }
            }
        } catch (\Throwable $eC) {
            error_log('[AuditoriaPlan] correo iniciar ejecución: ' . $eC->getMessage());
        }

        $this->redirectSuccess("/auditoria/plan/{$id}", 'Ejecución del plan iniciada. Ya puede registrar avance en el cronograma.');
    }

    /** POST /auditoria/plan/retornar/{id} — devolver a BORRADOR */
    public function retornar(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);
        $justificacion = trim(Request::post('justificacion',''));
        if (mb_strlen($justificacion) < 10) {
            Session::flash('error', 'Debe ingresar una justificación (mínimo 10 caracteres).');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }
        $this->model->cambiarEstado($id, 'BORRADOR');
        $this->model->sincronizarEstadoPrograma($id, 'BORRADOR');
        registrarAuditoria('auditoria_plan','RETORNAR','auditoria_plan',$id,
            ['estado'=>'APROBADO'],['estado'=>'BORRADOR','justificacion'=>$justificacion]);
        $this->redirectSuccess('/auditoria/plan', 'Plan retornado a BORRADOR.');
    }

    /** POST /auditoria/plan/revisar/{id} — enviar a EN_REVISION */
    public function enviarARevision(int $id): void
    {
        Csrf::verify();
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'Solo planes en BORRADOR pueden enviarse a revisión.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        // Validar programa creado
        $programa = $this->model->programaDelPlan($id);
        if (!$programa) {
            Session::flash('error', 'Para enviar a revisión debe crear primero el Programa de Auditoría.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        // Validar actividades en el cronograma
        $totalAct = $this->model->totalActividades($id);
        if ($totalAct === 0) {
            Session::flash('error', 'Para enviar a revisión debe agregar al menos una actividad al cronograma.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        $this->model->cambiarEstado($id, 'EN_REVISION');
        $this->model->sincronizarEstadoPrograma($id, 'EN_REVISION');
        registrarAuditoria('auditoria_plan','EN_REVISION','auditoria_plan',$id,
            ['estado'=>'BORRADOR'],['estado'=>'EN_REVISION']);

        // Correo a Coordinadores
        try {
            $um = new UsuarioModel();
            $coordinadores = $um->usuariosPorRol('COORDINADOR CALIDAD');
            foreach ($coordinadores as $coord) {
                if (!filter_var($coord['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) continue;
                $html = "<h2 style='color:#f59e0b;margin-top:0;'>🔍 Plan de Auditoría en Revisión</h2>
                    <p>El plan <strong>" . htmlspecialchars($item['codigo']) . " — " . htmlspecialchars($item['titulo']) . "</strong>
                    ha sido enviado para su aprobación.</p>
                    <a href='" . APP_URL . "/auditoria/plan/{$id}' style='background:#1B3A6B;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;display:inline-block;'>
                        Revisar y Aprobar →
                    </a>
                    <br><br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC — Mensaje automático</p>";
                enviarCorreo(
                    [$coord['correo_empleado'] => $coord['nombre_completo']],
                    "[SGC] Plan {$item['codigo']} — Requiere aprobación",
                    $html
                );
            }
        } catch (\Throwable $e) {
            error_log('[AuditoriaPlan] correo revision: ' . $e->getMessage());
        }

        $this->redirectSuccess('/auditoria/plan', 'Plan enviado a revisión. Se notificó a los Coordinadores de Calidad.');
    }


    /** POST /auditoria/plan/devolver/{id} — devolver a BORRADOR desde EN_REVISION */
    public function devolver(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);

        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'EN_REVISION') {
            Session::flash('error', 'Solo se pueden devolver planes en estado EN REVISIÓN.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        $comentario = trim(Request::post('comentario_devolucion', ''));
        if (mb_strlen($comentario) < 15) {
            Session::flash('error', 'El motivo de devolución debe tener al menos 15 caracteres.');
            $this->redirect("/auditoria/plan/{$id}");
            return;
        }

        // Actualizar estado + guardar comentario
        $this->model->update($id, [
            'estado'                => 'BORRADOR',
            'comentario_devolucion' => $comentario,
        ]);

        // Si tiene programa, también vuelve a BORRADOR
        $programa = $this->model->programaDelPlan($id);
        if ($programa && $programa['estado'] !== 'FINALIZADO') {
            $this->model->sincronizarEstadoPrograma($id, 'BORRADOR');
        }

        registrarAuditoria('auditoria_plan','DEVOLVER','auditoria_plan',$id,
            ['estado'=>'EN_REVISION'],
            ['estado'=>'BORRADOR','comentario_devolucion'=>$comentario]
        );

        // Correo al creador del plan
        try {
            $revisorNombre = 'Coordinador de Calidad';
            $correoRevisor = (new EmpleadoModel())->correoYNombre(Auth::empleadoId() ?? 0);
            if ($correoRevisor) $revisorNombre = $correoRevisor['nombre_completo'];

            $creador = (new EmpleadoModel())->porIdUsuario((int)($item['id_usuario'] ?? 0));

            if ($creador && filter_var($creador['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                $html = "<h2 style='color:#dc2626;margin-top:0;'>↩️ Plan de Auditoría Devuelto</h2>
                    <p>Hola <strong>" . htmlspecialchars($creador['nombre_completo']) . "</strong>,</p>
                    <p>El plan <strong>" . htmlspecialchars($item['codigo']) . " — " . htmlspecialchars($item['titulo']) . "</strong>
                    ha sido devuelto a <strong>BORRADOR</strong> por <em>" . htmlspecialchars($revisorNombre) . "</em>.</p>
                    <div style='background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:12px 16px;margin:12px 0;'>
                        <strong>Motivo:</strong><br>" . nl2br(htmlspecialchars($comentario)) . "
                    </div>
                    <p>Por favor corrija el plan y vuelva a enviarlo para revisión.</p>
                    <a href='" . APP_URL . "/auditoria/plan/editar/{$id}' style='background:#1B3A6B;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;display:inline-block;'>
                        Editar Plan →
                    </a>
                    <br><br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC — Mensaje automático</p>";

                enviarCorreo(
                    [$creador['correo_empleado'] => $creador['nombre_completo']],
                    "[SGC] Plan {$item['codigo']} devuelto — Requiere correcciones",
                    $html
                );
            }
        } catch (\Throwable $eD) {
            error_log('[AuditoriaPlan] correo devolver: ' . $eD->getMessage());
        }

        $this->redirectSuccess('/auditoria/plan',
            'Plan devuelto a BORRADOR. Se notificó al creador con el motivo.');
    }


    /** GET /auditoria/plan/{id}/datos — AJAX: datos del plan para autocompletar programa */
    public function datosPlan(int $id): void
    {
        $plan = $this->model->detalle($id);
        if (!$plan) { $this->json([]); return; }
        $this->json([
            'tipo_auditoria'   => $plan['tipo_auditoria']   ?? '',
            'objetivo'         => $plan['objetivo_general'] ?? '',
            'alcance'          => $plan['alcance']          ?? '',
            'id_auditor_lider' => $plan['id_auditor_lider'] ?? '',
            'auditor_nombre'   => $plan['auditor_nombre']   ?? '',
            'auditores'        => $plan['equipo_auditor']   ?? '',
            'procesos'         => $plan['procesos']         ?? [],
            'fecha_inicio'     => $plan['fecha_inicio']     ?? '',
        ]);
    }

}