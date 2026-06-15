<?php

namespace App\Services;

/**
 * NotificacionTareaService
 *
 * Centraliza los correos de notificación del flujo de tareas.
 * Antes estaban como HTML inline dentro de TareaController; aquí
 * usan la función enviarCorreo() del helper de notificaciones y
 * la plantilla corporativa _plantillaCorreo().
 *
 * Cada método es idempotente y silencioso ante errores de correo:
 * registra en error_log pero nunca lanza excepción (los fallos de
 * correo no deben interrumpir el flujo de negocio).
 */
class NotificacionTareaService
{
    // ── Helpers internos ─────────────────────────────────────────────────

    /**
     * Construye la tabla de datos de la tarea reutilizada en todos los correos.
     */
    private function tablaTarea(int $idTarea, array $tarea): string
    {
        $idSol  = htmlspecialchars((string)($tarea['id_solicitud']   ?? ''));
        $codigo = htmlspecialchars((string)($tarea['codigo_documento'] ?? '—'));
        return "
            <table style='width:100%;border-collapse:collapse;font-size:14px;
                          border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;'>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;width:140px;
                           border-bottom:1px solid #e5e7eb;'>Tarea #:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>
                  <strong>#{$idTarea}</strong>
                </td>
              </tr>
              <tr>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;
                           border-bottom:1px solid #e5e7eb;'>Solicitud #:</td>
                <td style='padding:10px 16px;border-bottom:1px solid #e5e7eb;'>#{$idSol}</td>
              </tr>
              <tr style='background:#f8fafc;'>
                <td style='padding:10px 16px;color:#6b7280;font-weight:600;'>Documento:</td>
                <td style='padding:10px 16px;'><code>{$codigo}</code></td>
              </tr>
            </table>";
    }

    private function enviar(string $correo, string $nombre, string $asunto, string $cuerpo): void
    {
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        try {
            enviarCorreo([$correo => $nombre], $asunto, $cuerpo);
        } catch (\Throwable $e) {
            error_log("[NotificacionTareaService] correo a {$correo}: " . $e->getMessage());
        }
    }

    // ── Métodos públicos ──────────────────────────────────────────────────

    /**
     * Notifica al REVISOR que hay un documento listo para revisar.
     */
    public function documentoEnviadoARevision(
        int    $idTarea,
        array  $tarea,
        string $correoRevisor,
        string $nombreRevisor,
        string $elaborador
    ): void {
        $asunto = "[SGC] Tarea #{$idTarea} — Documento listo para revisión";
        $cuerpo = "
            <h2 style='color:#1B3A6B;margin-top:0;'>📋 Documento Listo para Revisión</h2>
            <p>Hola <strong>" . htmlspecialchars($nombreRevisor) . "</strong>,</p>
            <p>El documento de la Tarea <strong>#{$idTarea}</strong> fue elaborado
               por <strong>" . htmlspecialchars($elaborador) . "</strong>
               y está listo para su revisión.</p>
            " . $this->tablaTarea($idTarea, $tarea) . "
            <br>
            <a href='" . APP_URL . "/tareas/revisar/{$idTarea}'
               style='background:#1B3A6B;color:#fff;padding:12px 28px;border-radius:6px;
                      text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
               Revisar Tarea #{$idTarea} →
            </a>
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

        $this->enviar($correoRevisor, $nombreRevisor, $asunto, $cuerpo);
    }

    /**
     * Notifica al APROBADOR que hay un documento listo para aprobar.
     */
    public function documentoEnviadoAAprobacion(
        int    $idTarea,
        array  $tarea,
        string $correoAprobador,
        string $nombreAprobador,
        string $revisor
    ): void {
        $asunto = "[SGC] Tarea #{$idTarea} — Documento listo para aprobación";
        $cuerpo = "
            <h2 style='color:#1B3A6B;margin-top:0;'>✅ Documento Revisado — Pendiente Aprobación</h2>
            <p>Hola <strong>" . htmlspecialchars($nombreAprobador) . "</strong>,</p>
            <p>El documento de la Tarea <strong>#{$idTarea}</strong> fue revisado
               por <strong>" . htmlspecialchars($revisor) . "</strong>
               y está listo para su aprobación.</p>
            " . $this->tablaTarea($idTarea, $tarea) . "
            <br>
            <a href='" . APP_URL . "/tareas/aprobar/{$idTarea}'
               style='background:#1B3A6B;color:#fff;padding:12px 28px;border-radius:6px;
                      text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
               Aprobar Tarea #{$idTarea} →
            </a>
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

        $this->enviar($correoAprobador, $nombreAprobador, $asunto, $cuerpo);
    }

    /**
     * Notifica al ELABORADOR que su documento fue devuelto por el revisor.
     */
    public function documentoDevueltoAlElaborador(
        int    $idTarea,
        array  $tarea,
        string $correoElaborador,
        string $nombreElaborador,
        string $revisor,
        string $motivo = ''
    ): void {
        $asunto = "[SGC] Tarea #{$idTarea} — Documento devuelto para corrección";
        $motivoHtml = $motivo
            ? "<div style='background:#fef2f2;border:1px solid #fca5a5;border-radius:6px;
                           padding:12px 16px;margin:12px 0;'>
                 <strong>Motivo:</strong> " . htmlspecialchars($motivo) . "
               </div>"
            : '';
        $cuerpo = "
            <h2 style='color:#dc3545;margin-top:0;'>🔄 Documento Devuelto para Corrección</h2>
            <p>Hola <strong>" . htmlspecialchars($nombreElaborador) . "</strong>,</p>
            <p>El revisor <strong>" . htmlspecialchars($revisor) . "</strong>
               ha devuelto el documento de la Tarea <strong>#{$idTarea}</strong>.</p>
            {$motivoHtml}
            " . $this->tablaTarea($idTarea, $tarea) . "
            <br>
            <a href='" . APP_URL . "/tareas/elaborar/{$idTarea}'
               style='background:#dc3545;color:#fff;padding:12px 28px;border-radius:6px;
                      text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
               Corregir Tarea #{$idTarea} →
            </a>
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

        $this->enviar($correoElaborador, $nombreElaborador, $asunto, $cuerpo);
    }

    /**
     * Confirma al REVISOR que su devolución quedó registrada.
     */
    public function confirmacionDevolucionRevisor(
        int    $idTarea,
        array  $tarea,
        string $correoRevisor,
        string $nombreRevisor,
        string $elaborador
    ): void {
        $asunto = "[SGC] Tarea #{$idTarea} — Devolución registrada";
        $cuerpo = "
            <h2 style='color:#f59e0b;margin-top:0;'>✅ Devolución Registrada</h2>
            <p>Hola <strong>" . htmlspecialchars($nombreRevisor) . "</strong>,</p>
            <p>Su devolución de la Tarea <strong>#{$idTarea}</strong> ha sido registrada.
               Se notificó al elaborador <strong>" . htmlspecialchars($elaborador) . "</strong>.</p>
            " . $this->tablaTarea($idTarea, $tarea) . "
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

        $this->enviar($correoRevisor, $nombreRevisor, $asunto, $cuerpo);
    }

    /**
     * Notifica al REVISOR que el aprobador devolvió la tarea.
     */
    public function documentoDevueltoAlRevisor(
        int    $idTarea,
        array  $tarea,
        string $correoRevisor,
        string $nombreRevisor,
        string $aprobador,
        string $motivo = ''
    ): void {
        $asunto = "[SGC] Tarea #{$idTarea} — Devuelta desde aprobación";
        $motivoHtml = $motivo
            ? "<div style='background:#fff3cd;border:1px solid #ffc107;border-radius:6px;
                           padding:12px 16px;margin:12px 0;'>
                 <strong>Motivo:</strong> " . htmlspecialchars($motivo) . "
               </div>"
            : '';
        $cuerpo = "
            <h2 style='color:#dc3545;margin-top:0;'>🔄 Documento Devuelto desde Aprobación</h2>
            <p>Hola <strong>" . htmlspecialchars($nombreRevisor) . "</strong>,</p>
            <p>El aprobador <strong>" . htmlspecialchars($aprobador) . "</strong>
               ha devuelto la Tarea <strong>#{$idTarea}</strong>.</p>
            {$motivoHtml}
            " . $this->tablaTarea($idTarea, $tarea) . "
            <br>
            <a href='" . APP_URL . "/tareas/revisar/{$idTarea}'
               style='background:#dc3545;color:#fff;padding:12px 28px;border-radius:6px;
                      text-decoration:none;font-size:14px;font-weight:600;display:inline-block;'>
               Ver Tarea #{$idTarea} →
            </a>
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — no responda.</p>";

        $this->enviar($correoRevisor, $nombreRevisor, $asunto, $cuerpo);
    }

    /**
     * Confirma al APROBADOR que su devolución al revisor quedó registrada.
     */
    public function confirmacionDevolucionAprobador(
        int    $idTarea,
        array  $tarea,
        string $correoAprobador,
        string $nombreAprobador,
        string $revisor,
        string $motivo = ''
    ): void {
        $asunto = "[SGC] Tarea #{$idTarea} — Devolución registrada";
        $motivoHtml = $motivo
            ? "<div style='background:#f0fdf4;border:1px solid #86efac;border-radius:6px;
                           padding:12px 16px;margin:12px 0;'>
                 <strong>Motivo:</strong> " . htmlspecialchars($motivo) . "
               </div>"
            : '';
        $cuerpo = "
            <h2 style='color:#f59e0b;margin-top:0;'>✅ Devolución Registrada</h2>
            <p>Hola <strong>" . htmlspecialchars($nombreAprobador) . "</strong>,</p>
            <p>Su devolución de la Tarea <strong>#{$idTarea}</strong> fue registrada.
               El revisor <strong>" . htmlspecialchars($revisor) . "</strong>
               ha sido notificado.</p>
            {$motivoHtml}
            " . $this->tablaTarea($idTarea, $tarea) . "
            <br><br>
            <p style='color:#9ca3af;font-size:12px;'>Mensaje automático — Limaro SGC.</p>";

        $this->enviar($correoAprobador, $nombreAprobador, $asunto, $cuerpo);
    }
}
