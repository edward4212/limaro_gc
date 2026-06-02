<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CargoModel;

/**
 * Listado consolidado de manuales de funciones.
 */
class ManualFuncionesController extends Controller
{
    public function index(): void
    {
        $model = new CargoModel();
        $this->view('seguridad/manual_funciones', [
            'pageTitle' => 'Manual de Funciones',
            'cargos'    => $model->listar(),
        ]);
    }
}
