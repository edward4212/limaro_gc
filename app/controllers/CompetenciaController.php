<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{CompetenciaCargoModel, CapacitacionEmpleadoModel, EmpleadoModel, CargoModel};

class CompetenciaController extends Controller
{
    /** GET /competencia — perfil de competencias por cargo */
    public function index(): void
    {
        $this->view('sgc/competencia/index', [
            'pageTitle' => 'Competencia y Capacitación',
            'cargos'    => (new CompetenciaCargoModel())->listarConCargos(),
        ]);
    }

    /** GET /competencia/cargo/{id} */
    public function editarPerfil(int $idCargo): void
    {
        $cargo = (new CargoModel())->find($idCargo);
        if (!$cargo) $this->abort(404);

        $perfil = (new CompetenciaCargoModel())->porCargo($idCargo);

        $this->view('sgc/competencia/perfil', [
            'pageTitle' => 'Perfil de Competencias — ' . $cargo['cargo'],
            'cargo'     => $cargo,
            'perfil'    => $perfil,
        ]);
    }

    /** POST /competencia/cargo/{id} */
    public function guardarPerfil(int $idCargo): void
    {
        Csrf::verify();
        $cargo = (new CargoModel())->find($idCargo);
        if (!$cargo) $this->abort(404);

        $data = Request::only([
            'formacion_academica', 'experiencia', 'formacion_entrenamiento', 'habilidades',
        ]);
        $data['id_usuario_registro'] = Auth::id();

        $model = new CompetenciaCargoModel();
        $model->guardarPerfil($idCargo, $data);

        registrarAuditoria('competencia', 'GUARDAR_PERFIL', 'competencia_cargo', $idCargo, null, $data);

        $this->redirectSuccess('/competencia', "Perfil de competencias de {$cargo['cargo']} guardado.");
    }

    /** GET /competencia/capacitaciones — listado general con filtros */
    public function capacitaciones(): void
    {
        $filtros = [
            'tipo'      => Request::get('tipo', ''),
            'resultado' => Request::get('resultado', ''),
        ];
        $this->view('sgc/competencia/capacitaciones', [
            'pageTitle' => 'Capacitaciones del Personal',
            'items'     => (new CapacitacionEmpleadoModel())->listar($filtros),
            'filtros'   => $filtros,
            'resumen'   => (new CapacitacionEmpleadoModel())->resumenPorResultado(),
        ]);
    }

    /** GET /competencia/capacitaciones/crear */
    public function crearCapacitacion(): void
    {
        $this->view('sgc/competencia/capacitacion_form', [
            'pageTitle' => 'Registrar Capacitación',
            'empleados' => (new EmpleadoModel())->activos(),
        ]);
    }

    /** POST /competencia/capacitaciones/crear */
    public function guardarCapacitacion(): void
    {
        Csrf::verify();
        $data = Request::only([
            'id_empleado', 'nombre_curso', 'tipo', 'entidad_capacitadora',
            'fecha_inicio', 'fecha_finalizacion', 'horas', 'resultado', 'observaciones',
        ]);

        if (empty($data['id_empleado']) || empty($data['nombre_curso']) || empty($data['fecha_finalizacion'])) {
            Session::flash('error', 'Empleado, nombre del curso y fecha de finalización son obligatorios.');
            $this->redirect('/competencia/capacitaciones/crear');
            return;
        }

        $data['id_empleado'] = (int) $data['id_empleado'];
        if (empty($data['fecha_inicio'])) $data['fecha_inicio'] = null;
        $data['horas'] = !empty($data['horas']) ? (int) $data['horas'] : null;
        if (empty($data['tipo']) || !in_array($data['tipo'], ['INTERNA','EXTERNA'], true)) {
            $data['tipo'] = 'INTERNA';
        }
        if (empty($data['resultado']) || !in_array($data['resultado'], ['APROBADO','NO_APROBADO','EN_CURSO'], true)) {
            $data['resultado'] = 'APROBADO';
        }
        $data['id_usuario_registro'] = Auth::id();

        // Certificado adjunto (opcional)
        $tieneArchivo = !empty($_POST['certificado_b64'])
                     || (isset($_FILES['certificado']) && ($_FILES['certificado']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE);
        if ($tieneArchivo) {
            try {
                $fileRef = $_FILES['certificado'] ?? [];
                $fileRef['field_name'] = 'certificado';
                $upload = subirArchivo($fileRef, 'capacitaciones', ['application/pdf'], 20971520);
                $data['certificado_archivo'] = $upload['nombre_storage'] ?? null;
            } catch (\Throwable $e) {
                error_log('[CompetenciaController::guardarCapacitacion] certificado: ' . $e->getMessage());
            }
        }

        $idCap = (new CapacitacionEmpleadoModel())->insert($data);
        registrarAuditoria('competencia', 'REGISTRAR_CAPACITACION', 'capacitacion_empleado', $idCap, null, $data);

        $this->redirectSuccess('/competencia/capacitaciones', 'Capacitación registrada correctamente.');
    }
}
