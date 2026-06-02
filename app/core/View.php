<?php

namespace App\Core;

/**
 * Renderizador de vistas PHP.
 * Soporta layouts y paso de datos a las vistas.
 */
class View
{
    /** @var array Datos globales disponibles en todas las vistas */
    private static array $shared = [];

    /**
     * Compartir datos globales con todas las vistas.
     */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Renderizar una vista dentro del layout principal.
     *
     * @param string $view   Ruta relativa dentro de app/views/ (ej. 'home/index')
     * @param array  $data   Datos locales de la vista
     * @param string $layout Layout a usar (sin extensión, relativo a views/layouts/)
     */
    public static function render(
        string $view,
        array  $data   = [],
        string $layout = 'app'
    ): void {
        $data    = array_merge(self::$shared, $data);
        $viewFile = APP_ROOT . '/app/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            Response::abort(500, "Vista no encontrada: $view");
        }

        // Renderizar contenido de la vista
        $content = self::capture($viewFile, $data);

        // Renderizar layout
        $layoutFile = APP_ROOT . '/app/views/layouts/' . $layout . '.php';
        if (file_exists($layoutFile)) {
            extract(array_merge($data, ['content' => $content]), EXTR_SKIP);
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Renderizar vista sin layout.
     */
    public static function renderPartial(string $view, array $data = []): void
    {
        $data     = array_merge(self::$shared, $data);
        $viewFile = APP_ROOT . '/app/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            Response::abort(500, "Vista no encontrada: $view");
        }
        extract($data, EXTR_SKIP);
        require $viewFile;
    }

    /**
     * Capturar salida de una vista como string.
     */
    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean() ?: '';
    }

    /**
     * Incluir un partial desde cualquier vista.
     */
    public static function partial(string $name, array $data = []): void
    {
        $data     = array_merge(self::$shared, $data);
        $partFile = APP_ROOT . '/app/views/partials/' . $name . '.php';
        if (file_exists($partFile)) {
            extract($data, EXTR_SKIP);
            require $partFile;
        }
    }
}
