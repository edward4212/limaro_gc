<?php

/**
 * Helpers de formateo — fechas en español, moneda COP, textos.
 */

if (!function_exists('fechaEs')) {
    /**
     * Formatear fecha en español Colombia.
     * 
     * @param string|null $fecha  Fecha en formato Y-m-d o datetime
     * @param string      $formato 'corto' | 'largo' | 'hora'
     */
    function fechaEs(?string $fecha, string $formato = 'corto'): string
    {
        if (!$fecha) {
            return '—';
        }

        try {
            $dt = new DateTimeImmutable($fecha, new DateTimeZone('America/Bogota'));
        } catch (Exception) {
            return htmlspecialchars($fecha);
        }

        $meses = [
            1  => 'enero', 2  => 'febrero', 3  => 'marzo',
            4  => 'abril', 5  => 'mayo',    6  => 'junio',
            7  => 'julio', 8  => 'agosto',  9  => 'septiembre',
            10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        $dia  = $dt->format('j');
        $mes  = $meses[(int)$dt->format('n')];
        $año  = $dt->format('Y');
        $hora = $dt->format('H:i');

        return match ($formato) {
            'largo' => "$dia de $mes de $año",
            'hora'  => "$dia/$mes/$año $hora",
            default => $dt->format('d/m/Y'),
        };
    }
}

if (!function_exists('monedaCop')) {
    /**
     * Formatear número como moneda COP.
     */
    function monedaCop(mixed $valor): string
    {
        return '$ ' . number_format((float)$valor, 0, ',', '.');
    }
}

if (!function_exists('e')) {
    /**
     * Escapar HTML de forma segura (alias htmlspecialchars).
     */
    function e(?string $str): string
    {
        return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('old')) {
    /**
     * Recuperar valor antiguo de formulario (tras error de validación).
     */
    function old(string $key, mixed $default = ''): string
    {
        return e(\App\Core\Session::getOldInput($key, $default));
    }
}

if (!function_exists('badgeEstado')) {
    /**
     * Badge Bootstrap según estado de solicitud/tarea.
     */
    function badgeEstado(string $estado): string
    {
        $map = [
            'CREADA'       => 'secondary',
            'ASIGNADA'     => 'info',
            'EN DESARROLLO'=> 'primary',
            'FINALIZADA'   => 'success',
            'CREADO'       => 'secondary',
            'REVISION'     => 'info',
            'APROBACION'   => 'warning',
            'DEVUELTO'     => 'danger',
            'FINALIZADO'   => 'success',
            'CAMBIO'       => 'warning',
            'ACTIVO'       => 'success',
            'INACTIVO'     => 'secondary',
            'VIGENTE'       => 'success',
            'OBSOLETO'      => 'danger',
        ];
        $color = $map[strtoupper($estado)] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . e($estado) . '</span>';
    }
}

if (!function_exists('asset')) {
    /**
     * URL de un asset público.
     */
    function asset(string $path): string
    {
        return APP_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * URL absoluta.
     */
    function url(string $path = ''): string
    {
        return APP_URL . '/' . ltrim($path, '/');
    }
}

if (!function_exists('csrfField')) {
    /**
     * Campo CSRF en formularios.
     */
    function csrfField(): string
    {
        return \App\Core\Csrf::field();
    }
}

if (!function_exists('truncar')) {
    /**
     * Truncar texto a N caracteres.
     */
    function truncar(string $texto, int $max = 80): string
    {
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }
        return mb_substr($texto, 0, $max) . '…';
    }
}

if (!function_exists('empresa')) {
    /**
     * Obtener datos de la empresa principal (id=1) desde la tabla `empresa`.
     * Resultado cacheado en memoria durante el request.
     *
     * Uso en vistas:
     *   $emp = empresa();
     *   echo e($emp['nombre_empresa']);
     *   <img src="<?= e(empresaLogoUrl()) ?>">
     *
     * @param string|null $campo Si se pasa, devuelve solo ese campo.
     */
    function empresa(?string $campo = null): mixed
    {
        static $datos = null;
        if ($datos === null) {
            try {
                $model = new \App\Models\EmpresaModel();
                $datos = $model->getDatos() ?: [];
            } catch (\Throwable $e) {
                $datos = [];
            }
            // Defaults de respaldo si la tabla está vacía
            $datos += [
                'id_empresa'         => 1,
                'nombre_empresa'     => 'Limaro SGC',
                'logo'               => '',
                'mision'             => '',
                'vision'             => '',
                'politica_calidad'   => '',
                'objetivos_calidad'  => '',
                'URL'                => '',
            ];
        }
        if ($campo !== null) {
            return $datos[$campo] ?? '';
        }
        return $datos;
    }
}

if (!function_exists('empresaLogoUrl')) {
    /**
     * URL del logo de la empresa. Si el campo `logo` está vacío, devuelve
     * null para que la vista use su fallback (SVG).
     *
     * - Si `logo` es una URL absoluta (http/https), se devuelve tal cual.
     * - Si es una ruta relativa, se resuelve contra APP_URL/assets/img/.
     */
    function empresaLogoUrl(): ?string
    {
        $logo = trim((string) empresa('logo'));
        if ($logo === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $logo)) {
            return $logo;
        }
        // Si ya viene con barra inicial, respetar ruta relativa al dominio
        if (str_starts_with($logo, '/')) {
            return APP_URL . $logo;
        }
        // Si ya incluye 'assets/' o 'storage/', no duplicar
        if (str_starts_with($logo, 'assets/') || str_starts_with($logo, 'storage/')) {
            return APP_URL . '/' . $logo;
        }
        return APP_URL . '/assets/img/' . $logo;
    }
}

if (!function_exists('prioridadLabel')) {
    /**
     * Etiqueta de prioridad (matriz Eisenhower).
     */
    function prioridadLabel(string $prioridad): string
    {
        $map = [
            'URGENTE_IMPORTANTE'    => ['danger',   'Urgente e Importante'],
            'URGENTE_NO_IMPORTANTE' => ['warning',  'Urgente, No Importante'],
            'NO_URGENTE_IMPORTANTE' => ['primary',  'No Urgente, Importante'],
            'NO_URGENTE_NO_IMPORTANTE' => ['secondary', 'No Urgente, No Importante'],
        ];
        [$color, $label] = $map[strtoupper($prioridad)] ?? ['secondary', $prioridad];
        return '<span class="badge bg-' . $color . '">' . $label . '</span>';
    }
}
