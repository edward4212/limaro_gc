<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\RevisionDireccionModel;

class RevisionDireccionController extends Controller
{
    private RevisionDireccionModel $model;
    public function __construct() { $this->model = new RevisionDireccionModel(); }

    public function index(): void
    {
        $this->view('sgc/revision/index', [
            'pageTitle'  => 'Revisión por la Dirección — §9.3',
            'revisiones' => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        $this->view('sgc/revision/form', ['pageTitle' => 'Nueva Revisión por la Dirección', 'item' => null]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['anio','fecha_revision','convocado_por','participantes','entradas','desempeno_procesos','satisfaccion_partes','resultados_auditorias','no_conformidades','objetivos_calidad','riesgos_opor','recursos','salidas','mejoras_sgc','recursos_necesarios','estado']);
        $errors = $this->validate($data, ['fecha_revision' => 'required', 'convocado_por' => 'required']);
        if ($errors) { Session::flash('error', 'Fecha y convocante son obligatorios.'); $this->redirect('/revision-direccion/crear'); return; }

        if (Request::hasFile('archivo_acta') && $_FILES['archivo_acta']['error'] === UPLOAD_ERR_OK) {
            try {
                $upload = subirArchivo($_FILES['archivo_acta'], 'sgc/revision', ['application/pdf','image/jpeg','image/png'], 20971520);
                $data['archivo_acta'] = $upload['ruta_relativa'];
            } catch (\Throwable $e) {
                error_log('[Limaro SGC] Acta revisión: ' . $e->getMessage());
            }
        }
        $id = $this->model->crear($data, Auth::id());
        registrarAuditoria('revision_direccion','CREAR','revision_direccion',$id,null,$data);
        $this->redirectSuccess('/revision-direccion', 'Revisión por la dirección registrada.');
    }

    public function ver(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/revision/ver', ['pageTitle' => 'Revisión ' . ($item['fecha_revision'] ?? ''), 'item' => $item]);
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/revision/form', ['pageTitle' => 'Editar Revisión', 'item' => $item]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $data  = Request::only(['anio','fecha_revision','convocado_por','participantes','entradas','desempeno_procesos','satisfaccion_partes','resultados_auditorias','no_conformidades','objetivos_calidad','riesgos_opor','recursos','salidas','mejoras_sgc','recursos_necesarios','estado']);
        $this->model->update($id, $data);
        registrarAuditoria('revision_direccion','EDITAR','revision_direccion',$id,$antes,$data);
        $this->redirectSuccess('/revision-direccion', 'Revisión actualizada.');
    }
}
