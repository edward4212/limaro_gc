<?php
/**
 * Definición de rutas de la aplicación Limaro SGC
 * 
 * Formato: $router->METHOD('uri', 'Controlador@metodo', ['middleware1', ...])
 * Parámetros de ruta con {param}
 */

use App\Core\Router;

/** @var Router $router */

// -----------------------------------------------------------------------
// Auth
// -----------------------------------------------------------------------
$router->get('/login',   'AuthController@showLogin');
$router->post('/login',  'AuthController@login');
$router->get('/logout',  'AuthController@logout');

// -----------------------------------------------------------------------
// Inicio / Dashboard
// -----------------------------------------------------------------------
$router->get('/inicio', 'HomeController@index', ['auth', 'permiso']);
$router->get('/',       'HomeController@index', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Configuración empresa
// -----------------------------------------------------------------------
$router->get('/empresa/editar',  'EmpresaController@editar',    ['auth', 'permiso']);
$router->post('/empresa/editar', 'EmpresaController@actualizar',['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Macroprocesos
// -----------------------------------------------------------------------
$router->get('/macroprocesos',            'MacroprocesoController@index',   ['auth', 'permiso']);
$router->get('/macroprocesos/crear',      'MacroprocesoController@crear',   ['auth', 'permiso']);
$router->post('/macroprocesos/crear',     'MacroprocesoController@guardar', ['auth', 'permiso']);
$router->get('/macroprocesos/editar/{id}','MacroprocesoController@editar',  ['auth', 'permiso']);
$router->post('/macroprocesos/editar/{id}','MacroprocesoController@actualizar', ['auth', 'permiso']);
$router->post('/macroprocesos/eliminar/{id}','MacroprocesoController@eliminar', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Procesos
// -----------------------------------------------------------------------
$router->get('/procesos',               'ProcesoController@index',      ['auth', 'permiso']);
$router->get('/procesos/crear',         'ProcesoController@crear',      ['auth', 'permiso']);
$router->post('/procesos/crear',        'ProcesoController@guardar',    ['auth', 'permiso']);
$router->get('/procesos/editar/{id}',   'ProcesoController@editar',     ['auth', 'permiso']);
$router->post('/procesos/editar/{id}',  'ProcesoController@actualizar', ['auth', 'permiso']);
$router->post('/procesos/eliminar/{id}','ProcesoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Subprocesos
// -----------------------------------------------------------------------
$router->get('/subprocesos',                'SubprocesoController@index',      ['auth', 'permiso']);
$router->get('/subprocesos/crear',          'SubprocesoController@crear',      ['auth', 'permiso']);
$router->post('/subprocesos/crear',         'SubprocesoController@guardar',    ['auth', 'permiso']);
$router->get('/subprocesos/editar/{id}',    'SubprocesoController@editar',     ['auth', 'permiso']);
$router->post('/subprocesos/editar/{id}',   'SubprocesoController@actualizar', ['auth', 'permiso']);
$router->post('/subprocesos/eliminar/{id}', 'SubprocesoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Tipos de Documento
// -----------------------------------------------------------------------
$router->get('/tipos-documento',               'TipoDocumentoController@index',      ['auth', 'permiso']);
$router->get('/tipos-documento/crear',         'TipoDocumentoController@crear',      ['auth', 'permiso']);
$router->post('/tipos-documento/crear',        'TipoDocumentoController@guardar',    ['auth', 'permiso']);
$router->get('/tipos-documento/editar/{id}',   'TipoDocumentoController@editar',     ['auth', 'permiso']);
$router->post('/tipos-documento/editar/{id}',  'TipoDocumentoController@actualizar', ['auth', 'permiso']);
$router->post('/tipos-documento/eliminar/{id}','TipoDocumentoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Documentos Registrados
// -----------------------------------------------------------------------
$router->get('/documentos/explorador',            'DocumentoController@explorador',  ['auth', 'permiso']);
$router->get('/documentos/explorador/proceso/{id}','DocumentoController@ajaxProceso', ['auth', 'permiso']);
$router->get('/documentos/explorador/tipo',        'DocumentoController@ajaxTipo',    ['auth', 'permiso']);
$router->get('/documentos',               'DocumentoController@index',      ['auth', 'permiso']);
$router->get('/documentos/crear',         'DocumentoController@crear',      ['auth', 'permiso']);
$router->post('/documentos/crear',        'DocumentoController@guardar',    ['auth', 'permiso']);
$router->get('/documentos/editar/{id}',   'DocumentoController@editar',     ['auth', 'permiso']);
$router->post('/documentos/editar/{id}',  'DocumentoController@actualizar', ['auth', 'permiso']);
$router->post('/documentos/eliminar/{id}','DocumentoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Acuerdos
// -----------------------------------------------------------------------
$router->get('/acuerdos',               'AcuerdoController@index',      ['auth', 'permiso']);
$router->get('/acuerdos/crear',         'AcuerdoController@crear',      ['auth', 'permiso']);
$router->post('/acuerdos/crear',        'AcuerdoController@guardar',    ['auth', 'permiso']);
$router->get('/acuerdos/editar/{id}',   'AcuerdoController@editar',     ['auth', 'permiso']);
$router->post('/acuerdos/editar/{id}',  'AcuerdoController@actualizar', ['auth', 'permiso']);
$router->post('/acuerdos/eliminar/{id}','AcuerdoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Documentos — Listados
// -----------------------------------------------------------------------
$router->get('/acuerdos/vigentes',            'AcuerdoController@vigentes',    ['auth', 'permiso']);
$router->get('/documentos/vigentes',          'DocumentoController@vigentes',  ['auth', 'permiso']);
$router->get('/documentos/vigentes/descargar-zip','DocumentoController@descargarZipVigentes', ['auth', 'permiso']);
$router->get('/documentos/obsoletos',         'DocumentoController@obsoletos', ['auth', 'permiso']);
$router->get('/versionamiento',                    'VersionamientoController@index',    ['auth', 'permiso']);
$router->get('/versionamiento/documento/{id}',     'VersionamientoController@detalle',  ['auth', 'permiso']);
$router->get('/versionamiento/descargar/{id}',     'VersionamientoController@descargar',['auth', 'permiso']);
$router->get('/versionamiento/nueva/{id}',         'VersionamientoController@nueva',    ['auth', 'permiso']);
$router->post('/versionamiento/nueva/{id}',        'VersionamientoController@guardar',  ['auth', 'permiso']);
$router->post('/versionamiento/cambiar-estado/{id}','VersionamientoController@cambiarEstado',['auth', 'permiso']);

// -----------------------------------------------------------------------
// Solicitudes — Radicar
// -----------------------------------------------------------------------
$router->get('/solicitudes/mis-radicadas',  'SolicitudController@misRadicadas',  ['auth', 'permiso']);
$router->get('/solicitudes/crear',          'SolicitudController@crear',         ['auth', 'permiso']);
$router->post('/solicitudes/crear',         'SolicitudController@guardarCrear',  ['auth', 'permiso']);
$router->get('/solicitudes/actualizar',     'SolicitudController@actualizar',    ['auth', 'permiso']);
$router->post('/solicitudes/actualizar',    'SolicitudController@guardarActualizar', ['auth', 'permiso']);
$router->get('/solicitudes/eliminar',       'SolicitudController@eliminar',      ['auth', 'permiso']);
$router->post('/solicitudes/eliminar',      'SolicitudController@guardarEliminar', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Solicitudes — Gestión
// -----------------------------------------------------------------------
$router->get('/solicitudes/radicadas',         'SolicitudController@radicadas',   ['auth', 'permiso']);
$router->get('/solicitudes/asignadas',         'SolicitudController@asignadas',   ['auth', 'permiso']);
$router->get('/solicitudes/desarrollo',        'SolicitudController@desarrollo',  ['auth', 'permiso']);
$router->get('/solicitudes/finalizadas',       'SolicitudController@finalizadas', ['auth', 'permiso']);
$router->get('/solicitudes/ver/{id}',          'SolicitudController@ver',         ['auth', 'permiso']);
$router->post('/solicitudes/asignar/{id}',     'SolicitudController@asignar',     ['auth', 'permiso']);
$router->post('/solicitudes/comentar/{id}',    'SolicitudController@comentar',    ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Tareas
// -----------------------------------------------------------------------
$router->get('/tareas/asignadas',             'TareaController@asignadas',  ['auth', 'permiso']);
$router->post('/tareas/iniciar/{id}',         'TareaController@iniciar',    ['auth', 'permiso']);
$router->get('/tareas/elaborar',              'TareaController@elaborar',   ['auth', 'permiso']);
$router->get('/tareas/elaborar/{id}',         'TareaController@elaborarVer', ['auth', 'permiso']);
$router->post('/tareas/elaborar/{id}',        'TareaController@elaborarGuardar', ['auth', 'permiso']);
$router->get('/tareas/revisar',               'TareaController@revisar',    ['auth', 'permiso']);
$router->get('/tareas/revisar/{id}',          'TareaController@revisarVer', ['auth', 'permiso']);
$router->post('/tareas/revisar/{id}',         'TareaController@revisarGuardar', ['auth', 'permiso']);
$router->get('/tareas/aprobar',               'TareaController@aprobar',    ['auth', 'permiso']);
$router->get('/tareas/aprobar/{id}',          'TareaController@aprobarVer', ['auth', 'permiso']);
$router->post('/tareas/aprobar/{id}',         'TareaController@aprobarGuardar', ['auth', 'permiso']);
$router->get('/tareas/devueltas',             'TareaController@devueltas',  ['auth', 'permiso']);
$router->get('/tareas/finalizadas',           'TareaController@finalizadas', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Configuración del sistema
// -----------------------------------------------------------------------
$router->get('/configuracion',          'ConfigController@index',        ['auth', 'permiso']);
$router->post('/configuracion/correo',  'ConfigController@guardarCorreo',['auth', 'permiso']);

// -----------------------------------------------------------------------
// Seguridad — Usuarios
// -----------------------------------------------------------------------
$router->get('/usuarios',               'UsuarioController@index',      ['auth', 'permiso']);
$router->get('/usuarios/crear',         'UsuarioController@crear',      ['auth', 'permiso']);
$router->post('/usuarios/crear',        'UsuarioController@guardar',    ['auth', 'permiso']);
$router->get('/usuarios/editar/{id}',   'UsuarioController@editar',     ['auth', 'permiso']);
$router->post('/usuarios/editar/{id}',  'UsuarioController@actualizar', ['auth', 'permiso']);
$router->post('/usuarios/eliminar/{id}','UsuarioController@eliminar',   ['auth', 'permiso']);
$router->post('/usuarios/resetear/{id}','UsuarioController@resetearClave', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Configuración del sistema
// -----------------------------------------------------------------------
$router->get('/configuracion',          'ConfigController@index',        ['auth', 'permiso']);
$router->post('/configuracion/correo',  'ConfigController@guardarCorreo',['auth', 'permiso']);

// -----------------------------------------------------------------------
// Seguridad — Cargos
// -----------------------------------------------------------------------
$router->get('/cargos',               'CargoController@index',      ['auth', 'permiso']);
$router->get('/cargos/crear',         'CargoController@crear',      ['auth', 'permiso']);
$router->post('/cargos/crear',        'CargoController@guardar',    ['auth', 'permiso']);
$router->get('/cargos/editar/{id}',   'CargoController@editar',     ['auth', 'permiso']);
$router->post('/cargos/editar/{id}',  'CargoController@actualizar', ['auth', 'permiso']);
$router->post('/cargos/eliminar/{id}','CargoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Configuración del sistema
// -----------------------------------------------------------------------
$router->get('/configuracion',          'ConfigController@index',        ['auth', 'permiso']);
$router->post('/configuracion/correo',  'ConfigController@guardarCorreo',['auth', 'permiso']);

// -----------------------------------------------------------------------
// Seguridad — Manual de Funciones
// -----------------------------------------------------------------------
$router->get('/manual-funciones', 'ManualFuncionesController@index', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Configuración del sistema
// -----------------------------------------------------------------------
$router->get('/configuracion',          'ConfigController@index',        ['auth', 'permiso']);
$router->post('/configuracion/correo',  'ConfigController@guardarCorreo',['auth', 'permiso']);

// -----------------------------------------------------------------------
// Seguridad — Roles
// -----------------------------------------------------------------------
$router->get('/roles',                  'RolController@index',      ['auth', 'permiso']);
$router->get('/roles/crear',            'RolController@crear',      ['auth', 'permiso']);
$router->post('/roles/crear',           'RolController@guardar',    ['auth', 'permiso']);
$router->get('/roles/editar/{id}',      'RolController@editar',     ['auth', 'permiso']);
$router->post('/roles/editar/{id}',     'RolController@actualizar', ['auth', 'permiso']);
$router->post('/roles/eliminar/{id}',   'RolController@eliminar',   ['auth', 'permiso']);
$router->get('/roles/permisos/{id}',    'RolController@permisos',   ['auth', 'permiso']);
$router->post('/roles/permisos/{id}',   'RolController@guardarPermisos', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Perfil
// -----------------------------------------------------------------------
$router->get('/perfil',              'PerfilController@index',      ['auth', 'permiso']);
$router->post('/perfil',             'PerfilController@actualizar', ['auth', 'permiso']);
$router->get('/perfil/cambiar-clave','PerfilController@cambiarClave', ['auth', 'permiso']);
$router->post('/perfil/cambiar-clave','PerfilController@guardarClave', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Archivos — descargas seguras
// -----------------------------------------------------------------------
$router->get('/archivo/{id}',        'ArchivoController@descargar', ['auth', 'permiso']);

// ───────────────────────────────────────────────────────────────────────
// §9.2 Auditoría Interna
// ───────────────────────────────────────────────────────────────────────
$router->get('/auditoria-interna',                         'AuditoriaInternaController@index',   ['auth','permiso']);
$router->get('/auditoria-interna/crear',                   'AuditoriaInternaController@crear',   ['auth','permiso']);
$router->post('/auditoria-interna/crear',                  'AuditoriaInternaController@guardar', ['auth','permiso']);
$router->get('/auditoria-interna/editar/{id}',             'AuditoriaInternaController@editar',  ['auth','permiso']);
$router->post('/auditoria-interna/editar/{id}',            'AuditoriaInternaController@actualizar', ['auth','permiso']);
$router->post('/auditoria-interna/hallazgo/{id}',          'AuditoriaInternaController@guardarHallazgo', ['auth','permiso']);

// ───────────────────────────────────────────────────────────────────────
// §9.3 Revisión por la Dirección
// ───────────────────────────────────────────────────────────────────────
$router->get('/revision-direccion',                'RevisionDireccionController@index',    ['auth','permiso']);
$router->get('/revision-direccion/crear',          'RevisionDireccionController@crear',    ['auth','permiso']);
$router->post('/revision-direccion/crear',         'RevisionDireccionController@guardar',  ['auth','permiso']);
$router->get('/revision-direccion/ver/{id}',       'RevisionDireccionController@ver',      ['auth','permiso']);
$router->get('/revision-direccion/editar/{id}',    'RevisionDireccionController@editar',   ['auth','permiso']);
$router->post('/revision-direccion/editar/{id}',   'RevisionDireccionController@actualizar', ['auth','permiso']);

// ── §6.2 Objetivos de Calidad ──────────────────────────────────────────
$router->get('/objetivos-calidad',                  'ObjetivoCalidadController@index',    ['auth','permiso']);
$router->get('/objetivos-calidad/crear',            'ObjetivoCalidadController@crear',    ['auth','permiso']);
$router->post('/objetivos-calidad/crear',           'ObjetivoCalidadController@guardar',  ['auth','permiso']);
$router->get('/objetivos-calidad/editar/{id}',      'ObjetivoCalidadController@editar',   ['auth','permiso']);
$router->post('/objetivos-calidad/editar/{id}',     'ObjetivoCalidadController@actualizar',['auth','permiso']);
$router->post('/objetivos-calidad/medicion/{id}',   'ObjetivoCalidadController@registrarMedicion',['auth','permiso']);

// ── §10.2 Acciones Correctivas ─────────────────────────────────────────
$router->get('/acciones-correctivas',               'AccionCorrectivaController@index',    ['auth','permiso']);
$router->get('/acciones-correctivas/crear',         'AccionCorrectivaController@crear',    ['auth','permiso']);
$router->post('/acciones-correctivas/crear',        'AccionCorrectivaController@guardar',  ['auth','permiso']);
$router->get('/acciones-correctivas/editar/{id}',   'AccionCorrectivaController@editar',   ['auth','permiso']);
$router->post('/acciones-correctivas/editar/{id}',  'AccionCorrectivaController@actualizar',['auth','permiso']);

// ── Centro de Reportes ─────────────────────────────────────────────────
$router->get('/reportes',                          'ReporteController@index',          ['auth','permiso']);
$router->get('/reportes/documentos/vigentes',      'ReporteController@docVigentes',    ['auth','permiso']);
$router->get('/reportes/documentos/obsoletos',     'ReporteController@docObsoletos',   ['auth','permiso']);
$router->get('/reportes/documentos/por-proceso',   'ReporteController@docPorProceso',  ['auth','permiso']);
$router->get('/reportes/documentos/versionamiento','ReporteController@docVersionamiento',['auth','permiso']);
$router->get('/reportes/solicitudes',              'ReporteController@solicitudes',    ['auth','permiso']);
$router->get('/reportes/tareas',                   'ReporteController@tareas',         ['auth','permiso']);
$router->get('/reportes/sgc/ejecutivo',            'ReporteController@sgcEjecutivo',   ['auth','permiso']);
$router->get('/reportes/sgc/objetivos',            'ReporteController@sgcObjetivos',   ['auth','permiso']);
$router->get('/reportes/sgc/hallazgos',            'ReporteController@sgcHallazgos',   ['auth','permiso']);
$router->get('/reportes/sgc/acciones-correctivas', 'ReporteController@sgcAcciones',    ['auth','permiso']);
$router->get('/reportes/seguridad/logins',         'ReporteController@segLogins',      ['auth','permiso']);
