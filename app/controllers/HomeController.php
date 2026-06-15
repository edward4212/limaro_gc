<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\EmpresaModel;
use App\Models\ObjetivoCalidadModel;

/**
 * Controlador del dashboard / inicio.
 * Muestra información institucional de la empresa y objetivos de calidad.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $empModel = new EmpresaModel();
        $objModel = new ObjetivoCalidadModel();

        $empresa   = $empModel->getDatos() ?? [];
        $objetivos = $objModel->listar();

        $this->view('home/index', [
            'pageTitle' => 'Inicio',
            'empresa'   => $empresa,
            'objetivos' => $objetivos,
        ]);
    }

    /** Módulos en desarrollo — evita 404 en submenús de módulos nuevos */
    public function proximamente(): void
    {
        \App\Core\Session::flash('info',
            '<i class="bi bi-clock me-2"></i>Este módulo está <strong>en desarrollo</strong>. Próximamente disponible.'
        );
        $this->redirect('/inicio');
    }

}