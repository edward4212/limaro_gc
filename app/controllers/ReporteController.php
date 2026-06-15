<?php
namespace App\Controllers;

use App\Core\{Controller, Auth, Request};
use App\Models\{ReporteModel, ProcesoModel, TipoDocumentoModel,
                AuditoriaPlanModel, AuditoriaProgramaModel, AuditoriaInformeModel, HallazgoModel};

/**
 * Controlador de Reportes — todos los módulos del SGC.
 */
class ReporteController extends Controller
{
    private ReporteModel       $model;
    private ProcesoModel       $procesoModel;
    private TipoDocumentoModel $tipoModel;

    public function __construct()
    {
        $this->model        = new ReporteModel();
        $this->procesoModel = new ProcesoModel();
        $this->tipoModel    = new TipoDocumentoModel();
    }

    /** GET /reportes — Índice central de reportes */
    public function index(): void
    {
        $this->view('reportes/index', [
            'pageTitle' => 'Centro de Reportes',
            'resumen'   => $this->model->resumenEjecutivo(),
        ]);
    }

    /** GET /reportes/documentos/vigentes */
    public function docVigentes(): void
    {
        $f = $this->filtros();
        $this->view('reportes/doc_vigentes', [
            'pageTitle' => 'Reporte: Listado Maestro Vigentes',
            'datos'     => $this->model->listadoMaestroVigentes($f),
            'filtros'   => $f,
            'procesos'  => $this->procesoModel->activos(),
            'tipos'     => $this->tipoModel->activos(),
        ]);
    }

    /** GET /reportes/documentos/obsoletos */
    public function docObsoletos(): void
    {
        $f = $this->filtros();
        $this->view('reportes/doc_obsoletos', [
            'pageTitle' => 'Reporte: Documentos Obsoletos',
            'datos'     => $this->model->listadoMaestroObsoletos($f),
            'filtros'   => $f,
        ]);
    }

    /** GET /reportes/documentos/por-proceso */
    public function docPorProceso(): void
    {
        $this->view('reportes/doc_por_proceso', [
            'pageTitle' => 'Reporte: Documentos por Proceso',
            'datos'     => $this->model->documentosPorProceso(),
        ]);
    }

    /** GET /reportes/documentos/versionamiento */
    public function docVersionamiento(): void
    {
        $f = $this->filtros();
        $this->view('reportes/doc_versionamiento', [
            'pageTitle' => 'Reporte: Historial de Versionamiento',
            'datos'     => $this->model->versionamientoHistorial($f),
            'filtros'   => $f,
            'procesos'  => $this->procesoModel->activos(),
        ]);
    }

    /** GET /reportes/solicitudes */
    public function solicitudes(): void
    {
        $f = $this->filtros();
        // tipo_solicitud es ENUM string, no INT FK — sobrescribir el cast
        $f['tipo'] = Request::get('id_tipo', '');
        $this->view('reportes/solicitudes', [
            'pageTitle' => 'Reporte: Solicitudes',
            'datos'     => $this->model->solicitudesPorEstado($f),
            'resumen'   => $this->model->resumenSolicitudes($f),
            'filtros'   => $f,
        ]);
    }

    /** GET /reportes/tareas */
    public function tareas(): void
    {
        $f = $this->filtros();
        $this->view('reportes/tareas', [
            'pageTitle' => 'Reporte: Tareas',
            'datos'     => $this->model->tareasPorEstado($f),
            'filtros'   => $f,
        ]);
    }

    /** GET /reportes/sgc/objetivos */
    public function sgcObjetivos(): void
    {
        $anio = Request::get('anio', ''); // vacío = todos los períodos
        $this->view('reportes/sgc_objetivos', [
            'pageTitle' => 'Reporte: Cumplimiento Objetivos de Calidad',
            'datos'     => $this->model->cumplimientoObjetivos($anio),
            'filtros'   => ['anio' => $anio],
            'anio'      => $anio,
        ]);
    }

    /** GET /reportes/sgc/hallazgos */
    public function sgcHallazgos(): void
    {
        $f = $this->filtros();
        $f['tipo'] = Request::get('id_tipo', ''); // tipo hallazgo es ENUM string
        $this->view('reportes/sgc_hallazgos', [
            'pageTitle' => 'Reporte: Hallazgos de Auditoría',
            'datos'     => $this->model->hallazgosAuditoria($f),
            'filtros'   => $f,
        ]);
    }

    /** GET /reportes/sgc/acciones-correctivas */
    public function sgcAcciones(): void
    {
        $f = $this->filtros();
        $this->view('reportes/sgc_acciones', [
            'pageTitle' => 'Reporte: Acciones Correctivas',
            'datos'     => $this->model->accionesCorrectivas($f),
            'filtros'   => $f,
        ]);
    }

    /** GET /reportes/sgc/ejecutivo */
    public function sgcEjecutivo(): void
    {
        $this->view('reportes/sgc_ejecutivo', [
            'pageTitle' => 'Resumen Ejecutivo SGC',
            'resumen'   => $this->model->resumenEjecutivo(),
            'objetivos' => $this->model->cumplimientoObjetivos(date('Y')),
            'hallazgos' => $this->model->hallazgosAuditoria(['anio' => date('Y')]),
        ]);
    }

    /** GET /reportes/seguridad/logins */
    public function segLogins(): void
    {
        $f = $this->filtros();
        // El filtro de resultado viene como 'estado' desde _filtros.php
        if (!empty($f['estado'])) $f['resultado'] = $f['estado'];
        $this->view('reportes/seg_logins', [
            'pageTitle' => 'Reporte: Auditoría de Accesos',
            'datos'     => $this->model->auditoriaLogin($f),
            'filtros'   => $f,
        ]);
    }

    /** Helper: leer y sanear filtros comunes de la URL */
    private function filtros(): array
    {
        // Valores ENUM permitidos — previene que un filtro inesperado pase a la query
        static $estadosSolicitud  = ['','CREADA','ASIGNADA','EN_DESARROLLO','FINALIZADA','FINALIZADA_SIN_TRAMITE'];
        static $estadosTarea      = ['','CREADO','REVISION','APROBACION','DEVUELTO','FINALIZADO'];
        static $estadosHallazgo   = ['','ABIERTO','EN_PROCESO','EN_TRATAMIENTO','CERRADO'];
        static $estadosAC         = ['','ABIERTA','EN_PROCESO','CERRADA','VENCIDA'];
        static $tipos             = ['','NO_CONFORMIDAD','OBSERVACION','OPORTUNIDAD','FORTALEZA','CREACION','ACTUALIZACION','ELIMINACION'];
        static $origenes          = ['','AUDITORIA','REVISION_DIRECCION','CLIENTE','PROCESO','OTRO'];
        static $resultados        = ['','EXITOSO','FALLIDO'];

        $estado    = Request::get('estado', '');
        $tipo      = Request::get('tipo', '');
        $origen    = Request::get('origen', '');
        $resultado = Request::get('resultado', '');

        $estadosPermitidos = array_unique(array_merge(
            $estadosSolicitud, $estadosTarea, $estadosHallazgo, $estadosAC
        ));

        return [
            'desde'      => preg_replace('/[^0-9\-]/', '', Request::get('desde', '')),
            'hasta'      => preg_replace('/[^0-9\-]/', '', Request::get('hasta', '')),
            'id_proceso' => (int) Request::get('id_proceso', 0),
            'id_tipo'    => (int) Request::get('id_tipo', 0),
            'anio'       => preg_replace('/[^0-9]/', '', Request::get('anio', '')),
            'usuario'    => preg_replace('/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ _\-]/', '', Request::get('usuario', '')),
            'estado'     => in_array($estado, $estadosPermitidos, true) ? $estado : '',
            'tipo'       => in_array($tipo, $tipos, true) ? $tipo : '',
            'origen'     => in_array($origen, $origenes, true) ? $origen : '',
            'resultado'  => in_array($resultado, $resultados, true) ? $resultado : '',
        ];
    }

    /** GET /reportes/sgc/contexto */
    public function sgcContexto(): void
    {
        $anio   = Request::get('anio', date('Y'));
        $foda   = [];
        $partes = [];

        try {
            $foda = (new \App\Models\FodaModel())->resumenPorTipo();
        } catch (\Throwable $e) { /* módulo Contexto pendiente de activar */ }

        try {
            $partes = (new \App\Models\ParteInteresadaModel())->resumenPorEstado();
        } catch (\Throwable $e) { /* módulo Contexto pendiente de activar */ }

        $this->view('reportes/sgc_contexto', [
            'pageTitle' => 'Reporte: Contexto Organizacional §4.1/4.2',
            'foda'      => $foda,
            'partes'    => $partes,
            'filtros'   => ['anio' => $anio],
            'anio'      => $anio,
        ]);
    }


    /** GET /reportes/seguridad/usuarios */
    public function segUsuarios(): void
    {
        $this->view('reportes/seg_usuarios', [
            'pageTitle' => 'Reporte: Estado de Usuarios',
            'datos'     => $this->model->reporteUsuarios(),
        ]);
    }

    /** GET /reportes/seguridad/contrasenas */
    public function segContrasenas(): void
    {
        $this->view('reportes/seg_contrasenas', [
            'pageTitle' => 'Reporte: Historial de Contraseñas',
            'datos'     => $this->model->reporteContrasenas(),
        ]);
    }


    /** GET /reportes/auditoria/planes */
    public function auditoriaPlan(): void
    {
        $anio = Request::get('anio', date('Y'));
        $planes = (new AuditoriaPlanModel())->listar(['anio'=>$anio]);
        $this->view('reportes/auditoria_planes', [
            'pageTitle' => 'Reporte — Planes de Auditoría',
            'planes'    => $planes,
            'anio'      => $anio,
        ]);
    }

    /** GET /reportes/auditoria/programas */
    public function auditoriaPrograma(): void
    {
        $anio = Request::get('anio', date('Y'));
        $programas = (new AuditoriaProgramaModel())->listar(['anio'=>$anio]);
        $this->view('reportes/auditoria_programas', [
            'pageTitle' => 'Reporte — Programas de Auditoría',
            'programas' => $programas,
            'anio'      => $anio,
        ]);
    }

    /** GET /reportes/auditoria/informes */
    public function auditoriaInforme(): void
    {
        $anio = Request::get('anio', date('Y'));
        $informes = (new AuditoriaInformeModel())->listar(['anio'=>$anio]);
        $this->view('reportes/auditoria_informes', [
            'pageTitle' => 'Reporte — Informes de Auditoría',
            'informes'  => $informes,
            'anio'      => $anio,
        ]);
    }

    /** GET /reportes/auditoria/hallazgos */
    public function auditoriaHallazgos(): void
    {
        $hModel  = new HallazgoModel();
        $filtros = [
            'estado'  => Request::get('estado',''),
            'tipo'    => Request::get('tipo',''),
            'proceso' => Request::get('proceso',''),
        ];
        $this->view('reportes/auditoria_hallazgos', [
            'pageTitle'  => 'Reporte — Hallazgos por Proceso',
            'kpis'       => $hModel->kpis(),
            'hallazgos'  => $hModel->listarFiltrado($filtros),
            'porProceso' => $hModel->porProceso(),
            'filtros'    => $filtros,
        ]);
    }

}