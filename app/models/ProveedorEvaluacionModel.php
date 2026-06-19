<?php
namespace App\Models;
use App\Core\Model;

class ProveedorEvaluacionModel extends Model
{
    protected string $table      = 'proveedor_evaluacion';
    protected string $primaryKey = 'id';

    /**
     * Registra una evaluación, calculando el promedio (simple, igual que el
     * Excel original PV-FO-4/5) y el resultado cualitativo en PHP — nunca
     * confiando en un valor de promedio enviado por el formulario.
     */
    public function registrar(array $data): int
    {
        $criterios = [
            (float) $data['cumplimiento_entrega'],
            (float) $data['calidad_especificaciones'],
            (float) $data['documentacion_garantias'],
            (float) $data['servicio_postventa'],
            (float) $data['precio'],
            (float) $data['capacidad_instalada'],
            (float) $data['soporte_tecnico'],
        ];
        $promedio = round(array_sum($criterios) / count($criterios), 1);

        $proveedorModel = new ProveedorModel();
        $data['promedio']  = $promedio;
        $data['resultado'] = $proveedorModel->derivarResultado($promedio);

        return $this->insert($data);
    }

    /** Para el reporte de comparación (PV-FO-5): última evaluación de cada proveedor. */
    public function comparativoUltimasEvaluaciones(): array
    {
        return $this->query("
            SELECT p.id AS id_proveedor, p.codigo, p.razon_social, p.numero_documento,
                   pe.fecha_evaluacion, pe.cumplimiento_entrega, pe.calidad_especificaciones,
                   pe.documentacion_garantias, pe.servicio_postventa, pe.precio,
                   pe.capacidad_instalada, pe.soporte_tecnico, pe.promedio, pe.resultado
            FROM proveedor p
            INNER JOIN proveedor_evaluacion pe ON pe.id = (
                SELECT pe2.id FROM proveedor_evaluacion pe2
                WHERE pe2.id_proveedor = p.id
                ORDER BY pe2.fecha_evaluacion DESC, pe2.id DESC LIMIT 1
            )
            ORDER BY pe.promedio DESC
        ")->fetchAll();
    }
}
