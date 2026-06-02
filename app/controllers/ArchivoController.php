<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\ArchivoModel;
use App\Core\Response;

/**
 * Descarga segura de archivos almacenados.
 */
class ArchivoController extends Controller
{
    /**
     * GET /archivo/{id}
     * Descarga el archivo autenticando al usuario.
     */
    public function descargar(int $id): void
    {
        $model = new ArchivoModel();
        $info  = $model->parDescarga($id);

        if (!$info) {
            $this->abort(404, 'Archivo no encontrado.');
        }

        // Verificar que el archivo existe en disco
        if (!file_exists($info['ruta_absoluta'])) {
            $this->abort(404, 'El archivo físico no existe en el servidor.');
        }

        Response::download($info['ruta_absoluta'], $info['nombre_original']);
    }
}
