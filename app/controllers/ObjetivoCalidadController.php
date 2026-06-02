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
            'pageTitle'  => 'Objetivos de Calidad — §6.2',
            'objetivos'  => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        $this->view('sgc/objetivos/form', [
            'pageTitle' => 'Nuevo Objetivo de Calidad',
            'item'      => null,
            'procesos'  => $this->procesoModel->activos(),
            'codigo'    => $this->model->siguienteCodigo(),
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['codigo','objetivo','meta','indicador','formula','frecuencia','responsable','id_proceso']);
        if (empty($data['objetivo'])) {
            Session::flash('error', 'El objetivo es obligatorio.');
            $this->redirect('/objetivos-calidad/crear');
            return;
        }
        $id = $this->model->insert($data);
        registrarAuditoria('objetivos_calidad','CREAR','objetivo_calidad',$id,null,$data);
        $this->redirectSuccess('/objetivos-calidad', 'Objetivo de calidad creado.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/objetivos/form', [
            'pageTitle'  => 'Editar Objetivo — ' . $item['codigo'],
            'item'       => $item,
            'procesos'   => $this->procesoModel->activos(),
            'mediciones' => $this->model->mediciones($id),
            'codigo'     => $item['codigo'],
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $data  = Request::only(['objetivo','meta','indicador','formula','frecuencia','responsable','id_proceso','estado']);
        $this->model->update($id, $data);
        registrarAuditoria('objetivos_calidad','EDITAR','objetivo_calidad',$id,$antes,$data);
        $this->redirectSuccess('/objetivos-calidad', 'Objetivo actualizado.');
    }

    public function registrarMedicion(int $id): void
    {
        Csrf::verify();
        $data = Request::only(['periodo','valor_obtenido','valor_meta','observacion']);
        $data['registrado_por'] = Auth::get('nombre_completo') ?? Auth::get('usuario');
        if (empty($data['periodo'])) {
            Session::flash('error', 'El período es obligatorio.');
            $this->redirect("/objetivos-calidad/editar/$id");
            return;
        }
        $this->model->registrarMedicion($id, $data);
        $this->redirectSuccess("/objetivos-calidad/editar/$id", 'Medición registrada.');
    }
}
