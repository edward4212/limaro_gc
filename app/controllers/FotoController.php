<?php
namespace App\Controllers;
use App\Core\Controller;
use App\Core\Auth;

/**
 * Sirve imágenes de perfil evitando el 403 de Apache en /storage/.
 * BUG-013: requiere sesión activa — usuarios anónimos reciben el avatar genérico.
 * No se devuelve 401 porque el tag <img> en el navbar silenciaría el error;
 * el avatar SVG es la degradación correcta para requests no autenticados.
 */
class FotoController extends Controller
{
    public function perfil(int $idUsuario): void
    {
        // Sin sesión → avatar genérico (no 401, para no romper <img> tags)
        if (!Auth::check()) {
            $this->servirAvatarGenerico();
            return;
        }
        $exts = ['jpg','jpeg','png','webp'];
        $base = APP_ROOT . '/public/storage/usuarios/' . $idUsuario . '/foto/foto.';

        foreach ($exts as $ext) {
            $path = $base . $ext;
            if (file_exists($path) && is_readable($path)) {
                $mime = match($ext) {
                    'jpg','jpeg' => 'image/jpeg',
                    'png'         => 'image/png',
                    'webp'        => 'image/webp',
                    default       => 'image/jpeg',
                };
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=86400');
                header('Content-Length: ' . filesize($path));
                readfile($path);
                exit;
            }
        }

        $this->servirAvatarGenerico();
    }

    private function servirAvatarGenerico(): never
    {
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=3600');
        echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
           . '<circle cx="50" cy="50" r="50" fill="#e2e8f0"/>'
           . '<circle cx="50" cy="38" r="18" fill="#94a3b8"/>'
           . '<ellipse cx="50" cy="85" rx="28" ry="20" fill="#94a3b8"/>'
           . '</svg>';
        exit;
    }
}
