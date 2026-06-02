<?php

/**
 * Helper de notificaciones por correo para Limaro SGC.
 *
 * Usa PHPMailer si está disponible (vía Composer), de lo contrario
 * usa mail() nativo de PHP. Configura el servidor SMTP en config.php.
 *
 * Constantes requeridas en config.php:
 *   MAIL_FROM       correo remitente (ej: sgc@coopaipe.com.co)
 *   MAIL_FROM_NAME  nombre remitente (ej: Limaro SGC)
 *   MAIL_SMTP_HOST  servidor SMTP (ej: mail.coopaipe.com.co)
 *   MAIL_SMTP_PORT  puerto (ej: 465 o 587)
 *   MAIL_SMTP_USER  usuario SMTP
 *   MAIL_SMTP_PASS  contraseña SMTP
 *   MAIL_SMTP_ENC   ssl | tls | '' (vacío = sin cifrado)
 */

if (!defined('MAIL_FROM')) {
    define('MAIL_FROM',      $_ENV['MAIL_FROM']      ?? 'noreply@limaro.cloud');
    define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? 'Limaro SGC');
    define('MAIL_SMTP_HOST', $_ENV['MAIL_SMTP_HOST'] ?? '');
    define('MAIL_SMTP_PORT', $_ENV['MAIL_SMTP_PORT'] ?? 465);
    define('MAIL_SMTP_USER', $_ENV['MAIL_SMTP_USER'] ?? '');
    define('MAIL_SMTP_PASS', $_ENV['MAIL_SMTP_PASS'] ?? '');
    define('MAIL_SMTP_ENC',  $_ENV['MAIL_SMTP_ENC']  ?? 'ssl');
}

// ─────────────────────────────────────────────────────────────
// FUNCIÓN PRINCIPAL
// ─────────────────────────────────────────────────────────────

if (!function_exists('enviarCorreo')) {
    /**
     * Enviar correo HTML.
     *
     * @param string|array $para     Destinatario(s): 'correo' o ['correo'=>'nombre',...]
     * @param string       $asunto
     * @param string       $cuerpoHtml
     * @param array        $adjuntos  Rutas absolutas de archivos a adjuntar (opcional)
     * @return bool
     */
    function enviarCorreo(
        string|array $para,
        string $asunto,
        string $cuerpoHtml,
        array $adjuntos = []
    ): bool {
        try {
            // Intentar con PHPMailer si está disponible
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                return _enviarConPHPMailer($para, $asunto, $cuerpoHtml, $adjuntos);
            }
            return _enviarConMailNativo($para, $asunto, $cuerpoHtml);
        } catch (\Throwable $e) {
            error_log('[MAIL ERROR] ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('_enviarConPHPMailer')) {
    function _enviarConPHPMailer(string|array $para, string $asunto, string $html, array $adjuntos): bool
    {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_SMTP_HOST;
        $mail->SMTPAuth   = !empty(MAIL_SMTP_USER);
        $mail->Username   = MAIL_SMTP_USER;
        $mail->Password   = MAIL_SMTP_PASS;
        $mail->SMTPSecure = match(strtolower(MAIL_SMTP_ENC)) {
            'ssl' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
            'tls' => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
            default => ''
        };
        $mail->Port       = (int) MAIL_SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->Subject    = $asunto;
        $mail->isHTML(true);
        $mail->Body       = _plantillaCorreo($asunto, $html);
        $mail->AltBody    = strip_tags($html);

        foreach ((array)$para as $correo => $nombre) {
            if (is_numeric($correo)) {
                $mail->addAddress($nombre);
            } else {
                $mail->addAddress($correo, $nombre);
            }
        }
        foreach ($adjuntos as $adj) {
            if (file_exists($adj)) $mail->addAttachment($adj);
        }

        return $mail->send();
    }
}

if (!function_exists('_enviarConMailNativo')) {
    function _enviarConMailNativo(string|array $para, string $asunto, string $html): bool
    {
        $destinatarios = [];
        foreach ((array)$para as $correo => $nombre) {
            $destinatarios[] = is_numeric($correo) ? $nombre : "$nombre <$correo>";
        }

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
        $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
        $headers .= "X-Mailer: Limaro SGC\r\n";

        $cuerpo = _plantillaCorreo($asunto, $html);

        return mail(implode(',', $destinatarios), $asunto, $cuerpo, $headers);
    }
}

// ─────────────────────────────────────────────────────────────
// PLANTILLA HTML
// ─────────────────────────────────────────────────────────────

if (!function_exists('_plantillaCorreo')) {
    function _plantillaCorreo(string $titulo, string $contenido): string
    {
        $empresa = empresa()['nombre_empresa'] ?? 'Limaro SGC';
        $url     = APP_URL;
        $anio    = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><title>{$titulo}</title></head>
<body style="margin:0;padding:0;background:#f4f6fa;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fa;padding:32px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);">
      <!-- Header -->
      <tr>
        <td style="background:#1e5fbf;padding:24px 32px;text-align:center;">
          <span style="color:#fff;font-size:22px;font-weight:700;letter-spacing:1px;">Limaro SGC</span>
          <br><span style="color:#c9d8f0;font-size:13px;">{$empresa}</span>
        </td>
      </tr>
      <!-- Body -->
      <tr>
        <td style="padding:32px;color:#374151;font-size:15px;line-height:1.6;">
          {$contenido}
        </td>
      </tr>
      <!-- Footer -->
      <tr>
        <td style="background:#f8fafc;padding:16px 32px;text-align:center;border-top:1px solid #e5e7eb;">
          <span style="color:#9ca3af;font-size:12px;">
            © {$anio} {$empresa} · Sistema de Gestión Documental<br>
            <a href="{$url}" style="color:#1e5fbf;">Acceder al sistema</a>
          </span>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
    }
}

// ─────────────────────────────────────────────────────────────
// NOTIFICACIONES ESPECÍFICAS DEL SGC
// ─────────────────────────────────────────────────────────────

if (!function_exists('notifSolicitudCreada')) {
    /**
     * Notificar al responsable del proceso que hay una nueva solicitud.
     */
    function notifSolicitudCreada(array $solicitud, string $correoResponsable): bool
    {
        $tipo    = $solicitud['tipo_solicitud']  ?? 'Nueva';
        $prioridad = $solicitud['prioridad']     ?? '';
        $desc    = $solicitud['solicitud']       ?? '';
        $id      = $solicitud['id_solicitud']    ?? '';
        $html = "
            <h2 style='color:#1e5fbf;margin-top:0;'>📋 Nueva Solicitud Radicada</h2>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
              <tr><td style='padding:6px 0;color:#6b7280;width:140px;'>Tipo:</td>
                  <td><strong>{$tipo}</strong></td></tr>
              <tr><td style='padding:6px 0;color:#6b7280;'>Prioridad:</td>
                  <td><strong style='color:#ef4444;'>{$prioridad}</strong></td></tr>
              <tr><td style='padding:6px 0;color:#6b7280;'>Descripción:</td>
                  <td>" . htmlspecialchars($desc) . "</td></tr>
            </table>
            <br>
            <a href='" . APP_URL . "/solicitudes/ver/{$id}'
               style='background:#1e5fbf;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;'>
               Ver Solicitud
            </a>";

        return enviarCorreo(
            $correoResponsable,
            "Nueva solicitud SGC - $tipo #$id",
            $html
        );
    }
}

if (!function_exists('notifSolicitudAsignada')) {
    /**
     * Notificar al empleado asignado para elaborar.
     */
    function notifSolicitudAsignada(array $solicitud, string $correoAsignado, string $nombreAsignado): bool
    {
        $tipo = $solicitud['tipo_solicitud'] ?? '';
        $id   = $solicitud['id_solicitud']  ?? '';
        $html = "
            <h2 style='color:#1e5fbf;margin-top:0;'>📝 Tarea Asignada</h2>
            <p>Hola <strong>{$nombreAsignado}</strong>,</p>
            <p>Se te ha asignado la elaboración de un documento en el SGC:</p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
              <tr><td style='padding:6px 0;color:#6b7280;width:140px;'>Solicitud #:</td>
                  <td><strong>{$id}</strong></td></tr>
              <tr><td style='padding:6px 0;color:#6b7280;'>Tipo:</td>
                  <td>{$tipo}</td></tr>
            </table>
            <br>
            <a href='" . APP_URL . "/tareas/elaborar/{$id}'
               style='background:#1e5fbf;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;'>
               Ver mi Tarea
            </a>";

        return enviarCorreo($correoAsignado, "Tarea asignada SGC - Solicitud #$id", $html);
    }
}

if (!function_exists('notifDocumentoAprobado')) {
    /**
     * Notificar que un documento fue aprobado y está vigente.
     */
    function notifDocumentoAprobado(array $documento, array $destinatarios): bool
    {
        $codigo  = $documento['codigo']          ?? '';
        $nombre  = $documento['nombre_documento'] ?? '';
        $version = $documento['numero_version']  ?? '?';
        $html = "
            <h2 style='color:#198754;margin-top:0;'>✅ Documento Aprobado y Vigente</h2>
            <p>Un documento del SGC ha sido aprobado exitosamente:</p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
              <tr><td style='padding:6px 0;color:#6b7280;width:140px;'>Código:</td>
                  <td><code style='background:#f3f4f6;padding:2px 6px;border-radius:4px;'>{$codigo}</code></td></tr>
              <tr><td style='padding:6px 0;color:#6b7280;'>Documento:</td>
                  <td><strong>" . htmlspecialchars($nombre) . "</strong></td></tr>
              <tr><td style='padding:6px 0;color:#6b7280;'>Versión:</td>
                  <td><span style='background:#1e5fbf;color:#fff;padding:2px 8px;border-radius:12px;font-size:12px;'>V{$version}</span></td></tr>
            </table>
            <br>
            <a href='" . APP_URL . "/documentos/vigentes'
               style='background:#198754;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;'>
               Ver Listado Maestro
            </a>";

        return enviarCorreo($destinatarios, "Documento aprobado: $codigo V$version", $html);
    }
}

if (!function_exists('notifTareaDevuelta')) {
    /**
     * Notificar al elaborador que su tarea fue devuelta.
     */
    function notifTareaDevuelta(array $tarea, string $correoElaborador, string $comentario = ''): bool
    {
        $id  = $tarea['id_solicitud'] ?? $tarea['id_tarea'] ?? '';
        $html = "
            <h2 style='color:#ef4444;margin-top:0;'>↩️ Tarea Devuelta para Corrección</h2>
            <p>Una de tus tareas ha sido devuelta para corrección:</p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;'>
              <tr><td style='padding:6px 0;color:#6b7280;width:140px;'>Tarea #:</td>
                  <td><strong>{$id}</strong></td></tr>
              " . ($comentario ? "<tr><td style='padding:6px 0;color:#6b7280;'>Observación:</td>
                  <td>" . htmlspecialchars($comentario) . "</td></tr>" : "") . "
            </table>
            <br>
            <a href='" . APP_URL . "/tareas/elaborar/{$id}'
               style='background:#ef4444;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-size:14px;'>
               Revisar y Corregir
            </a>";

        return enviarCorreo($correoElaborador, "Tarea devuelta SGC - #$id", $html);
    }
}
