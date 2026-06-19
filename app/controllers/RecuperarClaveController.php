<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\UsuarioModel;
use App\Models\RolModel;

class RecuperarClaveController extends Controller
{
    /** GET /recuperar-clave */
    public function show(): void
    {
        $this->view('auth/recuperar_clave', [], 'auth');
    }

    /** POST /recuperar-clave */
    public function enviar(): void
    {
        Csrf::verify();

        $identificador = trim(Request::post('identificador', ''));

        if (empty($identificador)) {
            Session::flash('error', 'Ingresa tu usuario o correo.');
            $this->redirect('/recuperar-clave');
        }

        $model = new UsuarioModel();

        $usuario = $model->buscarParaRecuperar($identificador);

        // Siempre mostrar mensaje genérico (no revelar si existe el usuario)
        $msgExito = 'Solicitud enviada. Si el usuario existe, el administrador recibirá la notificación y se te informará por correo.';

        if (!$usuario) {
            Session::flash('success', $msgExito);
            $this->redirect('/recuperar-clave');
        }

        if ($usuario['estado'] === 'INACTIVO') {
            Session::flash('success', $msgExito);
            $this->redirect('/recuperar-clave');
        }

        $destinatariosAdmin = $model->adminYCoordinadores();

        $emp         = empresa();
        $empNombre   = $emp['nombre_empresa'] ?? 'SGC';
        $nombreSolic = $usuario['nombre_completo'] ?? $usuario['usuario'];
        $correoSolic = $usuario['correo'] ?? null;
        $ip          = Request::ip();
        $fecha       = date('d/m/Y H:i');

        // ── Correo a Administradores / Coordinadores ──────────────
        $asuntoAdmin = "[{$empNombre}] Solicitud de cambio de contraseña";
        $cuerpoAdmin = "
            <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
                <div style='background:#0f1e38;padding:20px 30px;border-radius:10px 10px 0 0;'>
                    <h2 style='color:#fff;margin:0;font-size:18px;'>🔑 Solicitud de Cambio de Contraseña</h2>
                    <p style='color:#00B5D8;margin:4px 0 0;font-size:13px;'>{$empNombre} — Sistema de Gestión de Calidad</p>
                </div>
                <div style='background:#f8f9fa;padding:24px 30px;border:1px solid #dee2e6;'>
                    <p style='font-size:14px;color:#333;'>Un usuario ha solicitado el restablecimiento de su contraseña:</p>
                    <table style='width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;'>
                        <tr><td style='padding:8px 12px;background:#e9ecef;font-weight:600;width:40%;'>Usuario</td>
                            <td style='padding:8px 12px;border-bottom:1px solid #dee2e6;'><code>{$usuario['usuario']}</code></td></tr>
                        <tr><td style='padding:8px 12px;background:#e9ecef;font-weight:600;'>Nombre</td>
                            <td style='padding:8px 12px;border-bottom:1px solid #dee2e6;'>{$nombreSolic}</td></tr>
                        <tr><td style='padding:8px 12px;background:#e9ecef;font-weight:600;'>Correo</td>
                            <td style='padding:8px 12px;border-bottom:1px solid #dee2e6;'>{$correoSolic}</td></tr>
                        <tr><td style='padding:8px 12px;background:#e9ecef;font-weight:600;'>Fecha</td>
                            <td style='padding:8px 12px;border-bottom:1px solid #dee2e6;'>{$fecha}</td></tr>
                        <tr><td style='padding:8px 12px;background:#e9ecef;font-weight:600;'>IP</td>
                            <td style='padding:8px 12px;'>{$ip}</td></tr>
                    </table>
                    <p style='font-size:13px;color:#555;'>Por favor, restablezca la contraseña del usuario desde el panel de administración y notifíquele el acceso.</p>
                    <a href='" . APP_URL . "/usuarios'
                       style='display:inline-block;background:#1B3A6B;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;font-size:13px;margin-top:8px;'>
                        Ir a Gestión de Usuarios
                    </a>
                </div>
                <div style='background:#e9ecef;padding:12px 30px;border-radius:0 0 10px 10px;font-size:11px;color:#888;text-align:center;'>
                    {$empNombre} &mdash; Limaro SGC
                </div>
            </div>";

        foreach ($destinatariosAdmin as $dest) {
            if (empty($dest['correo'])) continue;
            try {
                enviarCorreo([$dest['correo'] => $dest['nombre_completo']], $asuntoAdmin, $cuerpoAdmin);
            } catch (\Throwable $e) {
                error_log("[RecuperarClave] correo admin {$dest['correo']}: " . $e->getMessage());
            }
        }

        // ── Correo al usuario solicitante ─────────────────────────
        if (!empty($correoSolic)) {
            $asuntoUser = "[{$empNombre}] Solicitud de cambio de contraseña recibida";
            $cuerpoUser = "
                <div style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
                    <div style='background:#0f1e38;padding:20px 30px;border-radius:10px 10px 0 0;'>
                        <h2 style='color:#fff;margin:0;font-size:18px;'>🔑 Solicitud Recibida</h2>
                        <p style='color:#00B5D8;margin:4px 0 0;font-size:13px;'>{$empNombre} — Sistema de Gestión de Calidad</p>
                    </div>
                    <div style='background:#f8f9fa;padding:24px 30px;border:1px solid #dee2e6;'>
                        <p style='font-size:14px;color:#333;'>Hola <strong>{$nombreSolic}</strong>,</p>
                        <p style='font-size:14px;color:#333;'>Recibimos tu solicitud de cambio de contraseña el <strong>{$fecha}</strong>.</p>
                        <p style='font-size:14px;color:#333;'>El administrador del sistema fue notificado y pronto recibirás tus nuevas credenciales de acceso.</p>
                        <p style='font-size:13px;color:#888;margin-top:16px;'>Si no solicitaste este cambio, ignora este mensaje o contacta al administrador.</p>
                    </div>
                    <div style='background:#e9ecef;padding:12px 30px;border-radius:0 0 10px 10px;font-size:11px;color:#888;text-align:center;'>
                        {$empNombre} &mdash; Limaro SGC
                    </div>
                </div>";

            try {
                enviarCorreo([$correoSolic => $nombreSolic], $asuntoUser, $cuerpoUser);
            } catch (\Throwable $e) {
                error_log("[RecuperarClave] correo usuario: " . $e->getMessage());
            }
        }

        Session::flash('success', $msgExito);
        $this->redirect('/recuperar-clave');
    }
}
