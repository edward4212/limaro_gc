<?php
namespace App\Controllers;
use App\Core\{Controller, Csrf, Auth, Request, Session};
use App\Models\{ProveedorModel, ProveedorEvaluacionModel};

class ProveedorController extends Controller
{
    private ProveedorModel $model;

    public function __construct() { $this->model = new ProveedorModel(); }

    /** Campos tipo checkbox: llegan ausentes del POST cuando no se marcan, deben normalizarse a 0/1. */
    private const CAMPOS_BOOLEANOS = [
        'rl_es_asociado_coopeaipe', 'regimen_simplificado', 'regimen_comun',
        'gran_contribuyente', 'exento_iva', 'autorretenedor',
        'realiza_transacciones_moneda_extranjera', 'posee_cuenta_exterior',
        'pep_administra_recursos_publicos', 'pep_ejerce_poder_publico', 'pep_reconocimiento_publico',
        'pep_vinculo_familiar', 'cuenta_sgsst', 'cuenta_sarlaft',
        'anexo_certificado_existencia', 'anexo_rut', 'anexo_cedula', 'anexo_declaracion_renta',
        'anexo_certificado_cuenta_bancaria', 'anexo_portafolio_servicios',
    ];

    private const CAMPOS_TEXTO = [
        'tipo_vinculo', 'tipo_persona', 'razon_social', 'tipo_documento', 'numero_documento',
        'direccion', 'barrio', 'ciudad', 'departamento', 'telefono', 'celular', 'correo',
        'fecha_constitucion', 'camara_comercio', 'actividad_economica', 'ciiu', 'servicio_prestado',
        'rl_nombre', 'rl_tipo_documento', 'rl_numero_documento', 'rl_telefono', 'rl_correo',
        'fecha_corte_financiera', 'total_ingresos_mensuales', 'total_egresos_mensuales',
        'total_activos', 'total_pasivos', 'total_patrimonio',
        'pep_especificar', 'pep_vinculo_familiar_nombre',
        'contacto_nombre', 'contacto_cargo', 'contacto_telefono', 'contacto_correo',
    ];

    /** Normaliza el array recibido del formulario: checkboxes ausentes a 0, fechas/numéricos vacíos a NULL. */
    private function normalizar(array $data): array
    {
        foreach (self::CAMPOS_BOOLEANOS as $campo) {
            $data[$campo] = !empty($data[$campo]) ? 1 : 0;
        }
        foreach (['fecha_constitucion', 'fecha_corte_financiera',
                  'total_ingresos_mensuales', 'total_egresos_mensuales',
                  'total_activos', 'total_pasivos', 'total_patrimonio'] as $campo) {
            if (isset($data[$campo]) && $data[$campo] === '') $data[$campo] = null;
        }
        return $data;
    }

    /** GET /proveedores */
    public function index(): void
    {
        $filtros = [
            'estado'       => Request::get('estado', ''),
            'tipo_vinculo' => Request::get('tipo_vinculo', ''),
            'busqueda'     => Request::get('busqueda', ''),
        ];
        $this->view('sgc/proveedores/index', [
            'pageTitle' => 'Gestión de Proveedores',
            'items'     => $this->model->listar($filtros),
            'filtros'   => $filtros,
            'resumenEstado' => $this->model->resumenPorEstado(),
        ]);
    }

    /** GET /proveedores/crear */
    public function crear(): void
    {
        $this->view('sgc/proveedores/form', [
            'pageTitle' => 'Registrar Proveedor',
            'item'      => null,
            'codigo'    => $this->model->siguienteCodigo(),
        ]);
    }

    /** POST /proveedores/crear */
    public function guardar(): void
    {
        Csrf::verify();
        $data = $this->normalizar(Request::only(array_merge(self::CAMPOS_TEXTO, self::CAMPOS_BOOLEANOS)));

        if (empty($data['razon_social'])) {
            Session::flash('error', 'La razón social o nombre completo es obligatorio.');
            $this->redirect('/proveedores/crear');
            return;
        }

        $data['codigo'] = $this->model->siguienteCodigo();
        $data['id_usuario_registro'] = Auth::id();
        $data['estado'] = 'ACTIVO';

        $id = $this->model->insert($data);
        registrarAuditoria('proveedores', 'CREAR', 'proveedor', $id, null, $data);

        $this->redirectSuccess('/proveedores', "Proveedor {$data['codigo']} registrado correctamente.");
    }

    /** GET /proveedores/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $this->view('sgc/proveedores/form', [
            'pageTitle' => 'Editar Proveedor — ' . $item['codigo'],
            'item'      => $item,
            'codigo'    => $item['codigo'],
        ]);
    }

    /** POST /proveedores/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->detalle($id);
        if (!$antes) $this->abort(404);

        $data = $this->normalizar(Request::only(array_merge(self::CAMPOS_TEXTO, self::CAMPOS_BOOLEANOS)));

        if (empty($data['razon_social'])) {
            Session::flash('error', 'La razón social o nombre completo es obligatorio.');
            $this->redirect("/proveedores/editar/{$id}");
            return;
        }

        $nuevoEstado = Request::post('estado', '');
        if (in_array($nuevoEstado, ['ACTIVO', 'INACTIVO', 'RESTRINGIDO'], true)) {
            $data['estado'] = $nuevoEstado;
        }

        // Verificación interna: solo se actualiza si el usuario marcó la casilla de verificación
        if (Request::post('marcar_verificado', '')) {
            $data['id_usuario_verifico'] = Auth::id();
            $data['fecha_verificacion']  = date('Y-m-d');
            $data['resultado_verificacion'] = Request::post('resultado_verificacion', '');
        }

        $this->model->update($id, $data);
        registrarAuditoria('proveedores', 'EDITAR', 'proveedor', $id, $antes, $data);

        $this->redirectSuccess('/proveedores', 'Proveedor actualizado.');
    }

    /** GET /proveedores/ver/{id} */
    public function ver(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $this->view('sgc/proveedores/ver', [
            'pageTitle'    => 'Proveedor ' . $item['codigo'],
            'item'         => $item,
            'evaluaciones' => $this->model->evaluaciones($id),
        ]);
    }

    /** GET /proveedores/{id}/evaluar */
    public function evaluarForm(int $id): void
    {
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $this->view('sgc/proveedores/evaluar', [
            'pageTitle' => 'Evaluar Proveedor — ' . $item['codigo'],
            'item'      => $item,
        ]);
    }

    /** POST /proveedores/{id}/evaluar */
    public function evaluarGuardar(int $id): void
    {
        Csrf::verify();
        $item = $this->model->detalle($id);
        if (!$item) $this->abort(404);

        $data = Request::only([
            'fecha_evaluacion', 'objeto_contrato',
            'cumplimiento_entrega', 'calidad_especificaciones', 'documentacion_garantias',
            'servicio_postventa', 'precio', 'capacidad_instalada', 'soporte_tecnico',
            'observaciones',
        ]);

        $criterios = ['cumplimiento_entrega','calidad_especificaciones','documentacion_garantias',
                      'servicio_postventa','precio','capacidad_instalada','soporte_tecnico'];
        foreach ($criterios as $c) {
            if ($data[$c] === null || $data[$c] === '' || !is_numeric($data[$c])
                || (float)$data[$c] < 0 || (float)$data[$c] > 5) {
                Session::flash('error', 'Todos los criterios deben calificarse entre 0.0 y 5.0.');
                $this->redirect("/proveedores/{$id}/evaluar");
                return;
            }
        }

        if (empty($data['fecha_evaluacion'])) $data['fecha_evaluacion'] = date('Y-m-d');
        $data['id_proveedor'] = $id;
        $data['id_usuario_evaluador'] = Auth::id();

        $evalModel = new ProveedorEvaluacionModel();
        $idEval = $evalModel->registrar($data);
        $evaluacionGuardada = $evalModel->find($idEval);

        // Si el resultado es NO_CONFIABLE, marcar el proveedor como RESTRINGIDO automáticamente.
        // Se usa el resultado YA calculado y guardado por el modelo (fuente única de verdad),
        // en vez de recalcular el promedio aquí por separado.
        $resultado = $evaluacionGuardada['resultado'] ?? null;
        if ($resultado === 'NO_CONFIABLE' && $item['estado'] !== 'RESTRINGIDO') {
            $this->model->update($id, ['estado' => 'RESTRINGIDO']);
            registrarAuditoria('proveedores', 'RESTRINGIR_AUTOMATICO', 'proveedor', $id,
                ['estado' => $item['estado']], ['estado' => 'RESTRINGIDO', 'motivo' => 'Evaluación NO_CONFIABLE']);
        }

        registrarAuditoria('proveedores', 'EVALUAR', 'proveedor_evaluacion', $idEval, null, $data);

        $this->redirectSuccess("/proveedores/ver/{$id}", 'Evaluación registrada correctamente.');
    }

    /** GET /proveedores/comparativo — reporte PV-FO-5 */
    public function comparativo(): void
    {
        $this->view('sgc/proveedores/comparativo', [
            'pageTitle' => 'Comparación de Proveedores',
            'items'     => (new ProveedorEvaluacionModel())->comparativoUltimasEvaluaciones(),
        ]);
    }
}
