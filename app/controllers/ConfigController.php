<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;

/**
 * ConfigController — Configuración del sistema (correo SMTP).
 * BUG-003: controller faltante que causaba error 500 en /configuracion.
 */
class ConfigController extends Controller
{
    public function index(): void
    {
        // Solo ADMINISTRADOR puede ver la configuración
        if (!Auth::tieneRol('ADMINISTRADOR')) {
            Session::flash('error', 'No tiene permiso para acceder a la configuración.');
            $this->redirect('/inicio');
            return;
        }

        $this->view('config/index', [
            'pageTitle'   => 'Configuración del Sistema',
            'smtpHost'    => getenv('MAIL_SMTP_HOST') ?: '',
            'smtpPort'    => getenv('MAIL_SMTP_PORT') ?: 465,
            'smtpUser'    => getenv('MAIL_SMTP_USER') ?: '',
            'smtpEnc'     => getenv('MAIL_SMTP_ENC')  ?: 'ssl',
            'mailFrom'    => getenv('MAIL_FROM')       ?: '',
            'mailFromName'=> getenv('MAIL_FROM_NAME')  ?: '',
        ]);
    }

    public function guardarCorreo(): void
    {
        Csrf::verify();

        if (!Auth::tieneRol('ADMINISTRADOR')) {
            $this->json(['error' => 'Sin permiso'], 403);
        }

        // La configuración SMTP vive en el .env del servidor.
        // Desde el panel no se puede modificar directamente (seguridad),
        // pero sí se puede probar la conexión.
        $to = trim(Request::post('test_email', ''));
        if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            require_once APP_ROOT . '/app/helpers/notificaciones.php';
            $ok = enviarCorreo(
                [$to => Auth::get('nombre_completo') ?? 'Administrador'],
                '[SGC] Prueba de correo ' . date('H:i:s'),
                '<h2 style="color:#1B3A6B;">✅ Correo de prueba</h2>
                 <p>La configuración de correo del SGC está funcionando correctamente.</p>
                 <p><small>Servidor: ' . htmlspecialchars(defined('MAIL_SMTP_HOST') ? MAIL_SMTP_HOST : getenv('MAIL_SMTP_HOST')) . '</small></p>'
            );
            if ($ok) {
                $this->redirectSuccess('/configuracion', "Correo de prueba enviado a {$to}.");
            } else {
                Session::flash('error', "No se pudo enviar el correo a {$to}. Revise la configuración SMTP en el servidor.");
                $this->redirect('/configuracion');
            }
            return;
        }

        Session::flash('error', 'Ingrese un correo válido para la prueba.');
        $this->redirect('/configuracion');
    }
}
