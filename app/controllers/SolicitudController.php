<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\SolicitudModel;
use App\Models\TipoDocumentoModel;
use App\Models\EmpleadoModel;
use App\Models\ArchivoModel;

/**
 * Controlador de Solicitudes.
 * Gestiona radicar solicitudes y la gestión de las mismas.
 */
class SolicitudController extends Controller
{
    private SolicitudModel     $model;
    private TipoDocumentoModel $tipoModel;
    private EmpleadoModel      $empModel;
    private ArchivoModel       $archivoModel;

    private array $prioridades = [
        'URGENTE_IMPORTANTE'      => 'Urgente e Importante',
        'URGENTE_NO_IMPORTANTE'   => 'Urgente, No Importante',
        'NO_URGENTE_IMPORTANTE'   => 'No Urgente, Importante',
        'NO_URGENTE_NO_IMPORTANTE'=> 'No Urgente, No Importante',
    ];

    public function __construct()
    {
        $this->model        = new SolicitudModel();
        $this->tipoModel    = new TipoDocumentoModel();
        $this->empModel     = new EmpleadoModel();
        $this->archivoModel = new ArchivoModel();
    }

    // -----------------------------------------------------------------------
    // Radicar Solicitudes
    // -----------------------------------------------------------------------

    /** GET /solicitudes/mis-radicadas */
    public function misRadicadas(): void
    {
        $this->view('solicitudes/mis_radicadas', [
            'pageTitle'   => 'Mis Solicitudes Radicadas',
            'solicitudes' => $this->model->misRadicadas(Auth::empleadoId() ?? 0),
        ]);
    }

    /** GET /solicitudes/crear */
    public function crear(): void
    {
        $this->view('solicitudes/form_radicar', [
            'pageTitle'     => 'Radicar Solicitud — Crear Documento',
            'tipoSolicitud' => 'CREACION',
            'tipos'         => $this->tipoModel->activos(),
            'prioridades'   => $this->prioridades,
        ]);
    }

    /** POST /solicitudes/crear */
    public function guardarCrear(): void
    {
        Csrf::verify();
        $this->guardarSolicitud('CREACION', '/solicitudes/crear');
    }

    /** GET /solicitudes/actualizar */
    public function actualizar(): void
    {
        $this->view('solicitudes/form_radicar', [
            'pageTitle'     => 'Radicar Solicitud — Actualizar Documento',
            'tipoSolicitud' => 'ACTUALIZACION',
            'tipos'         => $this->tipoModel->activos(),
            'prioridades'   => $this->prioridades,
        ]);
    }

    /** POST /solicitudes/actualizar */
    public function guardarActualizar(): void
    {
        Csrf::verify();
        $this->guardarSolicitud('ACTUALIZACION', '/solicitudes/actualizar');
    }

    /** GET /solicitudes/eliminar */
    public function eliminar(): void
    {
        $this->view('solicitudes/form_radicar', [
            'pageTitle'     => 'Radicar Solicitud — Eliminar Documento',
            'tipoSolicitud' => 'ELIMINACION',
            'tipos'         => $this->tipoModel->activos(),
            'prioridades'   => $this->prioridades,
        ]);
    }

    /** POST /solicitudes/eliminar */
    public function guardarEliminar(): void
    {
        Csrf::verify();
        $this->guardarSolicitud('ELIMINACION', '/solicitudes/eliminar');
    }

    /**
     * Lógica común para guardar solicitud.
     */
    private function guardarSolicitud(string $tipoSolicitud, string $backUrl): never
    {
        $data   = Request::only(['prioridad', 'descripcion', 'id_tipo_documento', 'id_documento']);
        $errors = $this->validate($data, [
            'descripcion'       => 'required',
            'id_tipo_documento' => 'required|integer',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect($backUrl);
        }

        $idEmpleado = Auth::empleadoId();
        if (!$idEmpleado) {
            Session::flash('error', 'No se encontró el empleado del usuario.');
            $this->redirect($backUrl);
        }

        try {
            $idSolicitud = $this->model->crearViaSP(
                $tipoSolicitud,
                $data['prioridad'] ?? 'NO_URGENTE_IMPORTANTE',
                trim($data['descripcion']),
                $idEmpleado,
                (int) $data['id_tipo_documento'],
                $data['id_documento'] ? (int) $data['id_documento'] : null
            );

            // Agregar comentario inicial si aplica
            if (!empty($data['descripcion'])) {
                $this->model->agregarComentario($idSolicitud, $idEmpleado, trim($data['descripcion']));
            }

            // Subir adjunto si existe
            if (Request::hasFile('adjunto')) {
                try {
                    $upload = subirArchivo(
                        $_FILES['adjunto'],
                        'documentos',
                        ['application/pdf',
                         'application/msword',
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'image/jpeg', 'image/png'],
                        20971520
                    );
                    $this->archivoModel->registrar('SOLICITUD', $idSolicitud, $upload, Auth::get('usuario'));
                } catch (\RuntimeException $e) {
                    Session::flash('warning', 'Solicitud creada, adjunto no pudo subirse: ' . $e->getMessage());
                }
            }

            registrarAuditoria('solicitudes', 'CREAR', 'solicitud', $idSolicitud, null, [
                'tipo' => $tipoSolicitud,
                'prioridad' => $data['prioridad'] ?? '',
            ]);
            $this->redirectSuccess('/solicitudes/mis-radicadas', 'Solicitud radicada exitosamente. ID: ' . $idSolicitud);

        } catch (\Throwable $e) {
            Session::flash('error', 'Error al radicar: Error interno. Contacte al administrador.');
            Session::setOldInput($data);
            $this->redirect($backUrl);
        }
    }

    // -----------------------------------------------------------------------
    // Gestión de Solicitudes
    // -----------------------------------------------------------------------

    /** GET /solicitudes/radicadas */
    public function radicadas(): void
    {
        $this->view('solicitudes/gestion', [
            'pageTitle'   => 'Solicitudes Radicadas',
            'solicitudes' => $this->model->porEstado('CREADA'),
            'estado'      => 'CREADA',
            'empleados'   => $this->empModel->activos(),
        ]);
    }

    /** GET /solicitudes/asignadas */
    public function asignadas(): void
    {
        $this->view('solicitudes/gestion', [
            'pageTitle'   => 'Solicitudes Asignadas',
            'solicitudes' => $this->model->porEstado('ASIGNADA'),
            'estado'      => 'ASIGNADA',
            'empleados'   => [],
        ]);
    }

    /** GET /solicitudes/desarrollo */
    public function desarrollo(): void
    {
        $this->view('solicitudes/gestion', [
            'pageTitle'   => 'Solicitudes en Desarrollo',
            'solicitudes' => $this->model->porEstado('EN DESARROLLO'),
            'estado'      => 'EN DESARROLLO',
            'empleados'   => [],
        ]);
    }

    /** GET /solicitudes/finalizadas */
    public function finalizadas(): void
    {
        $desde = Request::get('desde');
        $hasta = Request::get('hasta');
        $this->view('solicitudes/finalizadas', [
            'pageTitle'   => 'Solicitudes Finalizadas',
            'solicitudes' => $this->model->finalizadas($desde ?: null, $hasta ?: null),
            'desde'       => $desde,
            'hasta'       => $hasta,
        ]);
    }

    /** GET /solicitudes/ver/{id} */
    public function ver(int $id): void
    {
        $detalle = $this->model->detalle($id);
        if (!$detalle) $this->abort(404, 'Solicitud no encontrada.');

        $this->view('solicitudes/ver', [
            'pageTitle' => 'Solicitud #' . $id,
            'sol'       => $detalle,
            'empleados' => $this->empModel->activos(),
        ]);
    }

    /** POST /solicitudes/asignar/{id} */
    public function asignar(int $id): void
    {
        Csrf::verify();
        $idEmpleado = (int) Request::post('id_empleado', 0);

        if (!$idEmpleado) {
            $this->redirectError("/solicitudes/ver/$id", 'Debe seleccionar un empleado.');
        }

        $this->model->asignar(
            $id,
            $idEmpleado,
            'ELABORADOR',
            Auth::get('usuario') ?? 'sistema'
        );

        registrarAuditoria('solicitudes', 'ASIGNAR', 'solicitud', $id, null, ['id_empleado' => $idEmpleado]);
        $this->redirectSuccess("/solicitudes/ver/$id", 'Solicitud asignada exitosamente.');
    }

    /** POST /solicitudes/comentar/{id} */
    public function comentar(int $id): void
    {
        Csrf::verify();
        $comentario = trim((string) Request::post('comentario', ''));

        if (empty($comentario)) {
            $this->redirectError("/solicitudes/ver/$id", 'El comentario no puede estar vacío.');
        }

        $this->model->agregarComentario($id, Auth::empleadoId() ?? 0, $comentario);
        $this->redirectSuccess("/solicitudes/ver/$id", 'Comentario agregado.');
    }
}
