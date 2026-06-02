<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\DocumentoModel;
use App\Models\SolicitudModel;
use App\Models\TareaModel;
use App\Models\EmpresaModel;

/**
 * Controlador del dashboard / inicio.
 */
class HomeController extends Controller
{
    /**
     * Dashboard principal (GET /inicio).
     */
    public function index(): void
    {
        $docModel = new DocumentoModel();
        $solModel = new SolicitudModel();
        $tarModel = new TareaModel();
        $empModel = new EmpresaModel();

        // KPIs
        $kpis = [
            'documentos_vigentes' => count($docModel->vigentes()),
            'solicitudes_desarrollo' => count($solModel->porEstado('EN DESARROLLO')),
            'tareas_pendientes' => $tarModel->pendientesUsuario(Auth::empleadoId() ?? 0),
            'solicitudes_total' => $solModel->count(),
        ];

        // Últimas solicitudes
        $ultimas = $solModel->ultimas(5);

        // Datos para gráfico de solicitudes por estado
        $estadosSol = $solModel->conteoEstados();

        $this->view('home/index', [
            'pageTitle'  => 'Inicio',
            'kpis'       => $kpis,
            'ultimas'    => $ultimas,
            'estadosSol' => $estadosSol,
        ]);
    }
}
