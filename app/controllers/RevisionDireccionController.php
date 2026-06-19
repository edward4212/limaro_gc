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
            'pageTitle'  => 'Revisión por la Dirección',
            'revisiones' => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        Session::clearOldInput();
        $um = new \App\Models\UsuarioModel();
        $this->view('sgc/revision/form', [
            'pageTitle' => 'Nueva Revisión por la Dirección',
            'item'      => null,
            'usuarios'  => $um->usuariosActivosTodos(),
        ]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['anio','fecha_revision','convocado_por',
            'id_usuario_convocador','participantes','entradas','desempeno_procesos','satisfaccion_partes','resultados_auditorias','no_conformidades','objetivos_calidad','riesgos_opor','recursos','salidas','mejoras_sgc','recursos_necesarios','estado']);
        if (empty(trim($data['fecha_revision'] ?? ''))) {
            Session::flash('error', 'La fecha de la revisión es obligatoria.');
            $this->redirect('/revision-direccion/crear');
            return;
        }
        if (empty($data['convocado_por']) && !empty($data['id_usuario_convocador'])) {
            // Recuperar nombre desde BD si el hidden JS falló
            try {
                $emp = (new \App\Models\EmpleadoModel())->porIdUsuario((int)$data['id_usuario_convocador']);
                $data['convocado_por'] = $emp['nombre_completo'] ?? '';
            } catch (\Throwable $e) {
                error_log('[RevisionDireccionController] recuperar convocador: ' . $e->getMessage());
            }
        }

        $tieneArchivo = !empty($_POST['archivo_acta_b64'])
                     || (isset($_FILES['archivo_acta']) && ($_FILES['archivo_acta']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE);
        if ($tieneArchivo) {
            try {
                $fileRef = $_FILES['archivo_acta'] ?? [];
                $fileRef['field_name'] = 'archivo_acta';
                $upload = subirArchivo($fileRef, 'sgc/revision', ['application/pdf','image/jpeg','image/png'], 20971520);
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

        // CA-3: redirigir si está APROBADA — con enlace a ver solo lectura
        if (($item['estado'] ?? '') === 'APROBADA') {
            Session::flash('warning',
                'Esta revisión está <strong>APROBADA</strong> y no puede modificarse. ' .
                '<a href="' . APP_URL . '/revision-direccion/ver/' . $id . '" ' .
                'class="btn btn-sm btn-outline-primary ms-2">' .
                '<i class="bi bi-eye me-1"></i>Ver en modo lectura</a>'
            );
            $this->redirect('/revision-direccion');
            return;
        }

        $um = new \App\Models\UsuarioModel();
        $this->view('sgc/revision/form', [
            'pageTitle' => 'Editar Revisión',
            'item'      => $item,
            'usuarios'  => $um->usuariosActivosTodos(),
        ]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);

        // CA-2: no procesar si está APROBADA (defensa en profundidad)
        if (($antes['estado'] ?? '') === 'APROBADA') {
            Session::flash('error', 'No se pueden guardar cambios: la revisión está APROBADA.');
            $this->redirect('/revision-direccion');
            return;
        }
        $data = Request::only(['anio','fecha_revision','convocado_por','id_usuario_convocador',
            'participantes','entradas','desempeno_procesos','satisfaccion_partes',
            'resultados_auditorias','no_conformidades','objetivos_calidad','riesgos_opor',
            'recursos','salidas','mejoras_sgc','recursos_necesarios','estado']);
        if (empty($data['fecha_revision'])) $data['fecha_revision'] = null;
        $this->model->update($id, $data);
        registrarAuditoria('revision_direccion','EDITAR','revision_direccion',$id,$antes,$data);
        $this->redirectSuccess('/revision-direccion', 'Revisión actualizada.');
    }

    public function eliminar(int $id): void
    {
        Csrf::verify();
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        if (($item['estado'] ?? '') === 'APROBADA') {
            Session::flash('error', 'No se puede eliminar una revisión APROBADA.');
            $this->redirect('/revision-direccion');
            return;
        }
        $this->model->update($id, ['estado' => 'INACTIVO']);
        registrarAuditoria('revision_direccion','ELIMINAR','revision_direccion',$id,$item,null);
        $this->redirectSuccess('/revision-direccion', 'Revisión eliminada.');
    }

}