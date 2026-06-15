<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\CargoModel;
use App\Models\ArchivoModel;

/**
 * CRUD de Cargos.
 */
class CargoController extends Controller
{
    private CargoModel   $model;
    private ArchivoModel $archivoModel;

    public function __construct()
    {
        $this->model        = new CargoModel();
        $this->archivoModel = new ArchivoModel();
    }

    public function index(): void
    {
        $this->view('seguridad/cargos/index', [
            'pageTitle' => 'Cargos',
            'cargos'    => $this->model->listar(),
        ]);
    }

    public function crear(): void
    {
        Session::clearOldInput();
        $this->view('seguridad/cargos/form', ['pageTitle' => 'Crear Cargo', 'item' => null]);
    }

    public function guardar(): void
    {
        Csrf::verify();
        $data = Request::only(['cargo', 'descripcion', 'estado']);
        $errors = $this->validate($data, ['cargo' => 'required|max:150']);
        if ($errors) {
            Session::flash('error', 'Corrija los errores.');
            $this->redirect('/cargos/crear');
            return;
        }

        $id = $this->model->crear([
            'cargo'       => strtoupper(trim($data['cargo'])),
            'descripcion' => trim($data['descripcion'] ?? ''),
            'estado'      => $data['estado'] ?? 'ACTIVO',
        ]);

        // CA-3: detectar Base64 (app.js) o $_FILES clásico
        $tieneArchivo = !empty($_POST['manual_funciones_b64'])
                     || (isset($_FILES['manual_funciones']) && ($_FILES['manual_funciones']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE);
        if ($tieneArchivo) {
            try {
                $fileRef = $_FILES['manual_funciones'] ?? [];
                $fileRef['field_name'] = 'manual_funciones';
                $upload = subirArchivo($fileRef, 'cargos/manuales', ['application/pdf'], 20971520);
                $this->archivoModel->registrar('CARGO', $id, $upload, Auth::id());
                $this->model->actualizar($id, ['manual_funciones' => $upload['nombre_storage']]);

                // HU-031: notificar a empleados activos del cargo
                try {
                    $lista = $this->model->empleadosActivosConCorreo($id);

                    if (!empty($lista)) {
                        $cargoNombre = strtoupper(trim($data['cargo'] ?? ''));
                        $fecha       = date('d/m/Y H:i');
                        $asunto      = "[SGC Limaro] Nuevo manual de funciones — {$cargoNombre}";

                        foreach ($lista as $emp) {
                            if (!filter_var($emp['correo_empleado'], FILTER_VALIDATE_EMAIL)) continue;
                            $html = "<h2 style='color:#1e5fbf;margin-top:0;'>
                                        📄 Nuevo Manual de Funciones
                                     </h2>
                                     <p>Hola <strong>" . htmlspecialchars($emp['nombre_completo']) . "</strong>,</p>
                                     <p>Se ha cargado un nuevo <strong>Manual de Funciones</strong>
                                        para el cargo <strong>" . htmlspecialchars($cargoNombre) . "</strong>.</p>
                                     <table style='border-collapse:collapse;font-size:14px;
                                                   border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                                       <tr style='background:#f8fafc;'>
                                         <td style='padding:8px 14px;font-weight:600;'>Cargo:</td>
                                         <td style='padding:8px 14px;'>" . htmlspecialchars($cargoNombre) . "</td>
                                       </tr>
                                       <tr>
                                         <td style='padding:8px 14px;font-weight:600;'>Fecha de actualización:</td>
                                         <td style='padding:8px 14px;'>{$fecha}</td>
                                       </tr>
                                     </table>
                                     <br>
                                     <a href='" . APP_URL . "/seguridad/manual-funciones'
                                        style='background:#1e5fbf;color:#fff;padding:12px 24px;
                                               border-radius:6px;text-decoration:none;font-size:14px;
                                               font-weight:600;display:inline-block;'>
                                        Ver Manual de Funciones →
                                     </a>
                                     <br><br>
                                     <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

                            enviarCorreo(
                                [$emp['correo_empleado'] => $emp['nombre_completo']],
                                $asunto,
                                $html
                            );
                        }
                    }
                } catch (\Throwable $eN) {
                    error_log('[CargoController] notif manual: ' . $eN->getMessage());
                }
            } catch (\RuntimeException $e) {
                error_log('[Limaro SGC] Cargo creado, manual no subido: ' . $e->getMessage());
                Session::flash('warning', 'Cargo creado, manual no subido.');
            }
        }

        $this->redirectSuccess('/cargos', 'Cargo creado exitosamente.');
    }

    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);
        $this->view('seguridad/cargos/form', ['pageTitle' => 'Editar Cargo', 'item' => $item]);
    }

    public function actualizar(int $id): void
    {
        Csrf::verify();
        $data = Request::only(['cargo', 'descripcion', 'estado']);
        $this->model->actualizar($id, [
            'cargo'       => strtoupper(trim($data['cargo'])),
            'descripcion' => trim($data['descripcion'] ?? ''),
            'estado'      => $data['estado'] ?? 'ACTIVO',
        ]);

        $tieneArchivo = !empty($_POST['manual_funciones_b64'])
                     || (isset($_FILES['manual_funciones']) && ($_FILES['manual_funciones']['error'] ?? 4) !== UPLOAD_ERR_NO_FILE);
        if ($tieneArchivo) {
            try {
                $fileRef = $_FILES['manual_funciones'] ?? [];
                $fileRef['field_name'] = 'manual_funciones';
                $upload = subirArchivo($fileRef, 'cargos/manuales', ['application/pdf'], 20971520);
                $this->archivoModel->registrar('CARGO', $id, $upload, Auth::id());
                $this->model->actualizar($id, ['manual_funciones' => $upload['nombre_storage']]);

                // HU-031: notificar a empleados activos del cargo
                try {
                    $lista = $this->model->empleadosActivosConCorreo($id);

                    if (!empty($lista)) {
                        $cargoNombre = strtoupper(trim($data['cargo'] ?? ''));
                        $fecha       = date('d/m/Y H:i');
                        $asunto      = "[SGC Limaro] Nuevo manual de funciones — {$cargoNombre}";

                        foreach ($lista as $emp) {
                            if (!filter_var($emp['correo_empleado'], FILTER_VALIDATE_EMAIL)) continue;
                            $html = "<h2 style='color:#1e5fbf;margin-top:0;'>
                                        📄 Nuevo Manual de Funciones
                                     </h2>
                                     <p>Hola <strong>" . htmlspecialchars($emp['nombre_completo']) . "</strong>,</p>
                                     <p>Se ha cargado un nuevo <strong>Manual de Funciones</strong>
                                        para el cargo <strong>" . htmlspecialchars($cargoNombre) . "</strong>.</p>
                                     <table style='border-collapse:collapse;font-size:14px;
                                                   border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                                       <tr style='background:#f8fafc;'>
                                         <td style='padding:8px 14px;font-weight:600;'>Cargo:</td>
                                         <td style='padding:8px 14px;'>" . htmlspecialchars($cargoNombre) . "</td>
                                       </tr>
                                       <tr>
                                         <td style='padding:8px 14px;font-weight:600;'>Fecha de actualización:</td>
                                         <td style='padding:8px 14px;'>{$fecha}</td>
                                       </tr>
                                     </table>
                                     <br>
                                     <a href='" . APP_URL . "/seguridad/manual-funciones'
                                        style='background:#1e5fbf;color:#fff;padding:12px 24px;
                                               border-radius:6px;text-decoration:none;font-size:14px;
                                               font-weight:600;display:inline-block;'>
                                        Ver Manual de Funciones →
                                     </a>
                                     <br><br>
                                     <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

                            enviarCorreo(
                                [$emp['correo_empleado'] => $emp['nombre_completo']],
                                $asunto,
                                $html
                            );
                        }
                    }
                } catch (\Throwable $eN) {
                    error_log('[CargoController] notif manual: ' . $eN->getMessage());
                }
            } catch (\RuntimeException $e) {
                error_log('[Limaro SGC] Cargo actualizado, manual no reemplazado: ' . $e->getMessage());
                Session::flash('warning', 'Cargo actualizado, manual no reemplazado.');
            }
        }

        $this->redirectSuccess('/cargos', 'Cargo actualizado con notificación a empleados.');
    }

    public function eliminar(int $id): void
    {
        Csrf::verify();

        // CA-2: verificar que no tenga empleados activos
        $activos = $this->model->tieneEmpleadosActivos($id);

        if ($activos > 0) {
            $this->redirectError('/cargos',
                "No se puede inactivar este cargo: tiene <strong>{$activos}</strong> empleado(s) activo(s) asignado(s).");
            return;
        }

        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/cargos', 'Cargo inactivado correctamente.');
    }
}
