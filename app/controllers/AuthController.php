<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\UsuarioModel;

/**
 * Controlador de autenticación.
 * Gestiona login, logout y bloqueo por intentos fallidos.
 */
class AuthController extends Controller
{
    /**
     * Mostrar formulario de login (GET /login).
     */
    public function showLogin(): void
    {
        // Si ya está autenticado, redirigir al inicio
        if (Auth::check()) {
            Response::redirect('/inicio');
        }

        View::render('auth/login', ['pageTitle' => 'Iniciar Sesión'], 'auth');
    }

    /**
     * Procesar login (POST /login).
     */
    public function login(): void
    {
        // Si el token CSRF es inválido (sesión expirada por inactividad),
        // redirigir al login con mensaje amigable en lugar de mostrar error crudo
        if (!Csrf::check()) {
            Session::regenerate();
            Session::flash('warning', 'Su sesión expiró. Por favor ingrese de nuevo.');
            $this->redirect('/login');
        }
        Csrf::verify();

        $usuario = trim((string) Request::post('usuario', ''));
        $clave   = (string) Request::post('clave', '');

        // Validación básica
        if (empty($usuario) || empty($clave)) {
            Session::flash('error', 'Ingrese usuario y clave.');
            Session::setOldInput(['usuario' => $usuario]);
            Response::redirect('/login');
        }

        $model = new UsuarioModel();

        // Verificar si está bloqueado
        if ($model->estaBloqueado($usuario)) {
            Session::flash('error', 'Usuario o clave incorrectos. Intente de nuevo más tarde.');
            Session::setOldInput(['usuario' => $usuario]);
            registrarLoginAudit(null, $usuario, false, Request::ip(), Request::userAgent());
            Response::redirect('/login');
        }

        // Autenticar
        $user = $model->autenticar($usuario, $clave);

        if (!$user) {
            $model->registrarFallo($usuario);
            registrarLoginAudit(null, $usuario, false, Request::ip(), Request::userAgent());
            Session::flash('error', 'Usuario o clave incorrectos.');
            Session::setOldInput(['usuario' => $usuario]);
            Response::redirect('/login');
        }

        // Verificar estado del usuario
        if (($user['estado'] ?? '') !== 'ACTIVO') {
            Session::flash('error', 'Su cuenta está inactiva. Contacte al administrador.');
            Response::redirect('/login');
        }

        // HU-E05: verificación de vencimiento al login (respaldo del cron de inactivación)
        $vencimiento = $user['fecha_vencimiento'] ?? null;
        if ($vencimiento && strtotime($vencimiento) <= time()) {
            $model->inactivarPorVencimiento((int)$user['id_usuario']);
            Session::flash('error', 'Tu cuenta ha expirado. Contacta al administrador.');
            Response::redirect('/login');
        }

        // Login exitoso
        $model->registrarLoginExitoso((int)$user['id_usuario']);
        Auth::login($user);

        // Cargar permisos unificados de todos los roles del usuario
        $permisos = $model->permisosPorUsuario((int)$user['id_usuario']);
        Auth::setModulos($permisos);

                // Cargar mapa global URL→módulo para PermisoMiddleware
        try {
            $urlMap = (new \App\Models\ModuloModel())->mapaUrlCodigo();
            \App\Core\Session::set('_url_map', $urlMap);
        } catch (\Throwable $e) {
            // No bloquear login si falla la carga del mapa
        }

        registrarLoginAudit((int)$user['id_usuario'], $usuario, true, Request::ip(), Request::userAgent());
        Session::clearOldInput();

        // Forzar cambio de clave si fue reseteada o migrada de AES
        if (!empty($user['clave_requiere_reset'])) {
            Session::flash('warning', 'Por seguridad debe cambiar su contraseña antes de continuar.');
            Response::redirect('/perfil/cambiar-clave');
            return;
        }

        Response::redirect('/inicio');
    }

    /**
     * Cerrar sesión (GET /logout).
     */
    public function logout(): void
    {
        $usuario = Auth::get('usuario');
        Auth::logout();
        Session::flash('info', 'Sesión cerrada correctamente.');

        if ($usuario) {
            // No registrar en auditoria_login el logout (es solo login)
        }

        Response::redirect('/login');
    }
}
