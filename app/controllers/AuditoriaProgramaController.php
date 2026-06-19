<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{AuditoriaProgramaModel, AuditoriaPlanModel, AuditoriaProgramaActividadModel, ProcesoModel, EmpleadoModel};

class AuditoriaProgramaController extends Controller
{
    private AuditoriaProgramaModel $model;

    public function __construct()
    {
        $this->model = new AuditoriaProgramaModel();
    }

    private function datos(): array
    {
        return [
            'procesos'  => (new ProcesoModel())->activos(),
            'empleados' => (new EmpleadoModel())->activos(),
            'planes'    => (new AuditoriaPlanModel())->sinPrograma(),
        ];
    }

    /** GET /auditoria/programa */
    public function index(): void
    {
        $filtros = ['anio' => Request::get('anio',''), 'estado' => Request::get('estado','')];
        $this->view('sgc/auditoria_programa/index', [
            'pageTitle' => 'Programas de Auditoría',
            'programas' => $this->model->listar($filtros),
            'filtros'   => $filtros,
        ]);
    }

    /** GET /auditoria/programa/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        $usados = $this->model->planesUsados();
        $this->view('sgc/auditoria_programa/form', array_merge($this->datos(), [
            'pageTitle'   => 'Nuevo Programa de Auditoría',
            'item'        => null,
            'codigo'      => $this->model->siguienteCodigo(),
            'planesUsados'=> $usados,
        ]));
    }

    /** POST /auditoria/programa/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['id_plan','codigo','anio','objetivo',
                                'objetivos_especificos','alcance','id_proceso',
                                'tipo_auditoria','id_auditor_lider',
                                'auditores','fecha_inicio','fecha_fin','fecha_auditoria']);

        // Campos disabled no se envían en POST — leer hidden backups
        if (empty($data['tipo_auditoria']))   $data['tipo_auditoria']   = Request::input('tipo_auditoria_hidden', 'CALIDAD') ?: 'CALIDAD';
        if (empty($data['id_proceso']))       $data['id_proceso']       = Request::input('id_proceso_hidden', '') ?: null;
        if (empty($data['id_auditor_lider'])) $data['id_auditor_lider'] = Request::input('id_auditor_lider_hidden', '') ?: null;

        // Validar al menos un objetivo específico
        $objetivos  = (array)Request::post('obj_objetivo', []);
        $objFilled  = array_filter($objetivos, fn($o) => !empty(trim($o ?? '')));
        if (empty($objFilled)) {
            Session::flash('error', 'Debe agregar al menos un Objetivo Específico antes de crear el programa.');
            $this->redirect('/auditoria/programa/crear');
            return;
        }

        $data['descripcion'] = $data['objetivo'] ?? '';

        // auditor_lider (varchar NOT NULL) — obtener nombre del empleado
        if (!empty($data['id_auditor_lider'])) {
            $empModel = new EmpleadoModel();
            $emp = $empModel->correoYNombre((int)$data['id_auditor_lider']);
            $data['auditor_lider'] = $emp['nombre_completo'] ?? '';
        } else {
            $data['auditor_lider'] = '';
        }
        $data['id_usuario'] = Auth::id();
        $data['estado']     = 'BORRADOR';

        // Un solo programa por plan
        if (!empty($data['id_plan'])) {
            $existente = $this->model->planYaTienePrograma((int)$data['id_plan']);
            if ($existente) {
                Session::flash('error', 'El plan seleccionado ya tiene un Programa de Auditoría asignado (ID: ' . $existente['id'] . ').');
                Session::setOldInput($data);
                $this->redirect('/auditoria/programa/crear');
                return;
            }
        }

        $errors = $this->validate($data, [
            'descripcion' => 'required',
            'objetivo'    => 'required',
            'anio'        => 'required|integer',
        ]);
        if ($errors) {
            Session::flash('error', 'Corrija los campos requeridos.');
            Session::setOldInput($data);
            $this->redirect('/auditoria/programa/crear');
            return;
        }

        $id = $this->model->insert($data);
        if (!$id) {
            Session::flash('error', 'Error al guardar el programa.');
            $this->redirect('/auditoria/programa/crear'); return;
        }

        // Guardar objetivos específicos
        $this->guardarObjetivos($id, false);

        registrarAuditoria('auditoria_programa','CREAR','auditoria_programa',$id,null,$data);
        $this->redirectSuccess('/auditoria/programa', 'Programa de Auditoría creado.');
    }

    /** GET /auditoria/programa/{id} */
    public function ver(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        $actModel   = new AuditoriaProgramaActividadModel();
        $actividades = $actModel->porPrograma($id);
        $this->view('sgc/auditoria_programa/ver', [
            'pageTitle'   => 'Programa — ' . ($item['codigo'] ?? '#'.$id),
            'item'        => $item,
            'actividades' => $actividades,
        ]);
    }

    /** GET /auditoria/programa/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        // Editable solo si el plan está en BORRADOR
        $planEstado = $item['plan_estado'] ?? $item['estado'];
        if ($planEstado !== 'BORRADOR') {
            Session::flash('warning',
                'El programa no puede editarse. El plan vinculado está en estado <strong>' . $planEstado . '</strong>.');
            $this->redirect("/auditoria/programa/{$id}");
            return;
        }
        Session::clearOldInput();
        $actModel    = new AuditoriaProgramaActividadModel();
        $actividades = $actModel->porPrograma($id);
        $this->view('sgc/auditoria_programa/form', array_merge($this->datos(), [
            'pageTitle'   => 'Editar Programa — ' . $item['codigo'],
            'item'        => $item,
            'codigo'      => $item['codigo'],
            'actividades' => $actividades,
        ]));
    }

    /** POST /auditoria/programa/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $item = $this->model->detalle($id);
        if (!$item) { $this->abort(404); return; }

        // Verificar que el plan esté en BORRADOR
        if (!empty($item['id_plan'])) {
            $plan = (new AuditoriaPlanModel())->find((int)$item['id_plan']);
            if ($plan && $plan['estado'] !== 'BORRADOR') {
                Session::flash('error', 'No se puede editar: el plan vinculado está en estado ' . $plan['estado'] . '.');
                $this->redirect("/auditoria/programa/{$id}"); return;
            }
        } elseif ($item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'Solo programas en BORRADOR pueden editarse.');
            $this->redirect("/auditoria/programa/{$id}"); return;
        }
        $data = Request::only(['id_plan','anio','objetivo','objetivos_especificos',
                                'alcance','id_proceso','tipo_auditoria','id_auditor_lider',
                                'auditores','fecha_inicio','fecha_fin','fecha_auditoria']);
        if (empty($data['tipo_auditoria']))   $data['tipo_auditoria']   = Request::input('tipo_auditoria_hidden','CALIDAD') ?: 'CALIDAD';
        if (empty($data['id_proceso']))       $data['id_proceso']       = Request::input('id_proceso_hidden','') ?: null;
        if (empty($data['id_auditor_lider'])) $data['id_auditor_lider'] = Request::input('id_auditor_lider_hidden','') ?: null;
        $data['descripcion'] = $data['objetivo'] ?? '';
        if (!empty($data['id_auditor_lider'])) {
            $emp = (new EmpleadoModel())->correoYNombre((int)$data['id_auditor_lider']);
            $data['auditor_lider'] = $emp['nombre_completo'] ?? '';
        } else {
            $data['auditor_lider'] = '';
        }
        $this->model->update($id, $data);

        // Actualizar objetivos específicos
        $this->guardarObjetivos($id, true);

        registrarAuditoria('auditoria_programa','EDITAR','auditoria_programa',$id,$item,$data);
        $this->redirectSuccess('/auditoria/programa', 'Programa actualizado.');
    }

    /** POST /auditoria/programa/aprobar/{id} */
    public function aprobar(int $id): void
    {
        Csrf::verify();
        if (!Auth::hasRole([1,2])) $this->abort(403);
        $item = $this->model->find($id);
        if (!$item || $item['estado'] !== 'BORRADOR') {
            Session::flash('error', 'Solo programas en BORRADOR pueden aprobarse.');
            $this->redirect("/auditoria/programa/{$id}"); return;
        }
        $this->model->cambiarEstado($id, 'APROBADO');
        registrarAuditoria('auditoria_programa','APROBAR','auditoria_programa',$id,
            ['estado'=>'BORRADOR'],['estado'=>'APROBADO']);
        $this->redirectSuccess('/auditoria/programa', 'Programa aprobado.');
    }

    private function guardarObjetivos(int $idPrograma, bool $isEdit): void
    {
        $actModel = new AuditoriaProgramaActividadModel();

        // Estado de los objetivos = estado del plan vinculado
        $prog      = $this->model->find($idPrograma);
        $idPlan    = $prog['id_plan'] ?? null;
        $estadoPlan = 'BORRADOR';
        if ($idPlan) {
            $plan = (new AuditoriaPlanModel())->find((int)$idPlan);
            $estadoPlan = $plan['estado'] ?? 'BORRADOR';
        }

        $objetivos    = Request::post('obj_objetivo',    []);
        $criterios    = Request::post('obj_criterio',    []);
        $actividades  = Request::post('obj_actividad',   []);
        $riesgos      = Request::post('obj_riesgo',      []);
        $procedimientos = Request::post('obj_procedimiento', []);
        $observaciones  = Request::post('obj_observacion',   []);
        $referencias    = Request::post('obj_referenciacion', []);
        $papeles        = Request::post('obj_papel',     []);
        $ids            = Request::post('obj_id',        []);

        if (!is_array($objetivos) || empty($objetivos)) return;

        // En edición, eliminar los que ya no están
        if ($isEdit) {
            $idsExistentes = array_filter($ids, fn($i) => !empty($i));
            if ($idsExistentes) {
                $actModel->eliminarExcepto($idPrograma, array_values($idsExistentes));
            } else {
                $actModel->eliminarTodas($idPrograma);
            }
        }

        foreach ($objetivos as $k => $obj) {
            if (empty(trim($obj ?? ''))) continue;
            $fila = [
                'id_programa'            => $idPrograma,
                'objetivo_especifico'    => trim($obj),
                'criterio'               => trim($criterios[$k] ?? ''),
                'actividad'              => trim($actividades[$k] ?? ''),
                'riesgo'                 => trim($riesgos[$k] ?? ''),
                'procedimiento_auditoria'=> trim($procedimientos[$k] ?? ''),
                'observacion'            => trim($observaciones[$k] ?? ''),
                'referenciacion'         => trim($referencias[$k] ?? ''),
                'papel_de_trabajo'       => trim($papeles[$k] ?? ''),
                'orden'                  => $k + 1,
                'estado'                 => $estadoPlan,
            ];
            $objId = !empty($ids[$k]) ? (int)$ids[$k] : 0;
            if ($objId > 0) {
                $actModel->update($objId, $fila);
            } else {
                $actModel->insert($fila);
            }
        }
    }

}