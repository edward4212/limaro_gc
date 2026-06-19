<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\AuditoriaInternaModel;

class AuditoriaInternaController extends Controller
{
    private AuditoriaInternaModel $model;

    /** Roles con acceso de lectura al módulo */
    private const ROLES_VER    = [1, 2, 3, 8]; // Admin, Coord. Calidad, Líder Proceso, Auditor

    /** Roles con acceso de escritura (crear/editar/hallazgo) */
    private const ROLES_EDITAR = [1, 2, 8];    // Admin, Coord. Calidad, Auditor

    public function __construct() { $this->model = new AuditoriaInternaModel(); }

    public function index(): void
    {
        if (!Auth::hasRole(self::ROLES_VER)) $this->abort(403);
        $this->view('sgc/auditoria/index', [
            'pageTitle' => 'Auditoría Interna',
            'programas' => $this->model->listar(),
            'resumen'   => $this->model->resumenEstados(),
        ]);
    }

    public function crear(): void
    {
        if (!Auth::hasRole(self::ROLES_EDITAR)) $this->abort(403);
        $um = new \App\Models\UsuarioModel();
        $pm = new \App\Models\ProcesoModel();
        $this->view('sgc/auditoria/form', [
            'pageTitle' => 'Nuevo Programa de Auditoría',
            'item'      => null,
            'hallazgos' => [],
            'auditores' => $um->usuariosPorRol('AUDITOR'),
            'procesos'  => $pm->activos(),
        ]);
    }

    public function guardar(): void
    {
        if (!Auth::hasRole(self::ROLES_EDITAR)) $this->abort(403);
        Csrf::verify();
            'auditor_lider','id_auditor_lider',
            'tipo_auditoria','objetivos_especificos',
            'auditores','fecha_inicio','fecha_fin','estado'
        ]);

        $errors = $this->validate($data, [
            'descripcion'   => 'required',
            'auditor_lider' => 'required',
        ]);
        if ($errors) {
            Session::flash('error', 'Descripción y auditor líder son obligatorios.');
            $this->redirect('/auditoria-interna/crear');
            return;
        }

        // CA-2: valor por defecto tipo_auditoria
        $data['tipo_auditoria'] = $data['tipo_auditoria'] ?? 'CALIDAD';


        // Convertir vacíos a null para columnas DATE e INT
        foreach (['fecha_inicio','fecha_fin'] as $f) {
            if (isset($data[$f]) && $data[$f] === '') $data[$f] = null;
        }
        $data['id_auditor_lider'] = !empty($data['id_auditor_lider']) ? (int)$data['id_auditor_lider'] : null;
        $data['anio'] = !empty($data['anio']) ? (int)$data['anio'] : (int)date('Y');
        if (empty($data['auditor_lider'])) $data['auditor_lider'] = '—';
        $id = $this->model->crear($data, Auth::id());
        registrarAuditoria('auditoria_interna','CREAR','auditoria_programa',$id,null,$data);

        // CA-4: correo al auditor líder
        $idAuditorLider = (int)($data['id_auditor_lider'] ?? 0);
        if ($idAuditorLider) {
            try {
                $emp = (new \App\Models\EmpleadoModel())->porIdUsuario($idAuditorLider);

                if ($emp && filter_var($emp['correo_empleado'], FILTER_VALIDATE_EMAIL)) {
                    $tipo  = $data['tipo_auditoria'] ?? 'CALIDAD';
                    $anio  = $data['anio'] ?? date('Y');
                    $html  = "<h2 style='color:#1e5fbf;margin-top:0;'>
                                🔍 Nuevo Programa de Auditoría Asignado
                              </h2>
                              <p>Hola <strong>" . htmlspecialchars($emp['nombre_completo']) . "</strong>,</p>
                              <p>Fuiste designado como <strong>Auditor Líder</strong> en el siguiente programa:</p>
                              <table style='border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;
                                           border-radius:8px;overflow:hidden;'>
                                <tr style='background:#f8fafc;'>
                                  <td style='padding:8px 14px;font-weight:600;'>Descripción:</td>
                                  <td style='padding:8px 14px;'>" . htmlspecialchars($data['descripcion']) . "</td>
                                </tr>
                                <tr>
                                  <td style='padding:8px 14px;font-weight:600;'>Año:</td>
                                  <td style='padding:8px 14px;'>$anio</td>
                                </tr>
                                <tr style='background:#f8fafc;'>
                                  <td style='padding:8px 14px;font-weight:600;'>Tipo:</td>
                                  <td style='padding:8px 14px;'>$tipo</td>
                                </tr>
                                <tr>
                                  <td style='padding:8px 14px;font-weight:600;'>Período:</td>
                                  <td style='padding:8px 14px;'>" . ($data['fecha_inicio'] ?? '—') . " al " . ($data['fecha_fin'] ?? '—') . "</td>
                                </tr>
                              </table>
                              <br>
                              <a href='" . APP_URL . "/auditoria-interna'
                                 style='background:#1e5fbf;color:#fff;padding:12px 24px;
                                        border-radius:6px;text-decoration:none;font-size:14px;
                                        font-weight:600;display:inline-block;'>
                                 Ver Programa →
                              </a>
                              <br><br>
                              <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

                    enviarCorreo(
                        [$emp['correo_empleado'] => $emp['nombre_completo']],
                        "[SGC Limaro] Programa de Auditoría $anio — {$tipo}",
                        $html
                    );
                }
            } catch (\Throwable $eM) {
                error_log('[AuditoriaInterna] correo líder: ' . $eM->getMessage());
            }
        }

        $this->redirectSuccess('/auditoria-interna', 'Programa de auditoría creado. Se notificó al auditor líder.');
    }

    public function editar(int $id): void
    {
        if (!Auth::hasRole(self::ROLES_EDITAR)) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        // CA-4: no editar si finalizado o cancelado
        if (in_array($item['estado'] ?? '', ['FINALIZADA','CANCELADA'])) {
            Session::flash('warning',
                "El programa está <strong>{$item['estado']}</strong> y no puede editarse.");
            $this->redirect('/auditoria-interna');
            return;
        }
        $um = new \App\Models\UsuarioModel();
        $pm = new \App\Models\ProcesoModel();
        $this->view('sgc/auditoria/form', [
            'pageTitle' => 'Editar Programa — ' . ($item['anio'] ?? ''),
            'item'      => $item,
            'hallazgos' => $this->model->hallazgos($id),
            'auditores' => $um->usuariosPorRol('AUDITOR'),
            'procesos'  => $pm->activos(),
        ]);
    }

    public function actualizar(int $id): void
    {
        if (!Auth::hasRole(self::ROLES_EDITAR)) $this->abort(403);
        Csrf::verify();
        $data  = Request::only(['anio','descripcion','objetivo','alcance',
            'auditor_lider','id_auditor_lider','tipo_auditoria','objetivos_especificos',
            'auditores','fecha_inicio','fecha_fin','estado']);

        // Convertir vacíos a null para columnas DATE e INT
        foreach (['fecha_inicio','fecha_fin'] as $f) {
            if (isset($data[$f]) && $data[$f] === '') $data[$f] = null;
        }
        $data['id_auditor_lider'] = !empty($data['id_auditor_lider']) ? (int)$data['id_auditor_lider'] : null;
        $data['anio'] = !empty($data['anio']) ? (int)$data['anio'] : (int)date('Y');
        if (empty($data['auditor_lider'])) $data['auditor_lider'] = '—';
        // CA-3: bloquear FINALIZADO con hallazgos abiertos
        if (($data['estado'] ?? '') === 'FINALIZADA') {
            $abiertos = $this->model->contarHallazgosAbiertos($id);
            if ($abiertos > 0) {
                Session::flash('error',
                    "No se puede finalizar el programa: tiene <strong>{$abiertos}</strong> hallazgo(s) en estado ABIERTO. " .
                    "Cierre todos los hallazgos antes de finalizar.");
                $this->redirect("/auditoria-interna/editar/$id");
                return;
            }
        }

        $this->model->update($id, $data);
        registrarAuditoria('auditoria_interna','EDITAR','auditoria_programa',$id,$antes,$data);

        // CA-6: correo al auditor líder al actualizar
        $idLider = (int)($data['id_auditor_lider'] ?? $antes['id_auditor_lider'] ?? 0);
        if ($idLider) {
            try {
                $emp = (new \App\Models\EmpleadoModel())->porIdUsuario($idLider);
                if ($emp && filter_var($emp['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $html = "<h2 style='color:#0ea5e9;margin-top:0;'>📋 Programa de Auditoría Actualizado</h2>
                             <p>Hola <strong>" . htmlspecialchars($emp['nombre_completo']) . "</strong>,</p>
                             <p>El programa <strong>" . htmlspecialchars($data['descripcion'] ?? '') . "</strong>
                                (Año " . ($data['anio'] ?? '') . ") fue actualizado.</p>
                             <p>Estado actual: <strong>" . ($data['estado'] ?? '') . "</strong></p>
                             <a href='" . APP_URL . "/auditoria-interna'
                                style='background:#0ea5e9;color:#fff;padding:10px 24px;border-radius:6px;
                                       text-decoration:none;font-size:14px;display:inline-block;'>
                                Ver Programa →
                             </a>
                             <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático.</p>";
                    enviarCorreo([$emp['correo_empleado'] => $emp['nombre_completo']],
                        '[SGC Limaro] Programa de Auditoría Actualizado', $html);
                }
            } catch (\Throwable $eM) { error_log('[Auditoria] correo actualizar: ' . $eM->getMessage()); }
        }

        $this->redirectSuccess('/auditoria-interna', 'Programa actualizado.');
    }

    public function guardarHallazgo(int $idPrograma): void
    {
        if (!Auth::hasRole(self::ROLES_EDITAR)) $this->abort(403);
        Csrf::verify();
        $data = Request::only(['tipo','clausula_iso','proceso_auditado','descripcion','evidencia','accion_correctiva','responsable','fecha_cierre','estado']);
        if (empty($data['descripcion'])) { Session::flash('error', 'La descripción del hallazgo es obligatoria.'); $this->redirect("/auditoria-interna/editar/$idPrograma"); return; }
        $idH = $this->model->crearHallazgo($idPrograma, $data);
        registrarAuditoria('auditoria_interna','CREAR_HALLAZGO','auditoria_hallazgo',$idH,null,$data);

        // CA-5: correos al auditor líder y al responsable del hallazgo
        try {
            $programa = $this->model->find($idPrograma);
            $destinatarios = [];

            // Auditor líder
            $idLider2 = (int)($programa['id_auditor_lider'] ?? 0);
            if ($idLider2) {
                $empL = (new \App\Models\EmpleadoModel())->porIdUsuario($idLider2);
                if ($empL && filter_var($empL['correo_empleado'] ?? '', FILTER_VALIDATE_EMAIL)) {
                    $destinatarios[$empL['correo_empleado']] = $empL['nombre_completo'];
                }
            }

            // Responsable del hallazgo (si tiene correo)
            $respEmail = trim($data['responsable_correo'] ?? '');
            if ($respEmail && filter_var($respEmail, FILTER_VALIDATE_EMAIL)
                && !isset($destinatarios[$respEmail])) {
                $destinatarios[$respEmail] = $data['responsable'] ?? 'Responsable';
            }

            if (!empty($destinatarios)) {
                $tipoH  = str_replace('_', ' ', $data['tipo'] ?? 'HALLAZGO');
                $html   = "<h2 style='color:#dc3545;margin-top:0;'>⚠️ Nuevo Hallazgo Registrado</h2>
                           <p>Se registró un hallazgo en el Programa de Auditoría
                              <strong>" . htmlspecialchars($programa['descripcion'] ?? '') . "</strong>.</p>
                           <table style='border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;
                                        border-radius:8px;overflow:hidden;'>
                             <tr style='background:#f8fafc;'>
                               <td style='padding:8px 14px;font-weight:600;'>Tipo:</td>
                               <td style='padding:8px 14px;'><strong>$tipoH</strong></td>
                             </tr>
                             <tr>
                               <td style='padding:8px 14px;font-weight:600;'>Proceso:</td>
                               <td style='padding:8px 14px;'>" . htmlspecialchars($data['proceso_auditado'] ?? '—') . "</td>
                             </tr>
                             <tr style='background:#f8fafc;'>
                               <td style='padding:8px 14px;font-weight:600;'>Cláusula ISO:</td>
                               <td style='padding:8px 14px;'>" . htmlspecialchars($data['clausula_iso'] ?? '—') . "</td>
                             </tr>
                             <tr>
                               <td style='padding:8px 14px;font-weight:600;'>Descripción:</td>
                               <td style='padding:8px 14px;'>" . htmlspecialchars(substr($data['descripcion'] ?? '', 0, 200)) . "</td>
                             </tr>
                             <tr style='background:#f8fafc;'>
                               <td style='padding:8px 14px;font-weight:600;'>Responsable:</td>
                               <td style='padding:8px 14px;'>" . htmlspecialchars($data['responsable'] ?? '—') . "</td>
                             </tr>
                           </table>
                           <br>
                           <a href='" . APP_URL . "/auditoria-interna/editar/$idPrograma'
                              style='background:#dc3545;color:#fff;padding:10px 24px;border-radius:6px;
                                     text-decoration:none;font-size:14px;display:inline-block;'>
                              Ver Hallazgo →
                           </a>
                           <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático.</p>";

                foreach ($destinatarios as $email => $nombre) {
                    enviarCorreo([$email => $nombre],
                        '[SGC Limaro] Nuevo Hallazgo — ' . ($programa['descripcion'] ?? ''),
                        $html);
                }
            }
        } catch (\Throwable $eH) { error_log('[Auditoria] correo hallazgo: ' . $eH->getMessage()); }

        $this->redirectSuccess("/auditoria-interna/editar/$idPrograma", 'Hallazgo registrado.');
    }

    /** GET /auditoria-interna/ver/{id} — detalle solo lectura */
    public function ver(int $id): void
    {
        if (!Auth::hasRole(self::ROLES_VER)) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $um = new \App\Models\UsuarioModel();
        $pm = new \App\Models\ProcesoModel();
        $this->view('sgc/auditoria/ver', [
            'pageTitle'   => 'Auditoría #' . $id . ' — Solo Lectura',
            'item'        => $item,
            'auditores'   => $um->usuariosPorRol('AUDITOR'),
            'procesos'    => $pm->activos(),
            'soloLectura' => true,
        ]);
    }

}