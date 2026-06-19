<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Models\{ProcesoModel, TipoDocumentoModel};

/**
 * Endpoint genérico para poblar el selector "Valor Buscado" del filtro
 * avanzado con valores reales según la categoría elegida en
 * "Condición de Búsqueda" (proceso, tipo de documento, estado).
 */
class FiltroOpcionesController extends Controller
{
    /** GET /filtro-opciones/{categoria} */
    public function opciones(string $categoria): void
    {
        $valores = match ($categoria) {
            'proceso' => array_map(
                fn($p) => $p['proceso'],
                (new ProcesoModel())->activos()
            ),
            'tipo_documento' => array_map(
                fn($t) => $t['tipo_documento'],
                (new TipoDocumentoModel())->activos()
            ),
            'estado_version' => ['VIGENTE', 'OBSOLETO', 'CREADO'],
            default => [],
        };

        $this->json(['valores' => $valores]);
    }
}
