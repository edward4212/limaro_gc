<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\UsuarioModel;
use App\Models\EmpleadoModel;

/**
 * Controlador de Perfil de usuario.
 */
class PerfilController extends Controller
{
    private UsuarioModel  $userModel;
    private EmpleadoModel $empModel;

    public function __construct()
    {
        $this->userModel = new UsuarioModel();
        $this->empModel  = new EmpleadoModel();
    }

    /** GET /perfil */
    public function index(): void
    {
        $user = $this->userModel->verConDatos(Auth::id() ?? 0);
        if (!$user) $this->abort(404);

        $this->view('perfil/index', [
            'pageTitle' => 'Mi Perfil',
            'user'      => $user,
        ]);
    }

    /** POST /perfil — actualizar imagen */
    public function actualizar(): void
    {
        Csrf::verify();

        if (!Request::hasFile('img_empleado') || $_FILES['img_empleado']['error'] !== UPLOAD_ERR_OK) {
            $this->redirectSuccess('/perfil', 'Sin cambios guardados.');
            return;
        }

        $idUsuario  = (int)(Auth::id() ?? 0);
        $idEmpleado = (int)(Auth::empleadoId() ?? 0);

        if (!$idUsuario) {
            $this->redirectError('/perfil', 'Usuario no identificado.');
            return;
        }

        $archivo = $_FILES['img_empleado'];
        $mime    = mime_content_type($archivo['tmp_name']);
        $mimes   = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mime, $mimes, true)) {
            $this->redirectError('/perfil', 'Solo se permiten imágenes JPG, PNG o WEBP.');
            return;
        }

        if ($archivo['size'] > 5242880) {
            $this->redirectError('/perfil', 'La imagen no puede superar 5 MB.');
            return;
        }

        try {
            // Crear carpeta personal del usuario si no existe
            $carpeta = carpetaUsuario($idUsuario, 'foto');

            // Determinar extensión según MIME real
            $ext = match($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                default      => 'jpg',
            };

            // Ruta destino: siempre foto.{ext} (reemplaza la anterior)
            $rutaAbsoluta = $carpeta . '/foto.' . $ext;

            // Eliminar fotos anteriores de cualquier extensión
            foreach (['jpg','jpeg','png','webp'] as $e) {
                $vieja = $carpeta . '/foto.' . $e;
                if (file_exists($vieja) && $vieja !== $rutaAbsoluta) {
                    @unlink($vieja);
                }
            }

            // Mover archivo al destino
            if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
                throw new \RuntimeException('No se pudo guardar el archivo en el servidor.');
            }

            // Ruta relativa desde /public/ para guardar en BD y sesión
            $rutaRelativa = '/storage/usuarios/' . $idUsuario . '/foto/foto.' . $ext;

            // Guardar en BD
            $this->empModel->actualizarImagen($idEmpleado, $rutaRelativa);

            // Actualizar sesión activa
            $user = Auth::user();
            $user['img_empleado'] = $rutaRelativa;
            \App\Core\Session::set('_auth_user', $user);

            registrarAuditoria('perfil', 'FOTO', 'empleado', $idEmpleado, null, ['ruta' => $rutaRelativa]);
            $this->redirectSuccess('/perfil', 'Foto de perfil actualizada correctamente.');

        } catch (\Throwable $e) {
            error_log('[Limaro SGC] Foto perfil: ' . $e->getMessage());
            $this->redirectError('/perfil', 'Error interno al guardar la imagen. Intente de nuevo.');
        }
    }

    /** GET /perfil/cambiar-clave */
    public function cambiarClave(): void
    {
        $this->view('perfil/cambiar_clave', ['pageTitle' => 'Cambiar Clave']);
    }

    /** POST /perfil/cambiar-clave */
    public function guardarClave(): void
    {
        Csrf::verify();
        $data           = Request::only(['clave_actual', 'clave_nueva', 'clave_confirmar']);
        $idUsuario      = Auth::id() ?? 0;
        $resetForzado   = !empty(Auth::get('clave_requiere_reset'));

        // Si NO es reset forzado, verificar clave actual
        if (!$resetForzado) {
            if (!$this->userModel->verificarClave($idUsuario, $data['clave_actual'] ?? '')) {
                Session::flash('error', 'La clave actual es incorrecta.');
                $this->redirect('/perfil/cambiar-clave');
                return;
            }
        }

        // Validar nueva clave
        $errors = $this->validate($data, ['clave_nueva' => 'required|password_strong']);
        if ($errors) {
            Session::flash('error', 'La nueva clave no cumple los requisitos: mínimo 8 caracteres, mayúscula, minúscula, número y símbolo.');
            $this->redirect('/perfil/cambiar-clave');
            return;
        }

        if ($data['clave_nueva'] !== ($data['clave_confirmar'] ?? '')) {
            Session::flash('error', 'La nueva clave y su confirmación no coinciden.');
            $this->redirect('/perfil/cambiar-clave');
            return;
        }

        if (!$resetForzado && $data['clave_nueva'] === ($data['clave_actual'] ?? '')) {
            Session::flash('error', 'La nueva clave debe ser diferente a la actual.');
            $this->redirect('/perfil/cambiar-clave');
            return;
        }

        $this->userModel->cambiarClave($idUsuario, $data['clave_nueva']);

        // Limpiar el flag de reset en sesión
        $userData = Auth::user();
        if ($userData) {
            $userData['clave_requiere_reset'] = 0;
            Auth::login($userData);
        }

        registrarAuditoria('perfil', 'CAMBIAR_CLAVE', 'usuario', $idUsuario, null, null);
        $this->redirectSuccess('/inicio', 'Contraseña actualizada exitosamente.');
    }
}
