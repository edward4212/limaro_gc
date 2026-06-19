<?php

/**
 * Helper de notificaciones por correo para Limaro SGI.
 *
 * Usa PHPMailer si está disponible (vía Composer), de lo contrario
 * usa mail() nativo de PHP. Configura el servidor SMTP en config.php.
 *
 * Constantes requeridas en config.php:
 *   MAIL_FROM       correo remitente (ej: SGI@coopaipe.com.co)
 *   MAIL_FROM_NAME  nombre remitente (ej: Limaro SGI)
 *   MAIL_SMTP_HOST  servidor SMTP (ej: mail.coopaipe.com.co)
 *   MAIL_SMTP_PORT  puerto (ej: 465 o 587)
 *   MAIL_SMTP_USER  usuario SMTP
 *   MAIL_SMTP_PASS  contraseña SMTP
 *   MAIL_SMTP_ENC   ssl | tls | '' (vacío = sin cifrado)
 */

// ── Configuración de correo ─────────────────────────────────────────
// variables_order puede ser GPCS (sin E), por eso usamos getenv() como
// fuente principal — es la única que funciona sin 'E' en variables_order.
if (!defined('MAIL_FROM')) {
    function _mailEnv(string $key, $default = ''): string {
        // getenv() lee del entorno del proceso — funciona con cualquier variables_order
        $v = getenv($key);
        if ($v !== false && $v !== '') return $v;
        // $_ENV como fallback (funciona si variables_order incluye 'E')
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') return (string)$_ENV[$key];
        return (string)$default;
    }
    define('MAIL_FROM',      _mailEnv('MAIL_FROM',      'noreply@limaro.cloud'));
    define('MAIL_FROM_NAME', _mailEnv('MAIL_FROM_NAME', 'Limaro SGI'));
    define('MAIL_SMTP_HOST', _mailEnv('MAIL_SMTP_HOST', ''));
    define('MAIL_SMTP_PORT', _mailEnv('MAIL_SMTP_PORT', 465));
    define('MAIL_SMTP_USER', _mailEnv('MAIL_SMTP_USER', ''));
    define('MAIL_SMTP_PASS', _mailEnv('MAIL_SMTP_PASS', ''));
    define('MAIL_SMTP_ENC',  _mailEnv('MAIL_SMTP_ENC',  'ssl'));
}

// ── Cargar PHPMailer (DESPUÉS de definir constantes) ─────────────────
// Primero verificar vendor/ (Composer), luego app/libs/ (manual)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $_pmVendor = APP_ROOT . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    $_pmLocal  = APP_ROOT . '/app/libs/phpmailer/PHPMailer.php';
    if (file_exists($_pmVendor)) {
        require_once dirname($_pmVendor) . '/../src/Exception.php';
        require_once dirname($_pmVendor) . '/../src/SMTP.php';
        require_once $_pmVendor;
    } elseif (file_exists($_pmLocal)) {
        require_once APP_ROOT . '/app/libs/phpmailer/Exception.php';
        require_once APP_ROOT . '/app/libs/phpmailer/SMTP.php';
        require_once $_pmLocal;
    }
    unset($_pmVendor, $_pmLocal);
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
        $mail->SMTPSecure = match(strtolower((string)MAIL_SMTP_ENC)) {
            'ssl'  => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS,
            'tls'  => \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS,
            default => ''
        };
        $mail->Port       = (int) MAIL_SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 10; // segundos máximo de espera SMTP
        $mail->SMTPDebug  = defined('APP_DEBUG') && APP_DEBUG ? 2 : 0;
        $mail->Debugoutput = function(string $msg, int $level): void {
            error_log('[SMTP DEBUG] ' . trim($msg));
        };
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->Subject    = '=?UTF-8?B?' . base64_encode($asunto) . '?=';
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
        $headers .= "X-Mailer: Limaro SGI\r\n";

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
        $empresa = empresa()['nombre_empresa'] ?? 'Limaro SGI';
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
          <span style="color:#fff;font-size:22px;font-weight:700;letter-spacing:1px;">Limaro SGI</span>
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
            © {$anio} {$empresa} · Sistema de Gestión Integrado (SGI)<br>
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
// NOTIFICACIONES ESPECÍFICAS DEL SGI
// ─────────────────────────────────────────────────────────────



// [notifSolicitudAsignada] eliminada — reemplazada por NotificacionTareaService



// [notifDocumentoAprobado] eliminada — reemplazada por NotificacionTareaService



// [notifTareaDevuelta] eliminada — reemplazada por NotificacionTareaService


if (!function_exists('notifAcuerdoCreado')) {
    /**
     * HU-009: Notificar a TODOS los usuarios activos que se creó un nuevo acuerdo.
     *
     * @param array  $acuerdo  Datos del acuerdo (nombre, año, numero, fecha_aprobacion, etc.)
     * @param array  $usuarios Lista de ['correo_empleado'=>'...', 'nombre_completo'=>'...']
     * @param int    $idUsuarioOrigen  ID del usuario que creó el acuerdo
     * @return array{enviados:int, fallidos:int}
     */
    function notifAcuerdoCreado(array $acuerdo, array $usuarios, int $idUsuarioOrigen = 0): array
    {
        $enviados = 0;
        $fallidos = 0;

        $nombre      = htmlspecialchars($acuerdo['nombre_acuerdo'] ?? 'Sin nombre');
        $año         = $acuerdo['año_acuerdo']  ?? date('Y');
        $numero      = $acuerdo['numero_acuerdo'] ?? '';
        $fechaApro   = !empty($acuerdo['fecha_aprobacion'])
                       ? date('d/m/Y', strtotime($acuerdo['fecha_aprobacion']))
                       : 'Por definir';
        $acta        = $acuerdo['acta_aprobacion'] ?? '';
        $tipo        = $acuerdo['tipo_documento']  ?? 'Acuerdo';
        $urlSistema  = APP_URL . '/acuerdos/vigentes';

        $html = "
            <h2 style='color:#1e5fbf;margin-top:0;'>📋 Nuevo Acuerdo Registrado</h2>
            <p>Se ha registrado un nuevo acuerdo en el Sistema de Gestión Integrado (SGI):</p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;width:160px;border-bottom:1px solid #e5e7eb;'>Nombre:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><strong>{$nombre}</strong></td>
              </tr>
              <tr>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Número:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$numero} / {$año}</td>
              </tr>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Tipo:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$tipo}</td>
              </tr>
              <tr>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Fecha aprobación:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$fechaApro}</td>
              </tr>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;'>Nº Acta:</td>
                <td style='padding:10px 16px;'>{$acta}</td>
              </tr>
            </table>
            <br>
            <p style='color:#6b7280;font-size:13px;'>
              Puede consultar el acuerdo y descargar el documento adjunto desde el sistema:
            </p>
            <a href='{$urlSistema}'
               style='background:#1e5fbf;color:#fff;padding:12px 28px;border-radius:6px;
                      text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
               Ver Acuerdos Vigentes →
            </a>
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>
              Este mensaje fue generado automáticamente. No responda a este correo.
            </p>";

        $asunto = "Nuevo acuerdo SGI: {$nombre} ({$año})";

        foreach ($usuarios as $u) {
            $correo = trim($u['correo_empleado'] ?? $u['correo'] ?? '');
            $nomDest = $u['nombre_completo'] ?? $u['nombre'] ?? $correo;

            if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $fallidos++;
                continue;
            }

            // Personalizar saludo
            $htmlPersonalizado = str_replace(
                '<h2 ',
                "<p style='color:#374151;'>Hola <strong>" . htmlspecialchars($nomDest) . "</strong>,</p>
<h2 ",
                $html
            );

            $ok = enviarCorreo([$correo => $nomDest], $asunto, $htmlPersonalizado);

            // Registrar en notificacion_log
            _registrarNotificacionLog(
                'ACUERDO_NUEVO',
                $acuerdo['id_acuerdo'] ?? null,
                'acuerdo',
                $correo,
                $nomDest,
                $asunto,
                $ok ? 'ENVIADO' : 'FALLIDO',
                $ok ? null : 'enviarCorreo() retornó false',
                $idUsuarioOrigen
            );

            $ok ? $enviados++ : $fallidos++;
        }

        return ['enviados' => $enviados, 'fallidos' => $fallidos];
    }
}

if (!function_exists('_registrarNotificacionLog')) {
    /**
     * Insertar registro en notificacion_log.
     * No lanza excepción si falla (auditoría no debe interrumpir el flujo).
     */
    function _registrarNotificacionLog(
        string  $evento,
        ?int    $idEntidad,
        ?string $entidad,
        string  $email,
        ?string $nombre,
        string  $asunto,
        string  $estado,
        ?string $errorMsg,
        int     $idUsuarioOrigen = 0
    ): void {
        try {
            $db = \App\Core\Database::getInstance();
            $db->prepare(
                "INSERT INTO notificacion_log
                    (evento, id_entidad, entidad, destinatario_email, destinatario_nombre,
                     asunto, estado, error_msg, id_usuario_origen)
                 VALUES (?,?,?,?,?,?,?,?,?)"
            )->execute([
                $evento, $idEntidad, $entidad, $email,
                $nombre, $asunto, $estado, $errorMsg,
                $idUsuarioOrigen ?: null,
            ]);
        } catch (\Throwable $e) {
            error_log('[notificacion_log] ' . $e->getMessage());
        }
    }
}

if (!function_exists('notifVersionCreada')) {
    /**
     * HU-014: Notificar nueva versión de documento a todos los usuarios activos.
     */
    function notifVersionCreada(array $version, array $usuarios, int $idUsuarioOrigen = 0): array
    {
        $enviados = 0; $fallidos = 0;
        $codigo   = htmlspecialchars($version['codigo']           ?? '');
        $nombre   = htmlspecialchars($version['nombre_documento'] ?? '');
        $nVer     = $version['numero_version'] ?? 1;
        $estado   = $version['estado_version'] ?? 'VIGENTE';
        $desc     = htmlspecialchars($version['descripcion']      ?? '');
        $elab     = htmlspecialchars($version['elaborador']       ?? '');
        $url      = APP_URL . '/versionamiento';
        $asunto   = "Nueva versión SGI: {$codigo} — V{$nVer} ({$estado})";

        $html = "
            <h2 style='color:#1e5fbf;margin-top:0;'>📄 Nueva Versión de Documento</h2>
            <p>Se ha registrado una nueva versión en el Sistema de Gestión Integrado (SGI):</p>
            <table style='width:100%;border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;width:140px;border-bottom:1px solid #e5e7eb;'>Documento:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><strong>{$nombre}</strong></td>
              </tr>
              <tr>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Código:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><code>{$codigo}</code></td>
              </tr>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Versión:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><strong>V{$nVer}</strong> — {$estado}</td>
              </tr>
              <tr>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Cambio:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$desc}</td>
              </tr>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;'>Elaboró:</td>
                <td style='padding:10px 16px;'>{$elab}</td>
              </tr>
            </table>
            <br>
            <a href='{$url}' style='background:#1e5fbf;color:#fff;padding:12px 28px;border-radius:6px;
                text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
               Ver Versionamiento →
            </a>
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático. No responda.</p>";

        foreach ($usuarios as $u) {
            $correo  = trim($u['correo_empleado'] ?? '');
            $nomDest = $u['nombre_completo'] ?? $correo;
            if (empty($correo) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) { $fallidos++; continue; }
            $htmlP = str_replace('<h2 ', "<p style='color:#374151;'>Hola <strong>" . htmlspecialchars($nomDest) . "</strong>,</p>
<h2 ", $html);
            $ok = enviarCorreo([$correo => $nomDest], $asunto, $htmlP);
            _registrarNotificacionLog('VERSION_NUEVA', $version['id_versionamiento'] ?? null,
                'versionamiento', $correo, $nomDest, $asunto,
                $ok ? 'ENVIADO' : 'FALLIDO', $ok ? null : 'error', $idUsuarioOrigen);
            $ok ? $enviados++ : $fallidos++;
        }
        return ['enviados' => $enviados, 'fallidos' => $fallidos];
    }
}

if (!function_exists('notifSolicitudCreada')) {
    /**
     * HU-017: Notificar solicitud nueva.
     * Envía a CADA destinatario incluso si tiene múltiples roles.
     * Cada envío es independiente — un fallo no cancela los demás.
     */
    function notifSolicitudCreada(
        array  $solicitud,
        ?array $correoSolicitante,
        array  $coordinadores,
        array  $lideres,
        int    $idUsuarioOrigen = 0
    ): array {
        $enviados = 0;
        $fallidos = 0;

        $idSol  = $solicitud['id_solicitud'] ?? '—';
        $tipo   = match($solicitud['tipo_solicitud'] ?? '') {
            'CREACION'      => 'Creación de Documento',
            'ACTUALIZACION' => 'Actualización de Documento',
            'ELIMINACION'   => 'Eliminación de Documento',
            default         => $solicitud['tipo_solicitud'] ?? 'Solicitud',
        };
        $codDoc = htmlspecialchars($solicitud['codigo_documento'] ?? 'Sin código');
        $tipDoc = htmlspecialchars($solicitud['tipo_documento']   ?? '');
        $prio   = match($solicitud['prioridad'] ?? '') {
            'URGENTE_IMPORTANTE'    => '🔴 Urgente e Importante',
            'URGENTE_NO_IMPORTANTE' => '🟠 Urgente',
            'NO_URGENTE_IMPORTANTE' => '🟡 Importante',
            default                 => '⚪ Normal',
        };
        $justif = htmlspecialchars($solicitud['solicitud'] ?? '');
        $fecha  = date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'] ?? 'now'));
        $urlVer = APP_URL . '/solicitudes/ver/' . $idSol;
        $asunto = "[SGI] Solicitud #{$idSol} — {$tipo}: {$codDoc}";

        $htmlBase = "
            <h2 style='color:#1e5fbf;margin-top:0;'>📋 Nueva Solicitud Radicada</h2>
            <table style='width:100%;border-collapse:collapse;font-size:14px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <tr style='background:#f8fafc;'><td style='padding:10px 16px;color:#6b7280;font-weight:600;width:180px;border-bottom:1px solid #e5e7eb;'>ID Solicitud:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><strong>#{$idSol}</strong></td></tr>
              <tr><td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Tipo:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$tipo}</td></tr>
              <tr style='background:#f8fafc;'><td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Documento:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'><code>{$codDoc}</code> {$tipDoc}</td></tr>
              <tr><td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Prioridad:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$prio}</td></tr>
              <tr style='background:#f8fafc;'><td style='padding:10px 16px;color:#6b7280;font-weight:600;border-bottom:1px solid #e5e7eb;'>Fecha:</td><td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>{$fecha}</td></tr>
              <tr><td style='padding:10px 16px;color:#6b7280;font-weight:600;'>Justificación:</td><td style='padding:10px 16px;'>{$justif}</td></tr>
            </table><br>
            <a href='{$urlVer}' style='background:#1e5fbf;color:#fff;padding:12px 28px;border-radius:6px;text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>Ver Solicitud #{$idSol} →</a>
            <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

        /**
         * Función interna de envío individual.
         * Retorna true/false y nunca lanza excepción.
         */
        $enviar = function(string $correo, string $nombre, string $rolLabel)
            use ($asunto, $htmlBase, $idSol, $idUsuarioOrigen, &$enviados, &$fallidos): void
        {
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                error_log("[HU-017] Correo inválido: {$correo}");
                $fallidos++;
                return;
            }
            try {
                $saludo = "<p style='color:#374151;'>Hola <strong>" . htmlspecialchars($nombre) .
                          "</strong> <span style='color:#6b7280;font-size:12px;'>({$rolLabel})</span>,</p>";
                $ok = enviarCorreo([$correo => $nombre], $asunto, $saludo . $htmlBase);
                _registrarNotificacionLog('SOLICITUD_NUEVA', (int)$idSol, 'solicitud',
                    $correo, $nombre, $asunto,
                    $ok ? 'ENVIADO' : 'FALLIDO',
                    $ok ? null : 'enviarCorreo() retornó false',
                    $idUsuarioOrigen);
                if ($ok) {
                    $enviados++;
                    error_log("[HU-017] ✓ Enviado a {$correo} ({$rolLabel})");
                } else {
                    $fallidos++;
                    error_log("[HU-017] ✗ Falló envío a {$correo} ({$rolLabel})");
                }
            } catch (\Throwable $e) {
                $fallidos++;
                error_log("[HU-017] Excepción enviando a {$correo}: " . $e->getMessage());
            }
        };

        // ── 1. Solicitante ────────────────────────────────────────────
        if ($correoSolicitante) {
            $c = trim($correoSolicitante['correo_empleado'] ?? '');
            if ($c) $enviar($c, $correoSolicitante['nombre_completo'] ?? $c, 'Solicitante');
        }

        // ── 2. Coordinadores de Calidad ───────────────────────────────
        // Usamos un set de emails YA enviados para no duplicar
        $yaEnviados = [];
        if ($correoSolicitante) {
            $c = trim($correoSolicitante['correo_empleado'] ?? '');
            if ($c) $yaEnviados[strtolower($c)] = true;
        }

        foreach ($coordinadores as $u) {
            $c = trim($u['correo_empleado'] ?? '');
            if (!$c) continue;
            // Si ya recibió como solicitante, enviar de todas formas con label de coordinador
            // solo si es DIFERENTE al solicitante. Si es el mismo, ya recibió.
            if (!isset($yaEnviados[strtolower($c)])) {
                $enviar($c, $u['nombre_completo'] ?? $c, 'Coordinador de Calidad');
                $yaEnviados[strtolower($c)] = true;
            }
        }

        // ── 3. Líderes de Proceso ─────────────────────────────────────
        foreach ($lideres as $u) {
            $c = trim($u['correo_empleado'] ?? '');
            if (!$c) continue;
            if (!isset($yaEnviados[strtolower($c)])) {
                $enviar($c, $u['nombre_completo'] ?? $c, 'Líder de Proceso');
                $yaEnviados[strtolower($c)] = true;
            }
        }

        return ['enviados' => $enviados, 'fallidos' => $fallidos];
    }
}

if (!function_exists('notifCuentaPorVencer')) {
    /**
     * HU-E05 CA-3: Notifica al usuario y a los administradores que una cuenta
     * está próxima a vencer (usado por scripts/cron_vencimiento_usuarios.php
     * a los 30, 15 y 5 días antes del vencimiento).
     *
     * @param array $usuario   Fila con id_usuario, usuario, nombre_completo, correo_empleado, fecha_vencimiento
     * @param array $admins    Lista de administradores [['nombre_completo'=>..,'correo_empleado'=>..], ...]
     * @param int   $diasFaltantes 30, 15 o 5
     */
    function notifCuentaPorVencer(array $usuario, array $admins, int $diasFaltantes): array
    {
        $enviados = 0; $fallidos = 0;
        $nombre   = htmlspecialchars($usuario['nombre_completo'] ?? $usuario['usuario'] ?? '');
        $login    = htmlspecialchars($usuario['usuario'] ?? '');
        $fecha    = !empty($usuario['fecha_vencimiento']) ? fechaEs($usuario['fecha_vencimiento']) : '—';
        $asunto   = "[SGI] Su cuenta vence en {$diasFaltantes} día" . ($diasFaltantes === 1 ? '' : 's');

        $htmlUsuario = "
            <h2 style='color:#b45309;margin-top:0;'>⏰ Su cuenta está próxima a vencer</h2>
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Su cuenta de usuario (<code>{$login}</code>) en Limaro SGI vencerá el
               <strong>{$fecha}</strong> — faltan <strong>{$diasFaltantes} día(s)</strong>.</p>
            <p>Si necesita continuar usando el sistema después de esa fecha, contacte al
               administrador para extender su acceso.</p>
            <br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático. No responda.</p>";

        $correoUsuario = trim($usuario['correo_empleado'] ?? '');
        if (filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {
            $ok = enviarCorreo([$correoUsuario => $nombre], $asunto, $htmlUsuario);
            _registrarNotificacionLog('CUENTA_POR_VENCER', $usuario['id_usuario'] ?? null,
                'usuario', $correoUsuario, $nombre, $asunto,
                $ok ? 'ENVIADO' : 'FALLIDO', $ok ? null : 'error', 0);
            $ok ? $enviados++ : $fallidos++;
        } else {
            $fallidos++;
        }

        $htmlAdmin = "
            <h2 style='color:#b45309;margin-top:0;'>⏰ Cuenta de usuario próxima a vencer</h2>
            <p>La cuenta <strong>{$nombre}</strong> (<code>{$login}</code>) vencerá el
               <strong>{$fecha}</strong> — faltan <strong>{$diasFaltantes} día(s)</strong>.</p>
            <br>
            <a href='" . APP_URL . "/usuarios' style='background:#b45309;color:#fff;padding:10px 24px;
                border-radius:6px;text-decoration:none;font-size:14px;display:inline-block;'>
               Ver Usuarios →
            </a>
            <br><br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático. No responda.</p>";

        foreach ($admins as $a) {
            $c = trim($a['correo_empleado'] ?? '');
            if (!filter_var($c, FILTER_VALIDATE_EMAIL)) { $fallidos++; continue; }
            $nomAdmin = $a['nombre_completo'] ?? $c;
            $ok = enviarCorreo([$c => $nomAdmin], $asunto . ' — ' . $login, $htmlAdmin);
            _registrarNotificacionLog('CUENTA_POR_VENCER_ADMIN', $usuario['id_usuario'] ?? null,
                'usuario', $c, $nomAdmin, $asunto, $ok ? 'ENVIADO' : 'FALLIDO', $ok ? null : 'error', 0);
            $ok ? $enviados++ : $fallidos++;
        }

        return ['enviados' => $enviados, 'fallidos' => $fallidos];
    }
}

if (!function_exists('notifCuentaInactivadaPorVencimiento')) {
    /** HU-E05 CA-4: notifica al usuario que su cuenta fue inactivada por vencimiento. */
    function notifCuentaInactivadaPorVencimiento(array $usuario): array
    {
        $correo = trim($usuario['correo_empleado'] ?? '');
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) return ['enviados' => 0, 'fallidos' => 1];

        $nombre = htmlspecialchars($usuario['nombre_completo'] ?? $usuario['usuario'] ?? '');
        $asunto = '[SGI] Su cuenta ha sido inactivada por vencimiento';
        $html = "
            <h2 style='color:#b91c1c;margin-top:0;'>🔒 Cuenta inactivada</h2>
            <p>Hola <strong>{$nombre}</strong>,</p>
            <p>Su cuenta de usuario en Limaro SGI ha sido inactivada automáticamente por
               vencimiento. Contacte al administrador si necesita reactivar su acceso.</p>
            <br><p style='color:#9ca3af;font-size:12px;'>Mensaje automático. No responda.</p>";

        $ok = enviarCorreo([$correo => $nombre], $asunto, $html);
        _registrarNotificacionLog('CUENTA_INACTIVADA_VENCIMIENTO', $usuario['id_usuario'] ?? null,
            'usuario', $correo, $nombre, $asunto, $ok ? 'ENVIADO' : 'FALLIDO', $ok ? null : 'error', 0);
        return ['enviados' => $ok ? 1 : 0, 'fallidos' => $ok ? 0 : 1];
    }
}
