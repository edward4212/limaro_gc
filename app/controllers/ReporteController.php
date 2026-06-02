<?php
namespace App\Controllers;

use App\Core\{Controller, Auth, Request};
use App\Models\{ReporteModel, ProcesoModel, TipoDocumentoModel};

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
        $anio = Request::get('anio', date('Y'));
        $this->view('reportes/sgc_objetivos', [
            'pageTitle' => 'Reporte: Cumplimiento Objetivos de Calidad',
            'datos'     => $this->model->cumplimientoObjetivos($anio),
            'anio'      => $anio,
        ]);
    }

    /** GET /reportes/sgc/hallazgos */
    public function sgcHallazgos(): void
    {
        $f = $this->filtros();
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
        $this->view('reportes/seg_logins', [
            'pageTitle' => 'Reporte: Auditoría de Accesos',
            'datos'     => $this->model->auditoriaLogin($f),
            'filtros'   => $f,
        ]);
    }

    /** Helper: leer filtros comunes de la URL */
    private function filtros(): array
    {
        return [
            'desde'      => Request::get('desde', ''),
            'hasta'      => Request::get('hasta', ''),
            'id_proceso' => (int) Request::get('id_proceso', 0),
            'id_tipo'    => (int) Request::get('id_tipo', 0),
            'estado'     => Request::get('estado', ''),
            'tipo'       => Request::get('tipo', ''),
            'origen'     => Request::get('origen', ''),
            'anio'       => Request::get('anio', ''),
            'resultado'  => Request::get('resultado', ''),
            'usuario'    => Request::get('usuario', ''),
        ];
    }
}
