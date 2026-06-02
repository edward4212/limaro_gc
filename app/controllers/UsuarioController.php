<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\UsuarioModel;
use App\Models\RolModel;
use App\Models\CargoModel;

class UsuarioController extends Controller
{
    private UsuarioModel $model;
    private RolModel     $rolModel;
    private CargoModel   $cargoModel;

    public function __construct()
    {
        $this->model      = new UsuarioModel();
        $this->rolModel   = new RolModel();
        $this->cargoModel = new CargoModel();
    }

    /** GET /usuarios */
    public function index(): void
    {
        $this->view('seguridad/usuarios/index', [
            'pageTitle' => 'Gestión de Usuarios',
            'usuarios'  => $this->model->listar(),
        ]);
    }

    /** GET /usuarios/crear */
    public function crear(): void
    {
        $this->view('seguridad/usuarios/form', [
            'pageTitle' => 'Crear Usuario',
            'item'      => null,
            'roles'     => $this->rolModel->activos(),
            'cargos'    => $this->cargoModel->activos(),
        ]);
    }

    /** POST /usuarios/crear */
    public function guardar(): void
    {
        Csrf::verify();

        $data   = Request::only(['nombre_completo','correo','id_cargo','usuario','clave','clave_confirm','estado']);
        $idRoles = array_filter(array_map('intval', (array)(Request::post('roles') ?? [])));

        $errors = $this->validate($data, [
            'nombre_completo' => 'required|max:200',
            'correo'          => 'required|email',
            'id_cargo'        => 'required|integer',
            'usuario'         => 'required|max:50',
            'clave'           => 'required|password_strong',
        ]);

        if ($data['clave'] !== ($data['clave_confirm'] ?? '')) {
            $errors['clave_confirm'][] = 'Las claves no coinciden.';
        }

        if (empty($idRoles)) {
            $errors['roles'][] = 'Debe asignar al menos un rol.';
        }

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/usuarios/crear');
            return;
        }

        try {
            $id = $this->model->crear(
                trim($data['nombre_completo']),
                trim($data['correo']),
                (int)$data['id_cargo'],
                1,
                $idRoles,
                trim($data['usuario']),
                $data['clave'],
                $data['estado'] ?? 'ACTIVO'
            );

            // Crear carpeta personal del usuario al momento de su creación
            try {
                carpetaUsuario($id, 'foto');       // /storage/usuarios/{id}/foto/
                carpetaUsuario($id, 'firmas');     // /storage/usuarios/{id}/firmas/
                carpetaUsuario($id, 'documentos'); // /storage/usuarios/{id}/documentos/
            } catch (\Throwable $eFold) {
                error_log('[Limaro SGC] crear carpeta usuario: ' . $eFold->getMessage());
            }

            registrarAuditoria('usuarios', 'CREAR', 'usuario', $id, null, [
                'usuario' => $data['usuario'], 'roles' => $idRoles,
            ]);
            $this->redirectSuccess('/usuarios', 'Usuario creado exitosamente.');
        } catch (\Throwable $e) {
            error_log('[Limaro SGC] ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            Session::flash('error', 'Error interno al procesar la operación. Contacte al administrador.');
            Session::setOldInput($data);
            $this->redirect('/usuarios/crear');
        }
    }

    /** GET /usuarios/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->verConDatos($id);
        if (!$item) $this->abort(404);

        $this->view('seguridad/usuarios/form', [
            'pageTitle' => 'Editar Usuario',
            'item'      => $item,
            'roles'     => $this->rolModel->activos(),
            'cargos'    => $this->cargoModel->activos(),
        ]);
    }

    /** POST /usuarios/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes   = $this->model->find($id);
        $estado  = Request::post('estado', 'ACTIVO');
        $idRoles = array_filter(array_map('intval', (array)(Request::post('roles') ?? [])));

        if (empty($idRoles)) {
            Session::flash('error', 'Debe asignar al menos un rol.');
            $this->redirect("/usuarios/editar/$id");
            return;
        }

        $this->model->actualizarEstadoRoles($id, $estado, $idRoles);
        registrarAuditoria('usuarios', 'EDITAR', 'usuario', $id, $antes, [
            'estado' => $estado, 'roles' => $idRoles,
        ]);
        $this->redirectSuccess('/usuarios', 'Usuario actualizado.');
    }

    /** POST /usuarios/eliminar/{id} */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        if ($id === Auth::id()) {
            $this->redirectError('/usuarios', 'No puede inactivar su propia cuenta.');
            return;
        }
        $this->model->update($id, ['estado' => 'INACTIVO']);
        $this->redirectSuccess('/usuarios', 'Usuario inactivado.');
    }

    /** POST /usuarios/resetear/{id} */
    public function resetearClave(int $id): void
    {
        Csrf::verify();
        $nuevaClave = 'T' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5))
                    . random_int(10, 99)
                    . str_shuffle('!@#$')[0];
        $this->model->cambiarClave($id, $nuevaClave);
        $this->model->desbloquear($id);
        $this->redirectSuccess('/usuarios', "Clave reseteada. Clave temporal: <strong>$nuevaClave</strong> — Comuníquela de forma segura.");
    }
}
