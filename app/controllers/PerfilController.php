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

        // El form envía img_b64 (Base64 del FileReader) e img_mime
        $b64Raw = $_POST['img_b64'] ?? '';
        if (empty($b64Raw)) {
            $this->redirectSuccess('/perfil', 'Sin cambios guardados.');
            return;
        }

        $idUsuario  = (int)(Auth::id() ?? 0);
        $idEmpleado = (int)(Auth::empleadoId() ?? 0);
        if (!$idUsuario) {
            $this->redirectError('/perfil', 'Usuario no identificado.');
            return;
        }

        $mime  = $_POST['img_mime'] ?? 'image/jpeg';
        $mimes = ['image/jpeg','image/png','image/webp'];
        if (!in_array($mime, $mimes, true)) {
            $this->redirectError('/perfil', 'Solo se permiten imágenes JPG, PNG o WEBP.');
            return;
        }

        // Decodificar Base64 (quitar prefijo data:image/...;base64,)
        $b64Data = $b64Raw;
        if (str_contains($b64Data, ',')) {
            $b64Data = substr($b64Data, strpos($b64Data, ',') + 1);
        }
        $imgBytes = base64_decode(str_replace(['\n','\r',' '], '', $b64Data), true);
        if ($imgBytes === false || strlen($imgBytes) < 100) {
            $this->redirectError('/perfil', 'Imagen inválida. Intente con otro archivo.');
            return;
        }
        if (strlen($imgBytes) > 5242880) {
            $this->redirectError('/perfil', 'La imagen no puede superar 5 MB.');
            return;
        }

        try {
            $carpeta = carpetaUsuario($idUsuario, 'foto');
            $ext     = match($mime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                default      => 'jpg',
            };
            $rutaAbsoluta = $carpeta . '/foto.' . $ext;

            // Eliminar fotos anteriores
            foreach (['jpg','jpeg','png','webp'] as $e) {
                $vieja = $carpeta . '/foto.' . $e;
                if (file_exists($vieja)) @unlink($vieja);
            }

            if (file_put_contents($rutaAbsoluta, $imgBytes) === false) {
                throw new \RuntimeException('No se pudo guardar la imagen.');
            }

            $rutaRelativa = '/storage/usuarios/' . $idUsuario . '/foto/foto.' . $ext;

            // Actualizar BD — delegar al modelo (elimina SQL directo)
            $idEmp = $idEmpleado
                ?: (int)(new \App\Models\EmpleadoModel())->porIdUsuario($idUsuario)['id_empleado'];
            if ($idEmp) {
                (new \App\Models\EmpleadoModel())->actualizarImagen($idEmp, $rutaRelativa);
            }

            // Actualizar sesión
            $user = Auth::user();
            $user['img_empleado'] = $rutaRelativa;
            \App\Core\Session::set('_auth_user', $user);

            $this->redirectSuccess('/perfil', 'Foto de perfil actualizada correctamente.');

        } catch (\Throwable $e) {
            error_log('[Perfil] Error: ' . $e->getMessage() . ' — ' . $e->getFile() . ':' . $e->getLine());
            $this->redirectError('/perfil', 'Error al guardar: ' . $e->getMessage());
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

        // CA-3+4+5: cerrar sesión y redirigir al login con mensaje
        Auth::logout();
        Session::flash('success', 'Contraseña actualizada correctamente. Por seguridad, inicie sesión nuevamente.');
        $this->redirect('/login');
    }
}
