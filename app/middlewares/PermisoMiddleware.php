<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Middleware de permisos.
 * Verifica que el rol del usuario tenga acceso al módulo de la ruta actual.
 * 
 * La verificación se hace contra la tabla rol_modulo cargada en sesión.
 * El admin (rol 1) tiene acceso total.
 */
class PermisoMiddleware
{
    /**
     * Mapa de URI prefix → código de módulo
     * Solo rutas que requieren permiso específico.
     */
    private array $mapaPermisos = [
        '/macroprocesos'     => 'macroprocesos',
        '/procesos'          => 'procesos',
        '/tipos-documento'   => 'tipo_documentos',
        '/documentos'        => 'documentos_registrados',
        '/acuerdos'          => 'acuerdos',
        '/versionamiento'    => 'versionamiento',
        '/solicitudes/radicadas'  => 'sol_radicadas',
        '/solicitudes/asignadas'  => 'sol_asignadas',
        '/solicitudes/desarrollo' => 'sol_desarrollo',
        '/solicitudes/finalizadas'=> 'sol_finalizadas',
        '/tareas/aprobar'    => 'tarea_aprobar',
        '/tareas/revisar'    => 'tarea_revisar',
        '/tareas/elaborar'   => 'tarea_elaborar',
        '/usuarios'          => 'usuarios',
        '/cargos'            => 'cargos',
        '/manual-funciones'  => 'manual_funciones',
        '/roles'             => 'roles',
    ];

    public function handle(): void
    {
        // Admin siempre pasa (comprueba primary role Y array de roles)
        $rolesIds = (array)(Auth::get('roles_ids') ?? [Auth::get('id_rol')]);
        if (in_array(1, array_map('intval', $rolesIds), true)) {
            return;
        }

        $uri = Request::uri();
        $base = parse_url(APP_URL, PHP_URL_PATH) ?: '';
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }
        $uri = '/' . trim($uri, '/');

        // Buscar módulo según prefijo más largo
        $modulo  = null;
        $longest = 0;
        foreach ($this->mapaPermisos as $prefix => $codigo) {
            if (str_starts_with($uri, $prefix) && strlen($prefix) > $longest) {
                $modulo  = $codigo;
                $longest = strlen($prefix);
            }
        }

        if ($modulo === null) {
            return; // Ruta sin restricción de módulo
        }

        if (!Auth::puede($modulo, 'ver')) {
            Session::flash('error', 'No tiene permiso para acceder a este módulo.');
            Response::redirect('/inicio');
        }
    }
}
