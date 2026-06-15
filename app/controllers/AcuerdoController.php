<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Models\AcuerdoModel;
use App\Models\TipoDocumentoModel;
use App\Models\ArchivoModel;
use App\Models\UsuarioModel;

/**
 * Controlador de Acuerdos.
 */
class AcuerdoController extends Controller
{
    private AcuerdoModel       $model;
    private TipoDocumentoModel $tipoModel;
    private ArchivoModel       $archivoModel;
    private UsuarioModel       $usuarioModel;

    public function __construct()
    {
        $this->model        = new AcuerdoModel();
        $this->tipoModel    = new TipoDocumentoModel();
        $this->archivoModel = new ArchivoModel();
        $this->usuarioModel = new UsuarioModel();
    }

    /** GET /acuerdos */
    public function index(): void
    {
        $this->view('empresa/acuerdos/index', [
            'pageTitle' => 'Acuerdos',
            'acuerdos'  => $this->model->listar(),
        ]);
    }

    /** GET /acuerdos/vigentes */
    public function vigentes(): void
    {
        $año = Request::get('año') ? (int) Request::get('año') : null;
        $this->view('documentos/acuerdos_vigentes', [
            'pageTitle' => 'Acuerdos Vigentes',
            'acuerdos'  => $this->model->vigentes($año),
            'años'      => $this->model->años(),
            'filtroAño' => $año,
        ]);
    }

    /** GET /acuerdos/ver/{id} — ver inline en el navegador */
    public function ver(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404, 'Acuerdo no encontrado.');

        $archivo = $this->archivoModel->porEntidad('ACUERDO', $id);
        if (!$archivo) {
            Session::flash('error', 'Este acuerdo no tiene archivo adjunto.');
            $this->redirect('/acuerdos');
            return;
        }

        $rutaAbs = APP_ROOT . '/public' . $archivo['ruta_relativa'];
        if (!file_exists($rutaAbs)) {
            Session::flash('error', 'El archivo no se encontró en el servidor. Ruta: ' . $archivo['ruta_relativa']);
            $this->redirect('/acuerdos');
            return;
        }

        $mime   = mime_content_type($rutaAbs) ?: 'application/octet-stream';
        $nombre = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $archivo['nombre_original'] ?? 'archivo.pdf');

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . $nombre . '"');
        header('Content-Length: ' . filesize($rutaAbs));
        header('Cache-Control: private, max-age=3600');
        readfile($rutaAbs);
        exit;
    }

    /** GET /acuerdos/crear */
    public function crear(): void
    {
        Session::clearOldInput(); // Limpiar datos de intentos anteriores
        $tipoAcuerdo = $this->tipoModel->buscarPorNombre('ACUERDO');
        $this->view('empresa/acuerdos/form', [
            'pageTitle'   => 'Nuevo Acuerdo',
            'item'        => null,
            'tipos'       => $this->tipoModel->activos(),
            'tipoAcuerdo' => $tipoAcuerdo,
        ]);
    }

    /** POST /acuerdos/crear */
    public function guardar(): void
    {
        Csrf::verify();

        $data = Request::only([
            'año_acuerdo', 'numero_acuerdo', 'nombre_acuerdo',
            'id_tipo_documento', 'fecha_aprobacion', 'acta_aprobacion',
        ]);
        // HU-N01: forzar tipo 'Acuerdo' — previene manipulación del form
        $tipoAc = $this->tipoModel->porNombre('Acuerdo');
        if ($tipoAc) { $data['id_tipo_documento'] = $tipoAc['id_tipo_documento']; }

        // CA-1: validaciones numéricas server-side con mensajes descriptivos
        if (!ctype_digit((string)($data['año_acuerdo'] ?? '')) ||
            (int)$data['año_acuerdo'] < 2000 || (int)$data['año_acuerdo'] > 2100) {
            Session::flash('error', 'El campo <strong>Año</strong> debe ser un número entre 2000 y 2100.');
            $this->redirect(isset($data['id_acuerdo']) ? '/acuerdos/editar/'.$data['id_acuerdo'] : '/acuerdos/crear');
            return;
        }
        if (!ctype_digit((string)($data['numero_acuerdo'] ?? '')) ||
            (int)$data['numero_acuerdo'] < 1) {
            Session::flash('error', 'El campo <strong>Número</strong> solo acepta valores numéricos positivos.');
            $this->redirect(isset($data['id_acuerdo']) ? '/acuerdos/editar/'.$data['id_acuerdo'] : '/acuerdos/crear');
            return;
        }
        if (!ctype_digit((string)($data['acta_aprobacion'] ?? '')) ||
            (int)$data['acta_aprobacion'] < 0) {
            Session::flash('error', 'El campo <strong>N° Acta de Aprobación</strong> solo acepta valores numéricos.');
            $this->redirect(isset($data['id_acuerdo']) ? '/acuerdos/editar/'.$data['id_acuerdo'] : '/acuerdos/crear');
            return;
        }

        $errors = $this->validate($data, [
            'año_acuerdo'       => 'required|integer',
            'numero_acuerdo'    => 'required|max:20',
            'nombre_acuerdo'    => 'required|max:300',
            'id_tipo_documento' => 'required|integer',
            'acta_aprobacion'   => 'required|integer',
        ]);

        if ($errors) {
            Session::flash('error', 'Corrija los errores del formulario.');
            Session::setOldInput($data);
            $this->redirect('/acuerdos/crear');
            return;
        }

        // Validar archivo ANTES de crear el acuerdo
        $uploadData = null;
        // Detectar archivo: puede venir por base64 (POST) o $_FILES clásico
        $uploadData     = null;
        $tieneB64       = !empty($_POST['archivo_pdf_b64']);
        $tieneFile      = isset($_FILES['archivo_pdf']) && ($_FILES['archivo_pdf']['error'] ?? 4) === UPLOAD_ERR_OK;
        $archivoEnviado = $tieneB64 || $tieneFile;

        if ($archivoEnviado) {
            try {
                // Pasar field_name para que el helper encuentre los campos _b64 en POST
                $fileRef = $_FILES['archivo_pdf'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'size' => 0, 'tmp_name' => ''];
                $fileRef['field_name'] = 'archivo_pdf';
                $uploadData = subirArchivo(
                    $fileRef,
                    'acuerdos',
                    ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    20971520
                );
            } catch (\Throwable $e) {
                error_log('[Limaro SGC] subirArchivo acuerdo crear: ' . $e->getMessage());
                Session::flash('error', 'No se pudo guardar el archivo: ' . $e->getMessage());
                Session::setOldInput($data);
                $this->redirect('/acuerdos/crear');
                return;
            }
        }

        // Crear el acuerdo
        $id = $this->model->crear([
            'año_acuerdo'        => (int) $data['año_acuerdo'],
            'numero_acuerdo'     => trim($data['numero_acuerdo']),
            'nombre_acuerdo'     => trim($data['nombre_acuerdo']),
            'id_tipo_documento'  => (int) $data['id_tipo_documento'],
            'fecha_aprobacion'   => $data['fecha_aprobacion'] ?: null,
            'acta_aprobacion'    => (int) ($data['acta_aprobacion'] ?? 0),
            'id_usuario_creador' => Auth::id(),
        ]);

        // Registrar archivo en BD
        if ($uploadData !== null) {
            try {
                $this->archivoModel->registrar('ACUERDO', $id, $uploadData, Auth::id());
            } catch (\Throwable $e) {
                error_log('[Limaro SGC] registrar archivo acuerdo ' . $id . ': ' . $e->getMessage());
                Session::flash('warning', 'Acuerdo creado, pero el archivo no pudo registrarse en BD: ' . $e->getMessage());
                $this->redirect('/acuerdos');
                return;
            }
        }

        registrarAuditoria('acuerdos', 'CREAR', 'acuerdo', $id, null, $data);

        // HU-009: Enviar notificación por correo a todos los usuarios activos
        try {
            $acuerdoCompleto = $this->model->find($id);
            // Enriquecer con tipo de documento para el correo
            if ($acuerdoCompleto) {
                $tipoDoc = $this->tipoModel->find((int)$acuerdoCompleto['id_tipo_documento']);
                $acuerdoCompleto['tipo_documento'] = $tipoDoc['tipo_documento'] ?? 'Acuerdo';
                $acuerdoCompleto['id_acuerdo']     = $id;
            }
            $usuarios  = $this->usuarioModel->usuariosActivosConCorreo();
            $resultado = notifAcuerdoCreado(
                $acuerdoCompleto ?? array_merge($data, ['id_acuerdo' => $id]),
                $usuarios,
                (int)(Auth::id() ?? 0)
            );
            if ($resultado['fallidos'] > 0) {
                error_log("[HU-009] Acuerdo $id: {$resultado['enviados']} enviados, {$resultado['fallidos']} fallidos.");
            }
        } catch (\Throwable $e) {
            error_log('[HU-009] notifAcuerdoCreado error: ' . $e->getMessage());
            // No interrumpir el flujo principal si falla el correo
        }

        $this->redirectSuccess('/acuerdos', 'Acuerdo registrado y notificaciones enviadas.');
    }

    /** GET /acuerdos/editar/{id} */
    public function editar(int $id): void
    {
        $item = $this->model->find($id);
        if (!$item) $this->abort(404);

        $archivo     = $this->archivoModel->porEntidad('ACUERDO', $id);
        $tipoAcuerdo = $this->tipoModel->buscarPorNombre('ACUERDO');

        $this->view('empresa/acuerdos/form', [
            'pageTitle'   => 'Editar Acuerdo',
            'item'        => $item,
            'tipos'       => $this->tipoModel->activos(),
            'tipoAcuerdo' => $tipoAcuerdo,
            'archivo'     => $archivo,
        ]);
    }

    /** POST /acuerdos/editar/{id} */
    public function actualizar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        if (!$antes) $this->abort(404);

        $data = Request::only([
            'año_acuerdo', 'numero_acuerdo', 'nombre_acuerdo',
            'id_tipo_documento', 'fecha_aprobacion', 'acta_aprobacion',
        ]);
        // HU-N01: forzar tipo 'Acuerdo' en edición
        $tipoAc = $this->tipoModel->porNombre('Acuerdo');
        if ($tipoAc) { $data['id_tipo_documento'] = $tipoAc['id_tipo_documento']; }

        // Validar y subir nuevo archivo si se envió
        // Detectar si el usuario intentó subir un archivo
        // No filtrar por size>0 — algunos servidores reportan 0 en uploads válidos
        $uploadData     = null;
        $tieneB64       = !empty($_POST['archivo_pdf_b64']);
        $tieneFile      = isset($_FILES['archivo_pdf']) && ($_FILES['archivo_pdf']['error'] ?? 4) === UPLOAD_ERR_OK;
        $archivoEnviado = $tieneB64 || $tieneFile;

        if ($archivoEnviado) {
            try {
                $fileRef = $_FILES['archivo_pdf'] ?? ['error' => UPLOAD_ERR_NO_FILE, 'name' => '', 'size' => 0, 'tmp_name' => ''];
                $fileRef['field_name'] = 'archivo_pdf';
                $uploadData = subirArchivo(
                    $fileRef,
                    'acuerdos',
                    ['application/pdf', 'application/msword',
                     'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                    20971520
                );
            } catch (\Throwable $e) {
                error_log('[Limaro SGC] subirArchivo acuerdo actualizar ' . $id . ': ' . $e->getMessage());
                Session::flash('error', 'No se pudo guardar el archivo: ' . $e->getMessage());
                $this->redirect("/acuerdos/editar/$id");
                return;
            }
        }

        // Actualizar datos del acuerdo
        $this->model->actualizar($id, [
            'año_acuerdo'       => (int) $data['año_acuerdo'],
            'numero_acuerdo'    => trim($data['numero_acuerdo']),
            'nombre_acuerdo'    => trim($data['nombre_acuerdo']),
            'id_tipo_documento' => (int) $data['id_tipo_documento'],
            'fecha_aprobacion'  => $data['fecha_aprobacion'] ?: null,
            'acta_aprobacion'   => (int) ($data['acta_aprobacion'] ?? 0),
        ]);

        // Registrar nuevo archivo si se subió
        if ($uploadData !== null) {
            try {
                $this->archivoModel->registrar('ACUERDO', $id, $uploadData, Auth::id());
            } catch (\Throwable $e) {
                error_log('[Limaro SGC] registrar archivo actualizar acuerdo ' . $id . ': ' . $e->getMessage());
                Session::flash('warning', 'Acuerdo actualizado, pero el archivo no pudo registrarse: ' . $e->getMessage());
                $this->redirect("/acuerdos/editar/$id");
                return;
            }
        }

        registrarAuditoria('acuerdos', 'EDITAR', 'acuerdo', $id, $antes, $data);
        $this->redirectSuccess('/acuerdos', 'Acuerdo actualizado correctamente.');
    }

    /** POST /acuerdos/eliminar/{id} — soft-delete */
    public function eliminar(int $id): void
    {
        Csrf::verify();
        $antes = $this->model->find($id);
        try {
            $this->model->update($id, ['estado' => 'INACTIVO']);
            registrarAuditoria('acuerdos', 'ELIMINAR', 'acuerdo', $id, $antes, ['estado' => 'INACTIVO']);
            $this->redirectSuccess('/acuerdos', 'Acuerdo inactivado correctamente.');
        } catch (\Throwable $e) {
            error_log('[Limaro SGC] eliminar acuerdo: ' . $e->getMessage());
            Session::flash('error', 'No se pudo inactivar. Verifique que ejecutó la migración de normalización.');
            $this->redirect('/acuerdos');
        }
    }

    /**
     * Validar el array $_FILES[key] antes de subirlo.
     * Retorna string con mensaje de error, o null si está OK.
     */
    private function validarArchivoSubido(array $file): ?string
    {
        $erroresPHP = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite configurado en el servidor (upload_max_filesize).',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario.',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta. Intente de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Error interno: no se encontró directorio temporal en el servidor.',
            UPLOAD_ERR_CANT_WRITE => 'Error interno: no se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP bloqueó la subida del archivo.',
        ];

        $errorCode = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($errorCode === UPLOAD_ERR_NO_FILE) {
            return null; // No se envió archivo — no es un error
        }

        if ($errorCode !== UPLOAD_ERR_OK) {
            return $erroresPHP[$errorCode] ?? "Error al subir archivo (código PHP: $errorCode).";
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return 'El archivo no llegó al servidor correctamente.';
        }

        return null; // Todo OK
    }

    /**
     * GET /acuerdos/descargar-zip
     * Descarga todos los acuerdos ACTIVOS como ZIP. HU-008.
     */
    public function descargarZip(): void
    {
        if (!Auth::puede('acuerdos', 'ver')) {
            Session::flash('error', 'No tiene permiso para descargar acuerdos.');
            $this->redirect('/acuerdos');
            return;
        }

        // Obtener acuerdos activos con archivo
        $acuerdos = $this->model->listar();
        $conArchivo = array_filter($acuerdos, fn($a) =>
            ($a['estado'] ?? 'ACTIVO') === 'ACTIVO' && !empty($a['id_archivo'])
        );

        if (empty($conArchivo)) {
            Session::flash('warning', 'No hay acuerdos vigentes con archivos para descargar.');
            $this->redirect('/acuerdos');
            return;
        }

        if (!class_exists('ZipArchive')) {
            Session::flash('error', 'El servidor no tiene la extensión ZipArchive habilitada.');
            $this->redirect('/acuerdos');
            return;
        }

        $zip      = new \ZipArchive();
        $dirTmp  = APP_ROOT . '/storage/tmp';
        if (!is_dir($dirTmp)) {
            mkdir($dirTmp, 0755, true);
        }
        $tmpZip = $dirTmp . '/acuerdos_' . time() . '_' . rand(1000,9999) . '.zip';

        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Session::flash('error', 'No se pudo crear el archivo ZIP.');
            $this->redirect('/acuerdos');
            return;
        }

        // Prevenir timeout en colecciones grandes (PERF)
        set_time_limit(120);

        // Límite de seguridad: máximo 200 acuerdos por descarga
        // Para colecciones mayores, exportar por rango de año desde los filtros
        $conArchivo = array_slice(array_values($conArchivo), 0, 200);

        $agregados = 0;
        $errores   = [];

        foreach ($conArchivo as $a) {
            // Obtener ruta del archivo más reciente
            $archivo = $this->archivoModel->porEntidad('ACUERDO', (int)$a['id_acuerdo']);
            if (!$archivo) continue;

            $rutaAbs = APP_ROOT . '/public' . $archivo['ruta_relativa'];
            if (!file_exists($rutaAbs)) {
                $errores[] = $a['numero_acuerdo'];
                continue;
            }

            // Nombre legible dentro del ZIP: año_número_nombre.ext
            $ext         = pathinfo($archivo['nombre_original'], PATHINFO_EXTENSION) ?: 'pdf';
            $nombreLimpio = preg_replace('/[^a-zA-Z0-9_\-]/', '_',
                $a['año_acuerdo'] . '_' . $a['numero_acuerdo'] . '_' .
                substr($a['nombre_acuerdo'], 0, 50)
            );
            $nombreZip = $nombreLimpio . '.' . $ext;

            $zip->addFile($rutaAbs, $nombreZip);
            $agregados++;
        }

        $zip->close();

        // Registrar en log_descarga_masiva
        try {
            $this->query(
                "INSERT IGNORE INTO log_descarga_masiva (modulo, filtro, total_items, id_usuario, ip)
                 VALUES ('ACUERDOS', 'estado=ACTIVO', ?, ?, ?)",
                [$agregados, Auth::id() ?? 0, Request::ip() ?? '']
            );
        } catch (\Throwable $e) {
            error_log('[Limaro] log_descarga_masiva: ' . $e->getMessage());
        }

        if ($agregados === 0) {
            @unlink($tmpZip);
            Session::flash('warning', 'No se encontraron archivos físicos para incluir en el ZIP.');
            $this->redirect('/acuerdos');
            return;
        }

        // Nombre del ZIP
        $nombreDescarga = 'acuerdos_' . date('Y-m') . '.zip';

        // Enviar al navegador
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($tmpZip);

        // Limpiar ZIP temporal
        @unlink($tmpZip);
        exit;
    }

}