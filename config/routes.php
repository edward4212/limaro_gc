<?php
/**
 * Definición de rutas de la aplicación Limaro SGC
 * 
 * Formato: $router->METHOD('uri', 'Controlador@metodo', ['middleware1', ...])
 * Parámetros de ruta con {param}
 */

use App\Core\Router;

/** @var Router $router */

// Foto de perfil (evita 403 en acceso directo a storage)
$router->get('/foto-usuario/{id}', 'FotoController@perfil');

// -----------------------------------------------------------------------
// Auth
// -----------------------------------------------------------------------
$router->get('/login',   'AuthController@showLogin');
$router->post('/login',  'AuthController@login');
$router->get('/logout',  'AuthController@logout');
$router->get('/recuperar-clave',  'RecuperarClaveController@show');
$router->post('/recuperar-clave', 'RecuperarClaveController@enviar');

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
$router->get('/documentos/reasignar/{id}',  'DocumentoController@reasignarForm', ['auth','permiso']);
$router->post('/documentos/reasignar/{id}', 'DocumentoController@reasignar',     ['auth','permiso']);
$router->get('/documentos/editar/{id}',   'DocumentoController@editar',     ['auth', 'permiso']);
$router->post('/documentos/editar/{id}',  'DocumentoController@actualizar', ['auth', 'permiso']);
$router->post('/documentos/eliminar/{id}','DocumentoController@eliminar',   ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Datos Empresa — Acuerdos
// -----------------------------------------------------------------------
$router->get('/acuerdos',               'AcuerdoController@index',      ['auth', 'permiso']);
$router->get('/acuerdos/descargar-zip', 'AcuerdoController@descargarZip', ['auth','permiso']);
$router->get('/acuerdos/ver/{id}', 'AcuerdoController@ver', ['auth', 'permiso']);
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
$router->get('/documentos/buscar',                  'DocumentoController@buscar',             ['auth']);
$router->get('/documentos/vigentes/descargar-zip','DocumentoController@descargarZipVigentes', ['auth', 'permiso']);
$router->get('/documentos/obsoletos',         'DocumentoController@obsoletos', ['auth', 'permiso']);
$router->get('/versionamiento',                    'VersionamientoController@index',    ['auth', 'permiso']);
$router->get('/versionamiento/inactivos',       'VersionamientoController@inactivos',      ['auth','permiso']);
$router->post('/versionamiento/reactivar/{id}',  'VersionamientoController@reactivar',      ['auth','permiso']);
$router->get('/versionamiento/documento/{id}',     'VersionamientoController@detalle',  ['auth', 'permiso']);
$router->get('/versionamiento/descargar/{id}',     'VersionamientoController@descargar',['auth', 'permiso']);
$router->get('/versionamiento/nueva/{id}',         'VersionamientoController@nueva',    ['auth', 'permiso']);
$router->post('/versionamiento/nueva/{id}',        'VersionamientoController@guardar',  ['auth', 'permiso']);
$router->get('/versionamiento/reemplazar/{id_ver}',  'VersionamientoController@reemplazarForm',  ['auth', 'permiso']);
$router->post('/versionamiento/reemplazar/{id_ver}', 'VersionamientoController@reemplazarGuardar',['auth', 'permiso']);
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
$router->get('/solicitudes/panel',    'SolicitudController@panel',    ['auth','permiso']);
$router->get('/solicitudes/radicadas',         'SolicitudController@radicadas',   ['auth', 'permiso']);
$router->get('/solicitudes/asignadas',         'SolicitudController@asignadas',   ['auth', 'permiso']);
$router->get('/solicitudes/desarrollo',        'SolicitudController@desarrollo',  ['auth', 'permiso']);
$router->get('/solicitudes/finalizadas',       'SolicitudController@finalizadas', ['auth', 'permiso']);
$router->get('/solicitudes/ver/{id}',          'SolicitudController@ver',         ['auth', 'permiso']);
$router->post('/solicitudes/reasignar/{id}', 'SolicitudController@reasignar',  ['auth','permiso']);
$router->post('/solicitudes/asignar/{id}',     'SolicitudController@asignar',     ['auth', 'permiso']);
$router->post('/solicitudes/finalizar-sin-tramite/{id}', 'SolicitudController@finalizarSinTramite', ['auth','permiso']);
$router->post('/solicitudes/comentar/{id}',    'SolicitudController@comentar',    ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Tareas
// -----------------------------------------------------------------------
$router->get('/tareas/asignadas',             'TareaController@asignadas',  ['auth', 'permiso']);
$router->post('/tareas/iniciar/{id}',         'TareaController@iniciar',    ['auth', 'permiso']);
$router->get('/tareas/elaborar',              'TareaController@elaborar',   ['auth', 'permiso']);
$router->get('/tareas/ver/{id}',          'TareaController@ver',             ['auth','permiso']);
$router->get('/tareas/elaborar/{id}',         'TareaController@elaborarVer', ['auth', 'permiso']);
$router->post('/tareas/elaborar/{id}',        'TareaController@elaborarGuardar', ['auth', 'permiso']);
$router->get('/tareas/revisar',               'TareaController@revisar',    ['auth', 'permiso']);
$router->get('/tareas/revisar/{id}',          'TareaController@revisarVer', ['auth', 'permiso']);
$router->post('/tareas/revisar/{id}',         'TareaController@revisarGuardar', ['auth', 'permiso']);
$router->get('/tareas/aprobar',               'TareaController@aprobar',    ['auth', 'permiso']);
$router->get('/tareas/aprobar/{id}',          'TareaController@aprobarVer', ['auth', 'permiso']);
$router->post('/tareas/aprobar/{id}',         'TareaController@aprobarGuardar', ['auth', 'permiso']);
$router->get('/tareas/mis-tareas', 'TareaController@misTareas', ['auth','permiso']);
$router->get('/tareas/panel',      'TareaController@panel',     ['auth','permiso']);
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
$router->post('/usuarios/activar/{id}', 'UsuarioController@activar',    ['auth', 'permiso']);
$router->post('/usuarios/eliminar/{id}','UsuarioController@eliminar',   ['auth', 'permiso']);
$router->post('/usuarios/resetear/{id}','UsuarioController@resetearClave', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Configuración del sistema
// -----------------------------------------------------------------------

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

// -----------------------------------------------------------------------
// Seguridad — Manual de Funciones
// -----------------------------------------------------------------------
$router->get('/manual-funciones', 'ManualFuncionesController@index', ['auth', 'permiso']);

// -----------------------------------------------------------------------
// Configuración del sistema
// -----------------------------------------------------------------------

// -----------------------------------------------------------------------
// Seguridad — Roles
// -----------------------------------------------------------------------
$router->get('/roles',                  'RolController@index',      ['auth', 'permiso']);
$router->get('/roles/crear',            'RolController@crear',      ['auth', 'permiso']);
$router->post('/roles/crear',           'RolController@guardar',    ['auth', 'permiso']);
$router->get('/roles/editar/{id}',      'RolController@editar',     ['auth', 'permiso']);
$router->post('/roles/editar/{id}',     'RolController@actualizar', ['auth', 'permiso']);
$router->post('/roles/eliminar/{id}',   'RolController@eliminar',   ['auth', 'permiso']);
$router->get('/roles/sincronizar/{id}',  'RolController@sincronizar',   ['auth','permiso']);
$router->get('/roles/permisos/{id}',    'RolController@permisos',   ['auth', 'permiso']);
$router->post('/roles/permisos/{id}',   'RolController@guardarPermisos', ['auth', 'permiso']);

// Módulos del sistema
$router->get('/modulos',               'ModuloController@index',     ['auth', 'permiso']);
$router->get('/modulos/crear',         'ModuloController@crear',     ['auth', 'permiso']);
$router->post('/modulos/crear',        'ModuloController@guardar',   ['auth', 'permiso']);
$router->get('/modulos/editar/{id}',   'ModuloController@editar',    ['auth', 'permiso']);
$router->post('/modulos/editar/{id}',  'ModuloController@actualizar',['auth', 'permiso']);

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
// IMPORTANTE: las rutas específicas van ANTES de /archivo/{id} para que el
// router no capture 'v' o 'acta' como {id}.
$router->get('/archivo/v/{id}',      'ArchivoController@porVersion', ['auth', 'permiso']);
$router->get('/archivo/acta/{id}',   'ArchivoController@acta',       ['auth', 'permiso']);
// Visor Office: sin middleware auth — Microsoft Office Online debe poder
// hacer fetch; la seguridad la da el token temporal de un solo contexto.
$router->get('/archivo/{id}/view',   'ArchivoController@verConToken');
$router->get('/archivo/{id}/ver',    'ArchivoController@ver', ['auth']);
$router->get('/archivo/{id}',        'ArchivoController@descargar', ['auth', 'permiso']);

// Imágenes institucionales (logo público para login; organigrama/mapa con sesión)
$router->get('/empresa-img/{tipo}',  'EmpresaImgController@servir');

// ───────────────────────────────────────────────────────────────────────
// §9.2 Auditoría Interna
// ───────────────────────────────────────────────────────────────────────
$router->get('/auditoria-interna',                         'AuditoriaInternaController@index',   ['auth','permiso']);
$router->get('/auditoria-interna/crear',                   'AuditoriaInternaController@crear',   ['auth','permiso']);
$router->post('/auditoria-interna/crear',                  'AuditoriaInternaController@guardar', ['auth','permiso']);
$router->get('/auditoria-interna/ver/{id}',     'AuditoriaInternaController@ver',        ['auth','permiso']);
$router->get('/auditoria-interna/editar/{id}',             'AuditoriaInternaController@editar',  ['auth','permiso']);
$router->post('/auditoria-interna/editar/{id}',            'AuditoriaInternaController@actualizar', ['auth','permiso']);
$router->post('/auditoria-interna/hallazgo/{id}',          'AuditoriaInternaController@guardarHallazgo', ['auth','permiso']);

// HALLAZGOS — AJAX procesos por programa
$router->get('/hallazgos/programa/{id}/procesos', 'HallazgoController@procesosPorPrograma', ['auth']);

// §9.2 — REPORTES AUDITORÍA
$router->get('/reportes/auditoria/planes',     'ReporteController@auditoriaPlan',      ['auth','permiso']);
$router->get('/reportes/auditoria/programas',  'ReporteController@auditoriaPrograma',  ['auth','permiso']);
$router->get('/reportes/auditoria/informes',   'ReporteController@auditoriaInforme',   ['auth','permiso']);
$router->get('/reportes/auditoria/hallazgos',  'ReporteController@auditoriaHallazgos', ['auth','permiso']);

// §9.2 — PANEL HALLAZGOS AUDITORÍA
$router->get('/auditoria/hallazgos',                         'AuditoriaHallazgosController@index',        ['auth','permiso']);
$router->post('/auditoria/hallazgos/{id}/estado',            'AuditoriaHallazgosController@cambiarEstado',['auth','permiso']);
$router->post('/auditoria/hallazgos/{id}/generar-ac',        'AuditoriaHallazgosController@generarAC',    ['auth','permiso']);

// §9.2 — INFORME DE AUDITORÍA
$router->get('/auditoria/informe',                      'AuditoriaInformeController@index',            ['auth','permiso']);
$router->get('/auditoria/informe/crear',                'AuditoriaInformeController@crear',            ['auth','permiso']);
$router->post('/auditoria/informe/crear',               'AuditoriaInformeController@guardar',          ['auth','permiso']);
$router->get('/auditoria/informe/editar/{id}',          'AuditoriaInformeController@editar',           ['auth','permiso']);
$router->post('/auditoria/informe/editar/{id}',         'AuditoriaInformeController@actualizar',       ['auth','permiso']);
$router->post('/auditoria/informe/revisar/{id}',        'AuditoriaInformeController@enviarARevision',  ['auth','permiso']);
$router->post('/auditoria/informe/aprobar/{id}',        'AuditoriaInformeController@aprobar',          ['auth','permiso']);
$router->post('/auditoria/informe/devolver/{id}',       'AuditoriaInformeController@devolver',         ['auth','permiso']);
$router->get('/auditoria/informe/programa/{id}/datos',  'AuditoriaInformeController@datosProgramaInforme', ['auth']);
$router->post('/auditoria/informe/{id}/componentes',                   'AuditoriaInformeController@guardarComponente',  ['auth','permiso']);
$router->post('/auditoria/informe/{id}/componentes/eliminar/{cid}',   'AuditoriaInformeController@eliminarComponente',['auth','permiso']);
$router->post('/auditoria/informe/{id}/distribuir',                   'AuditoriaInformeController@distribuir',         ['auth','permiso']);
$router->post('/auditoria/informe/{id}/distribucion/confirmar/{did}', 'AuditoriaInformeController@confirmarRecibo',    ['auth','permiso']);
$router->get('/auditoria/informe/{id}',                 'AuditoriaInformeController@ver',              ['auth','permiso']);

// §9.2 — PROGRAMA DE AUDITORÍA
$router->get('/auditoria/programa',                 'AuditoriaProgramaController@index',     ['auth','permiso']);
$router->get('/auditoria/programa/crear',           'AuditoriaProgramaController@crear',     ['auth','permiso']);
$router->post('/auditoria/programa/crear',          'AuditoriaProgramaController@guardar',   ['auth','permiso']);
$router->get('/auditoria/programa/editar/{id}',     'AuditoriaProgramaController@editar',    ['auth','permiso']);
$router->post('/auditoria/programa/editar/{id}',    'AuditoriaProgramaController@actualizar',['auth','permiso']);
$router->post('/auditoria/programa/aprobar/{id}',   'AuditoriaProgramaController@aprobar',   ['auth','permiso']);
$router->get('/auditoria/programa/{id}',            'AuditoriaProgramaController@ver',       ['auth','permiso']);

// §9.2 — PLAN DE AUDITORÍA
$router->get('/auditoria/plan',                   'AuditoriaPlanController@index',     ['auth','permiso']);
$router->get('/auditoria/plan/crear',             'AuditoriaPlanController@crear',     ['auth','permiso']);
$router->post('/auditoria/plan/crear',            'AuditoriaPlanController@guardar',   ['auth','permiso']);
$router->get('/auditoria/plan/editar/{id}',       'AuditoriaPlanController@editar',    ['auth','permiso']);
$router->post('/auditoria/plan/editar/{id}',      'AuditoriaPlanController@actualizar',['auth','permiso']);
$router->post('/auditoria/plan/devolver/{id}',    'AuditoriaPlanController@devolver',       ['auth','permiso']);
$router->post('/auditoria/plan/revisar/{id}',     'AuditoriaPlanController@enviarARevision',['auth','permiso']);
$router->post('/auditoria/plan/aprobar/{id}',     'AuditoriaPlanController@aprobar',   ['auth','permiso']);
$router->post('/auditoria/plan/iniciar/{id}',     'AuditoriaPlanController@iniciarEjecucion', ['auth','permiso']);
$router->post('/auditoria/plan/retornar/{id}',    'AuditoriaPlanController@retornar',  ['auth','permiso']);
$router->get('/auditoria/plan/{id}/actividades',              'AuditoriaPlanActividadController@index',     ['auth','permiso']);
$router->post('/auditoria/plan/{id}/actividades',             'AuditoriaPlanActividadController@guardar',    ['auth','permiso']);
$router->get('/auditoria/plan/{id}/actividades/editar/{act_id}',   'AuditoriaPlanActividadController@redirigir',   ['auth']);
$router->post('/auditoria/plan/{id}/actividades/editar/{act_id}',  'AuditoriaPlanActividadController@actualizar', ['auth','permiso']);
$router->get('/auditoria/plan/{id}/actividades/eliminar/{act_id}', 'AuditoriaPlanActividadController@redirigir',   ['auth']);
$router->post('/auditoria/plan/{id}/actividades/eliminar/{act_id}','AuditoriaPlanActividadController@eliminar',  ['auth','permiso']);
$router->get('/auditoria/plan/{id}/datos',   'AuditoriaPlanController@datosPlan',  ['auth']);
$router->get('/auditoria/plan/{id}',              'AuditoriaPlanController@ver',       ['auth','permiso']);

// ───────────────────────────────────────────────────────────────────────
// §9.3 Revisión por la Dirección
// ───────────────────────────────────────────────────────────────────────
$router->get('/revision-direccion',                'RevisionDireccionController@index',    ['auth','permiso']);
$router->get('/revision-direccion/crear',          'RevisionDireccionController@crear',    ['auth','permiso']);
$router->post('/revision-direccion/crear',         'RevisionDireccionController@guardar',  ['auth','permiso']);
$router->get('/revision-direccion/ver/{id}',       'RevisionDireccionController@ver',      ['auth','permiso']);
$router->get('/revision-direccion/editar/{id}',    'RevisionDireccionController@editar',   ['auth','permiso']);
$router->post('/revision-direccion/editar/{id}',   'RevisionDireccionController@actualizar', ['auth','permiso']);
$router->post('/revision-direccion/eliminar/{id}', 'RevisionDireccionController@eliminar',   ['auth','permiso']);

// ── §6.2 Objetivos de Calidad ──────────────────────────────────────────
$router->get('/objetivos-calidad',                  'ObjetivoCalidadController@index',    ['auth','permiso']);
$router->get('/objetivos-calidad/crear',            'ObjetivoCalidadController@crear',    ['auth','permiso']);
$router->post('/objetivos-calidad/crear',           'ObjetivoCalidadController@guardar',  ['auth','permiso']);
$router->get('/objetivos-calidad/editar/{id}',      'ObjetivoCalidadController@editar',   ['auth','permiso']);
$router->post('/objetivos-calidad/eliminar/{id}', 'ObjetivoCalidadController@eliminar', ['auth','permiso']);
$router->post('/objetivos-calidad/editar/{id}',     'ObjetivoCalidadController@actualizar',['auth','permiso']);
$router->post('/objetivos-calidad/medicion/{id}',   'ObjetivoCalidadController@registrarMedicion',['auth','permiso']);

// ── §10.2 Acciones Correctivas ─────────────────────────────────────────
$router->get('/acciones-correctivas',               'AccionCorrectivaController@index',    ['auth','permiso']);
$router->get('/acciones-correctivas/crear',         'AccionCorrectivaController@crear',    ['auth','permiso']);
$router->post('/acciones-correctivas/crear',        'AccionCorrectivaController@guardar',  ['auth','permiso']);
$router->get('/acciones-correctivas/ver/{id}',    'AccionCorrectivaController@ver',      ['auth','permiso']);
$router->get('/acciones-correctivas/editar/{id}',   'AccionCorrectivaController@editar',   ['auth','permiso']);
$router->post('/acciones-correctivas/editar/{id}',  'AccionCorrectivaController@actualizar',['auth','permiso']);

// ── §6.1 Gestión de Riesgos por Proceso ─────────────────────────────────
$router->get('/riesgos',                            'RiesgoController@index',     ['auth','permiso']);
$router->get('/riesgos/crear',                       'RiesgoController@crear',     ['auth','permiso']);
$router->post('/riesgos/crear',                      'RiesgoController@guardar',   ['auth','permiso']);
$router->get('/riesgos/ver/{id}',                    'RiesgoController@ver',       ['auth','permiso']);
$router->get('/riesgos/editar/{id}',                 'RiesgoController@editar',    ['auth','permiso']);
$router->post('/riesgos/editar/{id}',                'RiesgoController@actualizar',['auth','permiso']);
$router->post('/riesgos/{id}/generar-ac',            'RiesgoController@generarAC', ['auth','permiso']);

// ── §8.4 Gestión de Proveedores ─────────────────────────────────────────
$router->get('/proveedores',                          'ProveedorController@index',         ['auth','permiso']);
$router->get('/proveedores/crear',                     'ProveedorController@crear',         ['auth','permiso']);
$router->post('/proveedores/crear',                    'ProveedorController@guardar',       ['auth','permiso']);
$router->get('/proveedores/ver/{id}',                  'ProveedorController@ver',           ['auth','permiso']);
$router->get('/proveedores/editar/{id}',               'ProveedorController@editar',        ['auth','permiso']);
$router->post('/proveedores/editar/{id}',              'ProveedorController@actualizar',    ['auth','permiso']);
$router->get('/proveedores/{id}/evaluar',              'ProveedorController@evaluarForm',   ['auth','permiso']);
$router->post('/proveedores/{id}/evaluar',             'ProveedorController@evaluarGuardar',['auth','permiso']);
$router->get('/proveedores/comparativo',               'ProveedorController@comparativo',   ['auth','permiso']);

// ── §7.2/7.3 Competencia y Capacitación ─────────────────────────────────
$router->get('/competencia',                            'CompetenciaController@index',              ['auth','permiso']);
$router->get('/competencia/cargo/{id}',                 'CompetenciaController@editarPerfil',       ['auth','permiso']);
$router->post('/competencia/cargo/{id}',                'CompetenciaController@guardarPerfil',      ['auth','permiso']);
$router->get('/competencia/capacitaciones',             'CompetenciaController@capacitaciones',     ['auth','permiso']);
$router->get('/competencia/capacitaciones/crear',       'CompetenciaController@crearCapacitacion',  ['auth','permiso']);
$router->post('/competencia/capacitaciones/crear',      'CompetenciaController@guardarCapacitacion',['auth','permiso']);

// ── §10.3 Oportunidades de Mejora ───────────────────────────────────────
$router->get('/mejora',                    'OportunidadMejoraController@index',         ['auth','permiso']);
$router->get('/mejora/crear',              'OportunidadMejoraController@crear',         ['auth','permiso']);
$router->post('/mejora/crear',             'OportunidadMejoraController@guardar',       ['auth','permiso']);
$router->get('/mejora/ver/{id}',           'OportunidadMejoraController@ver',           ['auth','permiso']);
$router->get('/mejora/{id}/evaluar',       'OportunidadMejoraController@evaluarForm',   ['auth','permiso']);
$router->post('/mejora/{id}/evaluar',      'OportunidadMejoraController@evaluarGuardar',['auth','permiso']);
$router->post('/mejora/{id}/generar-ac',   'OportunidadMejoraController@generarAC',     ['auth','permiso']);

// ── Filtro Avanzado: opciones reales por categoría ──────────────────────
$router->get('/filtro-opciones/{categoria}', 'FiltroOpcionesController@opciones', ['auth']);

// ── Centro de Reportes ─────────────────────────────────────────────────
$router->get('/reportes',                          'ReporteController@index',          ['auth','permiso']);
// Alias para módulo 69 (URL antigua en BD: /reportes/doc_vigentes)
$router->get('/reportes/doc_vigentes',         'ReporteController@docVigentes',    ['auth','permiso']);
$router->get('/reportes/documentos/vigentes',      'ReporteController@docVigentes',    ['auth','permiso']);
$router->get('/reportes/documentos/obsoletos',     'ReporteController@docObsoletos',   ['auth','permiso']);
$router->get('/reportes/documentos/por-proceso',   'ReporteController@docPorProceso',  ['auth','permiso']);
$router->get('/reportes/solicitudes',              'ReporteController@solicitudes',    ['auth','permiso']);
$router->get('/reportes/tareas',                   'ReporteController@tareas',         ['auth','permiso']);
$router->get('/reportes/sgc/ejecutivo',            'ReporteController@sgcEjecutivo',   ['auth','permiso']);
$router->get('/reportes/sgc/objetivos',            'ReporteController@sgcObjetivos',   ['auth','permiso']);
$router->get('/reportes/sgc/hallazgos',            'ReporteController@sgcHallazgos',   ['auth','permiso']);
$router->get('/reportes/sgc/acciones-correctivas', 'ReporteController@sgcAcciones',    ['auth','permiso']);
$router->get('/reportes/seguridad/usuarios',     'ReporteController@segUsuarios',    ['auth','permiso']);
$router->get('/reportes/seguridad/contrasenas',  'ReporteController@segContrasenas', ['auth','permiso']);
$router->get('/reportes/seguridad/logins',         'ReporteController@segLogins',      ['auth','permiso']);

// ── Módulos nuevos de la normalización (HU pendientes) ───────────────
// Contexto Organizacional §4.1/4.2
// §4.1 DOFA
$router->get('/contexto/foda',                    'ContextoController@foda',            ['auth','permiso']);
$router->get('/contexto/foda/crear',              'ContextoController@fodaCrear',       ['auth','permiso']);
$router->post('/contexto/foda/crear',             'ContextoController@fodaGuardar',     ['auth','permiso']);
$router->get('/contexto/foda/editar/{id}',        'ContextoController@fodaEditar',      ['auth','permiso']);
$router->post('/contexto/foda/editar/{id}',       'ContextoController@fodaActualizar',  ['auth','permiso']);
$router->post('/contexto/foda/eliminar/{id}',     'ContextoController@fodaEliminar',    ['auth','permiso']);
// §4.2 Partes Interesadas
$router->get('/contexto/partes-interesadas',              'ContextoController@partes',           ['auth','permiso']);
$router->get('/contexto/partes-interesadas/crear',        'ContextoController@partesCrear',      ['auth','permiso']);
$router->post('/contexto/partes-interesadas/crear',       'ContextoController@partesGuardar',    ['auth','permiso']);
$router->get('/contexto/partes-interesadas/editar/{id}',  'ContextoController@partesEditar',     ['auth','permiso']);
$router->post('/contexto/partes-interesadas/editar/{id}', 'ContextoController@partesActualizar', ['auth','permiso']);
$router->post('/contexto/partes-interesadas/eliminar/{id}','ContextoController@partesEliminar',  ['auth','permiso']);

// Gestión de Hallazgos (HU-036)
$router->get('/hallazgos',                'HallazgoController@index',     ['auth','permiso']);
$router->get('/hallazgos/crear',         'HallazgoController@crear',     ['auth','permiso']);
$router->post('/hallazgos/crear',        'HallazgoController@guardar',   ['auth','permiso']);
$router->get('/hallazgos/{id}',           'HallazgoController@ver',       ['auth','permiso']);
$router->post('/hallazgos/{id}',          'HallazgoController@actualizar',['auth','permiso']);
$router->post('/hallazgos/cerrar/{id}',   'HallazgoController@cerrar',    ['auth','permiso']);

// Reportes adicionales
$router->get('/reportes/sgc/contexto',      'ReporteController@sgcContexto', ['auth', 'permiso']);
