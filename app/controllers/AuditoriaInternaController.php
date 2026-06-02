<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\AuditoriaInternaModel;

class AuditoriaInternaController extends Controller
{
    private AuditoriaInternaModel $model;
    public function __construct() { $this->model = new AuditoriaInternaModel(); }

    public function index(): void
    {
        $this->view('sgc/auditoria/index', [
            'pageTitle' => 'Auditoría Interna — §9.2',
            'programas' => $this->model->listar(),
            'resumen'   => $this->model->resumenEstados(),
        ]);
    }

    public function crear(): void
    {
        $this->view('sgc/auditoria/form', [
            'pageTitle' => 'Nuevo Programa de Auditoría',
            'item'      => null,
            'hallazgos' => [],
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['anio','descripcion','objetivo','alcance','auditor_lider','auditores','fecha_inicio','fecha_fin','estado']);
        $errors = $this->validate($data, ['descripcion' => 'required', 'auditor_lider' => 'required']);
        if ($errors) { Session::flash('error', 'Descripción y auditor líder son obligatorios.'); $this->redirect('/auditoria-interna/crear'); return; }
        $id = $this->model->crear($data, Auth::id());
        registrarAuditoria('auditoria_interna','CREAR','auditoria_programa',$id,null,$data);
        $this->redirectSuccess('/auditoria-interna', 'Programa de auditoría creado.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('sgc/auditoria/form', [
            'pageTitle' => 'Editar Programa — ' . ($item['anio'] ?? ''),
            'item'      => $item,
            'hallazgos' => $this->model->hallazgos($id),
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        $data  = Request::only(['anio','descripcion','objetivo','alcance','auditor_lider','auditores','fecha_inicio','fecha_fin','estado']);
        $this->model->update($id, $data);
        registrarAuditoria('auditoria_interna','EDITAR','auditoria_programa',$id,$antes,$data);
        $this->redirectSuccess('/auditoria-interna', 'Programa actualizado.');
    }

    public function guardarHallazgo(int $idPrograma): void
    {
        Csrf::verify();
        $data = Request::only(['tipo','clausula_iso','proceso_auditado','descripcion','evidencia','accion_correctiva','responsable','fecha_cierre','estado']);
        if (empty($data['descripcion'])) { Session::flash('error', 'La descripción del hallazgo es obligatoria.'); $this->redirect("/auditoria-interna/editar/$idPrograma"); return; }
        $idH = $this->model->crearHallazgo($idPrograma, $data);
        registrarAuditoria('auditoria_interna','CREAR_HALLAZGO','auditoria_hallazgo',$idH,null,$data);
        $this->redirectSuccess("/auditoria-interna/editar/$idPrograma", 'Hallazgo registrado.');
    }
}
