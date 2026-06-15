<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\UsuarioModel;
use App\Models\RolModel;
use App\Models\CargoModel;

class UsuarioController extends Controller
{
    private UsuarioModel $model;
    private RolModel     $rolModel;
    private CargoModel   $cargoModel;

    public function __construct()
    {
        $this->model      = new UsuarioModel();
        $this->rolModel   = new RolModel();
        $this->cargoModel = new CargoModel();
    }

    /** GET /usuarios */
    public function index(): void
    {
        $this->view('seguridad/usuarios/index', [
            'pageTitle' => 'Gestión de Usuarios',
            'usuarios'  => $this->model->listar(),
        ]);
    }

    /** GET /usuarios/crear */
    public function crear(): void
    {
        Session::clearOldInput();
        // SEC: solo ADMINISTRADOR puede ver y asignar el rol ADMINISTRADOR
        $esAdmin  = \App\Core\Auth::tieneRol('ADMINISTRADOR');
        $todosRoles = $this->rolModel->activos();
        $roles = $esAdmin
            ? $todosRoles
            : array_filter($todosRoles, fn($r) => strtoupper($r['rol']) !== 'ADMINISTRADOR');

        $this->view('seguridad/usuarios/form', [
            'pageTitle' => 'Crear Usuario',
            'item'      => null,
            'roles'     => array_values($roles),
            'cargos'    => $this->cargoModel->activos(),
        ]);
    }


    /** Genera clave aleatoria segura — NUNCA se muestra en pantalla */
    public static function generarClaveAleatoria(int $longitud = 12): string
    {
        $mayus  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $minus  = 'abcdefghjkmnpqrstuvwxyz';
        $nums   = '23456789';
        $simbol = '!@#$%&*';
        $todos  = $mayus . $minus . $nums . $simbol;
        $clave  = $mayus[random_int(0, strlen($mayus)-1)]
                . $minus[random_int(0, strlen($minus)-1)]
                . $nums[random_int(0, strlen($nums)-1)]
                . $simbol[random_int(0, strlen($simbol)-1)];
        for ($i = 4; $i < $longitud; $i++) {
            $clave .= $todos[random_int(0, strlen($todos)-1)];
        }
        return str_shuffle($clave);
    }
    /** POST /usuarios/crear */
    public function guardar(): void
    {
        Csrf::verify();

        $data    = Request::only(['nombre_completo','correo','id_cargo','usuario','clave','clave_confirm']);
        $idRoles = array_filter(array_map('intval', (array)(Request::post('roles') ?? [])));

        // CA-1: si no se envió clave (auto-generada en JS), generar en servidor
        if (empty(trim($data['clave'] ?? ''))) {
            $data['clave'] = self::generarClaveAleatoria();
        }

        $errors = $this->validate($data, [
            'nombre_completo' => 'required|max:200',
            'correo'          => 'required|email',
            'id_cargo'        => 'required|integer',
            'usuario'         => 'required|max:50',
        ]);

        if (empty($idRoles)) {
            $errors['roles'][] = 'Debe asignar al menos un rol.';
        }

        // SEC: filtrar rol ADMINISTRADOR si el operador no es ADMINISTRADOR
        if (!\App\Core\Auth::tieneRol('ADMINISTRADOR')) {
            $rolesAdmin = array_column(
                array_filter($this->rolModel->activos(),
                    fn($r) => strtoupper($r['rol']) === 'ADMINISTRADOR'),
                'id_rol'
            );
            $idRoles = array_values(array_diff($idRoles, $rolesAdmin));
            if (empty($idRoles)) {
                $errors['roles'][] = 'No tiene permiso para asignar ese rol.';
            }
        }

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/usuarios/crear');
            return;
        }

        try {
            // CA-2: siempre CREADO al crear + clave_requiere_reset = 1
            $id = $this->model->crear(
                trim($data['nombre_completo']),
                trim($data['correo']),
                (int)$data['id_cargo'],
                1,
                $idRoles,
                trim($data['usuario']),
                $data['clave'],
                'CREADO'
            );
            // Guardar clave en texto plano temporalmente para enviar por correo
            $claveTexto = $data['clave'];

            // Crear carpeta personal del usuario al momento de su creación
            try {
                carpetaUsuario($id, 'foto');       // /storage/usuarios/{id}/foto/
                carpetaUsuario($id, 'firmas');     // /storage/usuarios/{id}/firmas/
                carpetaUsuario($id, 'documentos'); // /storage/usuarios/{id}/documentos/
            } catch (\Throwable $eFold) {
                error_log('[Limaro SGC] crear carpeta usuario: ' . $eFold->getMessage());
            }

            registrarAuditoria('usuarios', 'CREAR', 'usuario', $id, null, [
                'usuario' => $data['usuario'], 'roles' => $idRoles,
            ]);
            // Enviar correo con clave temporal
            try {
                $empData = (new \App\Models\EmpleadoModel())->find(
                    $this->model->empleadoIdDeUsuario($id)
                );
                $correoDestino = $empData['correo_empleado'] ?? '';
                if ($correoDestino && filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
                    $asunto  = '[SGC Limaro] Credenciales de acceso';
                    $html    = "<h2 style='color:#1e5fbf;'>👤 Bienvenido al SGC Limaro</h2>
                        <p>Hola <strong>" . htmlspecialchars(trim($data['nombre_completo'])) . "</strong>,</p>
                        <p>Se ha creado tu usuario en el sistema. Tus credenciales de acceso son:</p>
                        <table style='border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                          <tr style='background:#f8fafc;'><td style='padding:10px 16px;font-weight:600;'>Usuario:</td><td style='padding:10px 16px;'><code>" . htmlspecialchars(trim($data['usuario'])) . "</code></td></tr>
                          <tr><td style='padding:10px 16px;font-weight:600;'>Contraseña temporal:</td><td style='padding:10px 16px;'><code>" . htmlspecialchars($claveTexto) . "</code></td></tr>
                        </table>
                        <br>
                        <div style='background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:12px 16px;'>
                            ⚠️ <strong>Deberás cambiar tu contraseña</strong> en el primer inicio de sesión.
                        </div>
                        <br>
                        <a href='" . APP_URL . "/login' style='background:#1e5fbf;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>Ingresar al Sistema →</a>
                        <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";
                    enviarCorreo([$correoDestino => trim($data['nombre_completo'])], $asunto, $html);
                }
            } catch (\Throwable $eMail) {
                error_log('[UsuarioController] correo bienvenida: ' . $eMail->getMessage());
            }

            $this->redirectSuccess('/usuarios', 'Usuario creado. Se envió correo con credenciales temporales.');
        } catch (\Throwable $e) {
            error_log('[Limaro SGC] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            Session::flash('error', 'Error interno al procesar la operación. Contacte al administrador.');
            Session::setOldInput($data);
            $this->redirect('/usuarios/crear');
        }
    }

    /** GET /usuarios/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->verConDatos($id);
        if (!$item) $this->abort(404);

        $this->view('seguridad/usuarios/form', [
            'pageTitle'      => 'Editar Usuario #' . $id,
            'item'           => $item,
            'roles'          => $this->rolModel->activos(),
            'cargos'         => $this->cargoModel->activos(),
            'rolesAsignados' => $item['roles_ids'] ?? [],
        ]);
    }

    /** POST /usuarios/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes   = $this->model->verConDatos($id);
        $estado  = Request::post('estado', 'ACTIVO');
        $idRoles = array_filter(array_map('intval', (array)(Request::post('roles') ?? [])));

        $nombreCompleto     = trim(Request::post('nombre_completo', ''));
        $correo             = trim(Request::post('correo', ''));
        $idCargo            = (int) Request::post('id_cargo', 0);
        $telefono           = trim(Request::post('telefono', ''));
        $documentoIdentidad = trim(Request::post('documento_identidad', ''));
        $resetClave         = (int)(bool) Request::post('clave_requiere_reset', 0);

        $errors = [];
        if (empty($nombreCompleto))  $errors[] = 'El nombre completo es requerido.';
        if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL))
                                     $errors[] = 'El correo electrónico no es válido.';
        if (!$idCargo)               $errors[] = 'Debe seleccionar un cargo.';
        if (empty($idRoles))         $errors[] = 'Debe asignar al menos un rol.';

        if ($errors) {
            Session::flash('error', implode(' ', $errors));
            $this->redirect("/usuarios/editar/$id");
            return;
        }

        try {
            $this->model->actualizarCompleto(
                $id, $estado, $idRoles,
                $nombreCompleto, $correo, $idCargo,
                $telefono ?: null, $documentoIdentidad ?: null,
                $resetClave
            );
            registrarAuditoria('usuarios', 'EDITAR', 'usuario', $id, $antes, [
                'estado' => $estado, 'nombre' => $nombreCompleto,
            ]);
            $this->redirectSuccess('/usuarios', 'Usuario actualizado correctamente.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Error al actualizar: ' . $e->getMessage());
            $this->redirect("/usuarios/editar/$id");
        }
    }

    /** POST /usuarios/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        if ($id === Auth::id()) {
            $this->redirectError('/usuarios', 'No puede inactivar su propia cuenta.');
            return;
        }
        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/usuarios', 'Usuario inactivado.');
    }

    /** POST /usuarios/resetear/{id} */
    public function resetearClave(int $id): void
    {
        Csrf::verify();

        // CA-2: clave aleatoria segura — NUNCA se muestra en pantalla
        $nuevaClave = self::generarClaveAleatoria();

        $this->model->cambiarClave($id, $nuevaClave);
        $this->model->update($id, ['clave_requiere_reset' => 1]);
        $this->model->desbloquear($id);

        // CA-1: enviar clave SOLO por correo
        try {
            $userData = $this->model->verConDatos($id);
            $correo   = $userData['correo_empleado'] ?? '';
            $nombre   = $userData['nombre_completo'] ?? 'Usuario';
            $usuario  = $userData['usuario'] ?? '';

            if ($correo && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $asunto = '[SGC Limaro] Restablecimiento de contraseña';
                $html   = "<h2 style='color:#dc3545;margin-top:0;'>🔐 Contraseña Restablecida</h2>
                    <p>Hola <strong>" . htmlspecialchars($nombre) . "</strong>,</p>
                    <p>Un administrador restableció tu contraseña. Tus nuevas credenciales son:</p>
                    <table style='border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
                      <tr style='background:#f8fafc;'>
                        <td style='padding:10px 16px;font-weight:600;'>Usuario:</td>
                        <td style='padding:10px 16px;'><code>" . htmlspecialchars($usuario) . "</code></td>
                      </tr>
                      <tr>
                        <td style='padding:10px 16px;font-weight:600;'>Contraseña temporal:</td>
                        <td style='padding:10px 16px;'><code>" . htmlspecialchars($nuevaClave) . "</code></td>
                      </tr>
                    </table>
                    <br>
                    <div style='background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:12px 16px;'>
                        ⚠️ <strong>Deberás cambiar tu contraseña</strong> al iniciar sesión.
                    </div>
                    <br>
                    <a href='" . APP_URL . "/login'
                       style='background:#1e5fbf;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
                       Ingresar →
                    </a>
                    <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

                enviarCorreo([$correo => $nombre], $asunto, $html);
                $this->redirectSuccess('/usuarios',
                    "Contraseña restablecida. Se envió un correo a <strong>" .
                    htmlspecialchars($correo) . "</strong> con las nuevas credenciales.");
            } else {
                $this->redirectSuccess('/usuarios',
                    'Contraseña restablecida. No se pudo enviar correo (sin dirección válida).');
            }
        } catch (\Throwable $eR) {
            error_log('[resetearClave] correo: ' . $eR->getMessage());
            $this->redirectSuccess('/usuarios', 'Contraseña restablecida. Error al enviar correo.');
        }
    }
}
