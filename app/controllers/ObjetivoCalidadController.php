<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{ObjetivoCalidadModel, ProcesoModel};

class ObjetivoCalidadController extends Controller
{
    private ObjetivoCalidadModel $model;
    private ProcesoModel         $procesoModel;

    public function __construct()
    {
        $this->model        = new ObjetivoCalidadModel();
        $this->procesoModel = new ProcesoModel();
    }

    public function index(): void
    {
        $this->view('sgc/objetivos/index', [
            'pageTitle'  => 'Objetivos de Calidad',
            'objetivos'  => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('sgc/objetivos/form', [
            'pageTitle' => 'Nuevo Objetivo de Calidad',
            'item'      => null,
            'procesos'  => $this->procesoModel->activos(),
            'empleados' => (new \App\Models\EmpleadoModel())->activos(),
            'codigo'    => $this->model->siguienteCodigo(),
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['codigo','objetivo','meta','indicador','formula','frecuencia','id_responsable','id_proceso']);
        $data['id_responsable'] = ((int)($data['id_responsable'] ?? 0)) ?: null;
        $data['id_proceso']     = ((int)($data['id_proceso'] ?? 0)) ?: null;

        $faltantes = [];
        foreach (['codigo','objetivo','meta','indicador','formula','frecuencia','id_proceso'] as $campo) {
            if ($campo === 'id_proceso') continue; // §6.2: id_proceso es opcional ("— General —")
            if (empty($data[$campo])) $faltantes[] = $campo;
        }
        if ($faltantes) {
            Session::flash('error', 'Los siguientes campos son obligatorios: ' . implode(', ', $faltantes) . '.');
            Session::setOldInput(Request::all());
            $this->redirect('/objetivos-calidad/crear');
            return;
        }

        $id = $this->model->insert($data);
        registrarAuditoria('objetivos_calidad','CREAR','objetivo_calidad',$id,null,$data);
        $this->redirectSuccess('/objetivos-calidad', 'Objetivo de calidad creado.');
    }

    public function editar(int $id): void
    {
        if (!\App\Core\Auth::puede('objetivos_calidad', 'editar')) $this->abort(403);
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/objetivos/form', [
            'pageTitle'  => 'Editar Objetivo — ' . $item['codigo'],
            'item'       => $item,
            'procesos'   => $this->procesoModel->activos(),
            'empleados'  => (new \App\Models\EmpleadoModel())->activos(),
            'mediciones' => $this->model->mediciones($id),
            'codigo'     => $item['codigo'],
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        if (!\App\Core\Auth::puede('objetivos_calidad', 'editar')) $this->abort(403);
        $antes = $this->model->find($id);
        $data  = Request::only(['objetivo','meta','indicador','formula','frecuencia','id_responsable','id_proceso','estado']);
        $data['id_responsable'] = ((int)($data['id_responsable'] ?? 0)) ?: null;
        $data['id_proceso']     = ((int)($data['id_proceso'] ?? 0)) ?: null;
        if ($data['id_responsable'] !== null && !(new \App\Models\EmpleadoModel())->esActivo($data['id_responsable'])) {
            Session::flash('error', 'El responsable seleccionado no es válido. Seleccione un empleado activo.');
            $this->redirect("/objetivos-calidad/editar/{$id}");
            return;
        }
        $this->model->update($id, $data);
        registrarAuditoria('objetivos_calidad','EDITAR','objetivo_calidad',$id,$antes,$data);
        $this->redirectSuccess('/objetivos-calidad', 'Objetivo actualizado.');
    }

    public function registrarMedicion(int $id): void
    {
        Csrf::verify();
        $data = Request::only(['periodo','valor_obtenido','valor_meta','observacion']);
        $data['registrado_por'] = Auth::get('nombre_completo') ?? Auth::get('usuario');
        $data['id_usuario']     = Auth::id();
        if (empty($data['periodo'])) {
            Session::flash('error', 'El período es obligatorio.');
            $this->redirect("/objetivos-calidad/editar/$id");
            return;
        }
        $this->model->registrarMedicion($id, $data);
        $this->redirectSuccess("/objetivos-calidad/editar/$id", 'Medición registrada.');
    }

    public function eliminar(int $id): void
    {
        Csrf::verify();
        if (!\App\Core\Auth::puede('objetivos_calidad', 'eliminar')) $this->abort(403);

        $antes = $this->model->find($id);
        if (!$antes) $this->abort(404);

        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('sgc', 'ELIMINAR', 'objetivo_calidad', $id, $antes, null);
        $this->redirectSuccess('/objetivos-calidad', 'Objetivo eliminado.');
    }

}