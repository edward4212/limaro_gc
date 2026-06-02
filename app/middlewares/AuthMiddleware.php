<?php

namespace App\Middlewares;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;

/**
 * Middleware de autenticación.
 * Verifica que el usuario tenga sesión activa.
 * Si no, redirige a /login.
 */
class AuthMiddleware
{
    /**
     * Manejar el middleware.
     */
    public function handle(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Debe iniciar sesión para acceder.');
            Response::redirect('/login');
        }

        // Verificar que el usuario no esté bloqueado
        $user = Auth::user();
        if (($user['estado'] ?? '') !== 'ACTIVO') {
            Auth::logout();
            Session::flash('error', 'Su cuenta está inactiva. Contacte al administrador.');
            Response::redirect('/login');
        }
    }
}
