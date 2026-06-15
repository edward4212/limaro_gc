<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{AuditoriaInformeModel, AuditoriaProgramaModel, AuditoriaPlanModel, EmpleadoModel, UsuarioModel, AuditoriaInformeComponenteModel, AuditoriaInformeDistribucionModel};

class AuditoriaInformeController extends Controller
{
    private AuditoriaInformeModel $model;

    public function __construct() { $this->model = new AuditoriaInformeModel(); }

    /** GET /auditoria/informe */
    public function index(): void
    {
        $filtros = ['anio'=>Request::get('anio',''), 'estado'=>Request::get('estado','')];
        $this->view('sgc/auditoria_informe/index', [
            'pageTitle' => 'Informes de Auditoría §9.2',
            'informes'  => $this->model->listar($filtros),
            'filtros'   => $filtros,
        ]);
    }

    /** GET /auditoria/informe/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        $programas = (new AuditoriaProgramaModel())->conPlanAprobado();
        $this->view('sgc/auditoria_informe/form', [
            'pageTitle' => 'Nuevo Informe de Auditoría',
            'item'      => null,
            'codigo'    => $this->model->siguienteCodigo(),
            'programas' => $programas,
            'empleados' => (new EmpleadoModel())->activos(),
        ]);
    }

    /** POST /auditoria/informe/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only([
            'id_programa','id_plan','codigo','tipo_auditoria','fecha_informe',
            'sumario_ejecutivo','criterio_auditoria','objetivo','alcance',
            'opinion','contextualizacion','antecedentes',
            'resultados_auditoria','evaluacion','id_auditor_lider',
        ]);
        $data['id_usuario'] = Auth::id();
        $data['estado']     = 'BORRADOR';
        if (empty($data['tipo_auditoria'])) $data['tipo_auditoria'] = Request::input('tipo_auditoria_hidden','CALIDAD') ?: 'CALIDAD';

        if (empty($data['sumario_ejecutivo'])) {
            Session::flash('error', 'El sumario ejecutivo es requerido.');
            $this->redirect('/auditoria/informe/crear'); return;
        }

        // Verificar que el programa no tenga ya un informe
        if (!empty($data['id_programa'])) {
            $existe = $this->model->informeDelPrograma((int)$data['id_programa']);
            if ($existe) {
                Session::flash('error', 'El programa ya tiene un informe asociado (' . $existe['codigo'] . ').');
                $this->redirect('/auditoria/informe/crear'); return;
            }
        }

        $id = $this->model->insert($data);
        registrarAuditoria('auditoria_informe','CREAR','auditoria_informe',$id,null,$data);
        $this->redirectSuccess('/auditoria/informe', 'Informe creado correctamente.');
    }

    /** GET /auditoria/informe/{id} */
    public function ver(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        $compModel = new AuditoriaInformeComponenteModel();
        $distModel = new AuditoriaInformeDistribucionModel();
        $this->view('sgc/auditoria_informe/ver', [
            'pageTitle'     => 'Informe — ' . $item['codigo'],
            'item'          => $item,
            'componentes'   => $compModel->porInforme($id),
            'resumen'       => $compModel->resumenCalificaciones($id),
            'distribuciones'=> $distModel->porInforme($id),
            'empleados'     => (new EmpleadoModel())->activos(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // HU-AI-11: COMPONENTES DEL INFORME
    // ─────────────────────────────────────────────────────────────────

    /** POST /auditoria/informe/{id}/componentes */
    public function guardarComponente(int $id): void
    {
        Csrf::verify();
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'Solo informes en BORRADOR permiten agregar componentes.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        $data = Request::only(['componente','descripcion','calificacion','observaciones','orden']);
        $data['id_informe'] = $id;
        if (empty($data['orden'])) {
            $compModel = new AuditoriaInformeComponenteModel();
            $data['orden'] = count($compModel->porInforme($id)) + 1;
        }
        try {
            (new AuditoriaInformeComponenteModel())->insert($data);
            Session::flash('success', 'Componente agregado.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al guardar el componente.');
        }
        $this->redirect("/auditoria/informe/{$id}");
    }

    /** POST /auditoria/informe/{id}/componentes/eliminar/{cid} */
    public function eliminarComponente(int $id, int $cid): void
    {
        Csrf::verify();
        (new AuditoriaInformeComponenteModel())->delete($cid);
        Session::flash('success', 'Componente eliminado.');
        $this->redirect("/auditoria/informe/{$id}");
    }

    // ─────────────────────────────────────────────────────────────────
    // HU-AI-13: DISTRIBUCIÓN DEL INFORME
    // ─────────────────────────────────────────────────────────────────

    /** POST /auditoria/informe/{id}/distribuir */
    public function distribuir(int $id): void
    {
        Csrf::verify();
        $item = $this->model->detalle($id);
        if (!$item || $item['estado'] !== 'APROBADO') {
            Session::flash('error', 'Solo informes APROBADOS pueden distribuirse.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }

        $correo     = trim(Request::post('correo_destinatario',''));
        $nombre     = trim(Request::post('nombre_destinatario',''));
        $cargo      = trim(Request::post('cargo_destinatario',''));
        $medio      = Request::post('medio','CORREO');
        $idEmpleado = Request::post('id_empleado','') ?: null;

        // Si seleccionó empleado, traer sus datos
        if ($idEmpleado) {
            $emp = (new EmpleadoModel())->correoYNombre((int)$idEmpleado);
            if ($emp) {
                $nombre = $nombre ?: $emp['nombre_completo'];
                $correo = $correo ?: ($emp['correo_empleado'] ?? '');
            }
        }

        if (empty($nombre)) {
            Session::flash('error', 'El nombre del destinatario es requerido.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }

        $distModel = new AuditoriaInformeDistribucionModel();
        $distModel->insert([
            'id_informe'          => $id,
            'id_empleado'         => $idEmpleado,
            'nombre_destinatario' => $nombre,
            'cargo_destinatario'  => $cargo,
            'correo_destinatario' => $correo,
            'medio'               => $medio,
            'fecha_envio'         => date('Y-m-d H:i:s'),
            'confirmacion_recibo' => 0,
        ]);

        // Enviar correo si tiene email válido
        if ($medio === 'CORREO' && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            try {
                $html = "<h2 style='color:#1B3A6B;'>📄 Informe de Auditoría</h2>
                    <p>Estimado/a <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                    <p>Se ha puesto a su disposición el informe de auditoría
                    <strong>{$item['codigo']}</strong> para su conocimiento.</p>
                    <table style='font-size:13px;border-collapse:collapse;'>
                        <tr><td style='color:#6b7280;padding:4px 8px;'>Tipo:</td><td>" . htmlspecialchars($item['tipo_auditoria']??'') . "</td></tr>
                        <tr><td style='color:#6b7280;padding:4px 8px;'>Fecha:</td><td>" . htmlspecialchars($item['fecha_informe']??'') . "</td></tr>
                        <tr><td style='color:#6b7280;padding:4px 8px;'>Auditor:</td><td>" . htmlspecialchars($item['auditor_nombre']??'') . "</td></tr>
                    </table>
                    <br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC — Mensaje automático</p>";
                enviarCorreo([$correo => $nombre],
                    "[SGC] Informe de Auditoría {$item['codigo']}", $html);
            } catch (\Throwable $e) {
                error_log('[Informe] correo distribucion: ' . $e->getMessage());
            }
        }

        // Cambiar estado a DISTRIBUIDO si es primera distribución
        if ($item['estado'] === 'APROBADO') {
            $this->model->cambiarEstado($id, 'DISTRIBUIDO');
        }

        Session::flash('success', "Informe distribuido a {$nombre}" . ($correo ? " y correo enviado." : "."));
        $this->redirect("/auditoria/informe/{$id}");
    }

    /** POST /auditoria/informe/{id}/distribucion/confirmar/{did} */
    public function confirmarRecibo(int $id, int $did): void
    {
        Csrf::verify();
        (new AuditoriaInformeDistribucionModel())->update($did, ['confirmacion_recibo' => 1]);
        Session::flash('success', 'Recibo confirmado.');
        $this->redirect("/auditoria/informe/{$id}");
    }

    /** GET /auditoria/informe/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        if ($item['estado'] !== 'BORRADOR') {
            Session::flash('warning', 'Solo informes en BORRADOR pueden editarse.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        Session::clearOldInput();
        $this->view('sgc/auditoria_informe/form', [
            'pageTitle' => 'Editar Informe — ' . $item['codigo'],
            'item'      => $item,
            'codigo'    => $item['codigo'],
            'programas' => (new AuditoriaProgramaModel())->conPlanAprobado(),
            'empleados' => (new EmpleadoModel())->activos(),
        ]);
    }

    /** POST /auditoria/informe/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'No se puede editar este informe.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        $data = Request::only([
            'id_programa','id_plan','tipo_auditoria','fecha_informe',
            'sumario_ejecutivo','criterio_auditoria','objetivo','alcance',
            'opinion','contextualizacion','antecedentes',
            'resultados_auditoria','evaluacion','id_auditor_lider',
        ]);
        if (empty($data['tipo_auditoria'])) $data['tipo_auditoria'] = Request::input('tipo_auditoria_hidden','CALIDAD') ?: 'CALIDAD';
        $this->model->update($id, $data);
        registrarAuditoria('auditoria_informe','EDITAR','auditoria_informe',$id,$item,$data);
        $this->redirectSuccess('/auditoria/informe', 'Informe actualizado.');
    }

    /** POST /auditoria/informe/revisar/{id} */
    public function enviarARevision(int $id): void
    {
        Csrf::verify();
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'Solo informes en BORRADOR pueden enviarse a revisión.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        $this->model->cambiarEstado($id, 'EN_REVISION');
        try {
            $um = new UsuarioModel();
            foreach ($um->usuariosPorRol('COORDINADOR CALIDAD') as $coord) {
                if (!filter_var($coord['correo_empleado']??'', FILTER_VALIDATE_EMAIL)) continue;
                $html = "<h2 style='color:#f59e0b;'>📄 Informe de Auditoría en Revisión</h2>
                    <p>El informe <strong>{$item['codigo']}</strong> requiere su aprobación.</p>
                    <a href='" . APP_URL . "/auditoria/informe/{$id}' style='background:#1B3A6B;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;'>Revisar Informe →</a>
                    <br><br><p style='color:#9ca3af;font-size:11px;'>Limaro SGC</p>";
                enviarCorreo([$coord['correo_empleado']=>$coord['nombre_completo']],
                    "[SGC] Informe {$item['codigo']} — Requiere aprobación", $html);
            }
        } catch (\Throwable $e) { error_log('[Informe] correo revision: '.$e->getMessage()); }
        $this->redirectSuccess('/auditoria/informe', 'Informe enviado a revisión.');
    }

    /** POST /auditoria/informe/aprobar/{id} */
    public function aprobar(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'EN_REVISION') {
            Session::flash('error', 'Solo informes en EN REVISIÓN pueden aprobarse.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        $this->model->cambiarEstado($id, 'FINALIZADO');
        registrarAuditoria('auditoria_informe','APROBAR','auditoria_informe',$id,
            ['estado'=>'EN_REVISION'],['estado'=>'FINALIZADO']);

        // Finalizar también el Plan y el Programa vinculados
        $informe = $this->model->detalle($id);
        if (!empty($informe['id_plan'])) {
            (new AuditoriaPlanModel())->cambiarEstado((int)$informe['id_plan'], 'FINALIZADO');
            (new AuditoriaPlanModel())->sincronizarEstadoPrograma((int)$informe['id_plan'], 'FINALIZADO');
        }

        $this->redirectSuccess('/auditoria/informe', 'Informe aprobado. Plan y Programa quedaron en estado FINALIZADO.');
    }

    /** POST /auditoria/informe/devolver/{id} */
    public function devolver(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'EN_REVISION') {
            Session::flash('error', 'Solo informes en revisión pueden devolverse.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        $comentario = trim(Request::post('comentario_devolucion',''));
        if (mb_strlen($comentario) < 10) {
            Session::flash('error', 'El motivo de devolución debe tener al menos 10 caracteres.');
            $this->redirect("/auditoria/informe/{$id}"); return;
        }
        try {
            $this->model->update($id, ['estado'=>'BORRADOR','comentario_devolucion'=>$comentario]);
        } catch (\Throwable $e) {
            // Si comentario_devolucion no existe aún, solo cambiar estado
            $this->model->cambiarEstado($id, 'BORRADOR');
        }
        registrarAuditoria('auditoria_informe','DEVOLVER','auditoria_informe',$id,
            ['estado'=>'EN_REVISION'],['estado'=>'BORRADOR']);
        $this->redirectSuccess('/auditoria/informe', 'Informe devuelto a BORRADOR.');
    }

    /** GET /auditoria/informe/programa/{id}/datos — AJAX */
    public function datosProgramaInforme(int $id): void
    {
        $prog = (new AuditoriaProgramaModel())->detalle($id);
        if (!$prog) { $this->json([]); return; }

        // Traer datos del PLAN vinculado
        $plan = null;
        if (!empty($prog['id_plan'])) {
            $plan = (new AuditoriaPlanModel())->detalle((int)$prog['id_plan']);
        }

        $this->json([
            'tipo_auditoria'   => $plan['tipo_auditoria']   ?? $prog['tipo_auditoria']   ?? '',
            'id_auditor_lider' => $plan['id_auditor_lider'] ?? $prog['id_auditor_lider'] ?? '',
            'auditor_nombre'   => $plan['auditor_nombre']   ?? $prog['auditor_nombre']   ?? '',
            'alcance'          => $plan['alcance']          ?? $prog['alcance']          ?? '',
            'objetivo'         => $plan['objetivo_general'] ?? $prog['objetivo']         ?? '',
            'id_plan'          => $prog['id_plan']          ?? '',
        ]);
    }
}
