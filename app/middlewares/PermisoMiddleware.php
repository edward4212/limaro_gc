<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Middleware de permisos.
 *
 * Construye el mapa URL→módulo desde la sesión (cargado al login desde
 * la tabla `modulo`). No tiene rutas hardcodeadas: cualquier módulo
 * nuevo en la BD queda automáticamente protegido.
 *
 * Algoritmo:
 *  1. Admin (rol 1) → siempre pasa.
 *  2. Buscar en el mapa global el módulo cuya URL coincida con la
 *     ruta actual (match exacto primero, luego prefijo más largo).
 *  3. Si no hay módulo registrado para esa URL → permitir acceso
 *     (rutas internas como /archivo/123, /api/...).
 *  4. Si hay módulo → verificar que el usuario tenga ver=1 para él.
 */
class PermisoMiddleware
{
    public function handle(): void
    {
        // ── Admin siempre pasa ──────────────────────────────────────
        $rolesIds = (array)(Auth::get('roles_ids') ?? []);
        if (in_array(1, array_map('intval', $rolesIds), true)) {
            return;
        }

        // ── Normalizar URI ──────────────────────────────────────────
        $uri  = Request::uri();
        $base = parse_url(APP_URL, PHP_URL_PATH) ?: '';
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base)) ?: '/';
        }
        $uri = '/' . trim($uri, '/');

        // ── Obtener mapa global URL→código (desde sesión) ───────────
        $urlMap = Auth::get('_url_map', []);

        // Si no está en sesión, cargarlo (primera petición tras login)
        if (empty($urlMap)) {
            $urlMap = $this->cargarUrlMap();
            Auth::set('_url_map', $urlMap);
        }

        // ── Buscar módulo por URL (exacto primero, luego prefijo más largo) ──
        $codigoModulo = null;
        $longitudMax  = 0;

        foreach ($urlMap as $url => $codigo) {
            // Coincidencia exacta → máxima prioridad
            if ($url === $uri) {
                $codigoModulo = $codigo;
                break;
            }
            // Prefijo: /documentos/ cubre /documentos/editar/5
            $prefijo = rtrim($url, '/') . '/';
            if (str_starts_with($uri, $prefijo) && strlen($url) > $longitudMax) {
                $codigoModulo = $codigo;
                $longitudMax  = strlen($url);
            }
        }

        // Ruta no registrada en ningún módulo → permitir
        if ($codigoModulo === null) {
            return;
        }

        // ── Verificar permiso ver (mínimo para cualquier acceso) ─────
        if (!Auth::puede($codigoModulo, 'ver')) {
            Session::flash('error', 'No tiene permiso para acceder a este módulo.');
            Response::redirect('/inicio');
        }

        // ── HU-M08: verificación granular para acciones de escritura ──
        // Las peticiones POST/PUT/DELETE requieren además el permiso
        // correspondiente a la acción detectada en la URI. Las acciones
        // de flujo no mapeadas (asignar, aprobar, comentar...) mantienen
        // su verificación específica dentro de cada controlador.
        $metodo = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($metodo, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $permiso = $this->permisoPorAccion($uri);
            if ($permiso !== null && !Auth::puede($codigoModulo, $permiso)) {
                Session::flash('error', "No tiene permiso de {$permiso} en este módulo.");
                Response::redirect('/inicio');
            }
        }
    }

    /**
     * Mapea el segmento de acción de la URI al permiso requerido.
     * Devuelve null si la acción no corresponde a un permiso granular
     * conocido (en ese caso aplica solo la verificación de `ver`).
     */
    private function permisoPorAccion(string $uri): ?string
    {
        $mapa = [
            'crear'      => 'crear',
            'nuevo'      => 'crear',
            'nueva'      => 'crear',
            'agregar'    => 'crear',
            'editar'     => 'editar',
            'actualizar' => 'editar',
            'eliminar'   => 'eliminar',
            'borrar'     => 'eliminar',
        ];

        foreach (explode('/', trim($uri, '/')) as $segmento) {
            if (isset($mapa[$segmento])) {
                return $mapa[$segmento];
            }
        }
        return null;
    }

    /**
     * Carga desde DB el mapa completo URL → código de módulo.
     * Solo incluye módulos con URL (los contenedores sin URL se omiten).
     * Se ordena de mayor a menor longitud para que el prefix-match
     * siempre use la URL más específica.
     */
    private function cargarUrlMap(): array
    {
        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->query(
                "SELECT url, codigo FROM modulo
                 WHERE url IS NOT NULL AND url != '' AND estado = 'ACTIVO'
                 ORDER BY LENGTH(url) DESC"
            );
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $map  = [];
            foreach ($rows as $r) {
                $map[$r['url']] = $r['codigo'];
            }
            return $map;
        } catch (\Throwable $e) {
            return []; // Si falla, no bloquear
        }
    }
}
