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
        $this->view('sgc/acciones/form', [
            'pageTitle' => 'Nueva Acción Correctiva',
            'item'      => null,
            'codigo'    => $this->model->siguienteCodigo(),
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['codigo','origen','descripcion_nc','causa_raiz','accion_inmediata','accion_correctiva','responsable','fecha_planificada']);
        if (empty($data['descripcion_nc'])) {
            Session::flash('error', 'La descripción de la no conformidad es obligatoria.');
            $this->redirect('/acciones-correctivas/crear');
            return;
        }
        $data['id_usuario'] = Auth::id();
        $id = $this->model->insert($data);
        registrarAuditoria('acciones_correctivas','CREAR','accion_correctiva',$id,null,$data);
        $this->redirectSuccess('/acciones-correctivas', 'Acción correctiva registrada.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/acciones/form', [
            'pageTitle' => 'Editar AC — ' . $item['codigo'],
            'item'      => $item,
            'codigo'    => $item['codigo'],
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $data  = Request::only(['origen','descripcion_nc','causa_raiz','accion_inmediata','accion_correctiva','responsable','fecha_planificada','fecha_cierre','eficacia','eficaz','estado']);
        $this->model->update($id, $data);
        registrarAuditoria('acciones_correctivas','EDITAR','accion_correctiva',$id,$antes,$data);
        $this->redirectSuccess('/acciones-correctivas', 'Acción correctiva actualizada.');
    }
}
