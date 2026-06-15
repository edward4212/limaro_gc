<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Database;

/**
 * Sirve las imágenes institucionales de la empresa (logo, organigrama,
 * mapa de procesos) sin exponer /public/storage/ directamente.
 *
 * - logo: público (lo necesita la pantalla de login).
 * - organigrama / mapa: requieren sesión (información interna).
 */
class EmpresaImgController extends Controller
{
    private const CAMPOS = [
        'logo'        => 'logo',
        'organigrama' => 'organigrama',
        'mapa'        => 'mapa_procesos',
    ];

    public function servir(string $tipo): void
    {
        $campo = self::CAMPOS[$tipo] ?? null;
        if ($campo === null) {
            $this->abort(404, 'Recurso no encontrado.');
        }

        // Organigrama y mapa de procesos son información interna del SGC
        if ($tipo !== 'logo' && !Auth::check()) {
            $this->abort(401, 'Debe autenticarse.');
        }

        // Usar EmpresaModel en lugar de Database::getInstance() directo
        $row = (new \App\Models\EmpresaModel())->primerRegistro($campo);
        $ruta = trim((string) ($row['ruta'] ?? ''));

        if ($ruta === '' || !str_starts_with($ruta, '/storage/')) {
            $this->abort(404, 'Recurso no configurado.');
        }

        $base = realpath(APP_ROOT . '/public/storage');
        $abs  = realpath(APP_ROOT . '/public/' . ltrim(str_replace(['..', "\0"], '', $ruta), '/'));

        if ($base === false || $abs === false || !str_starts_with($abs, $base . DIRECTORY_SEPARATOR)) {
            $this->abort(404, 'El archivo físico no existe.');
        }

        $mime = mime_content_type($abs) ?: 'application/octet-stream';

        // Solo tipos visuales/documentales esperados para estos campos
        $permitidos = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml','application/pdf'];
        if (!in_array($mime, $permitidos, true)) {
            $this->abort(404, 'Tipo de recurso no válido.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($abs) . '"');
        header('Content-Length: ' . filesize($abs));
        // Logo es público y cacheable; organigrama/mapa solo cache privado
        header('Cache-Control: ' . ($tipo === 'logo' ? 'public, max-age=86400' : 'private, max-age=3600'));
        readfile($abs);
        exit;
    }
}
