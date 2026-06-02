<?php

/**
 * Helper de auditoría — registra acciones en la tabla auditoria.
 */

if (!function_exists('registrarAuditoria')) {
    /**
     * Insertar registro en la tabla de auditoría general.
     *
     * @param string      $modulo   Nombre del módulo (ej: 'documentos')
     * @param string      $accion   Acción realizada (ej: 'CREAR', 'EDITAR', 'ELIMINAR')
     * @param string|null $entidad  Nombre de la entidad (ej: 'documento')
     * @param mixed       $idEntidad ID del registro afectado
     * @param array|null  $antes    Datos antes del cambio
     * @param array|null  $despues  Datos después del cambio
     */
    function registrarAuditoria(
        string  $modulo,
        string  $accion,
        ?string $entidad   = null,
        mixed   $idEntidad = null,
        ?array  $antes     = null,
        ?array  $despues   = null
    ): void {
        try {
            $db = \App\Core\Database::getInstance();

            $stmt = $db->prepare("
                INSERT INTO auditoria
                    (id_usuario, usuario, modulo, accion, entidad, id_entidad,
                     datos_antes, datos_despues, ip, user_agent, fecha)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                \App\Core\Auth::id(),
                \App\Core\Auth::get('usuario'),
                $modulo,
                $accion,
                $entidad,
                (string)($idEntidad ?? ''),
                $antes   ? json_encode($antes,   JSON_UNESCAPED_UNICODE) : null,
                $despues ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
                \App\Core\Request::ip(),
                \App\Core\Request::userAgent(),
            ]);
        } catch (Throwable) {
            // No romper la aplicación por fallo de auditoría
        }
    }
}

if (!function_exists('registrarLoginAudit')) {
    /**
     * Registrar intento de login en auditoria_login.
     *
     * @param int|null $idUsuario  ID del usuario (null si no se encontró)
     * @param string   $usuario    Nombre de usuario intentado
     * @param bool     $exitoso    Si el login fue exitoso
     * @param string   $ip         IP del cliente
     * @param string   $userAgent  User-Agent
     */
    function registrarLoginAudit(
        ?int   $idUsuario,
        string $usuario,
        bool   $exitoso,
        string $ip,
        string $userAgent
    ): void {
        try {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                INSERT INTO auditoria_login
                    (id_usuario, usuario, exitoso, ip, user_agent, fecha)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $idUsuario,
                $usuario,
                $exitoso ? 1 : 0,
                $ip,
                substr($userAgent, 0, 500),
            ]);
        } catch (Throwable) {
            // No romper el login por fallo de auditoría
        }
    }
}
