# Limaro SGC — Sistema de Gestión Documental
### Cooperativa Coopaipe · ISO 9001:2015 Cláusula 7.5

---

## Descripción

**Limaro SGC** es un MVP para la gestión documental de la Cooperativa Coopaipe, desarrollado en **PHP 8+ puro** con arquitectura **MVC propia** (sin framework externo). Cubre íntegramente los requisitos de control de la información documentada establecidos en la cláusula **7.5 de la norma ISO 9001:2015**.

---

## Requisitos del Servidor

| Componente | Versión mínima |
|---|---|
| PHP | 8.0+ |
| MySQL / MariaDB | 8.0+ / 11.4+ |
| Servidor web | Apache 2.4+ con `mod_rewrite` habilitado |
| Extensiones PHP | `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `gd` |

> **Nota:** No se requiere Composer. El autoloader PSR-4 está implementado directamente en `public/index.php`.

---

## Instalación

### 1. Clonar o descomprimir el proyecto

```bash
# Clonar
git clone https://github.com/tu-org/limaro_sgc.git /var/www/limaro_sgc

# O descomprimir
unzip limaro_sgc.zip -d /var/www/limaro_sgc
```

### 2. Crear la base de datos y ejecutar migraciones

```sql
CREATE DATABASE limarocloud_limaro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
mysql -u root -p limarocloud_limaro < sql/01_migracion_correctiva.sql
mysql -u root -p limarocloud_limaro < sql/02_seed_inicial.sql
```

> **IMPORTANTE:** No modifiques los archivos SQL. La migración incluye estructura completa, stored procedures y datos semilla.

### 3. Configurar variables de entorno

```bash
cp .env.example .env
nano .env
```

Edita los siguientes valores obligatoriamente:

```dotenv
APP_URL=http://localhost/limaro_sgc/public
APP_ENV=production
APP_DEBUG=false

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=limarocloud_limaro
DB_USER=tu_usuario
DB_PASS=tu_contraseña

AES_KEY=kddbjw8b3d
SESSION_NAME=LIMARO_SGC
```

### 4. Configurar Apache — DocumentRoot

```apache
<VirtualHost *:80>
    ServerName limaro.local
    DocumentRoot /var/www/limaro_sgc/public

    <Directory /var/www/limaro_sgc/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

> Asegúrate que `mod_rewrite` esté habilitado: `a2enmod rewrite && systemctl restart apache2`

### 5. Permisos de escritura (storage)

```bash
chmod -R 775 /var/www/limaro_sgc/public/storage
chown -R www-data:www-data /var/www/limaro_sgc/public/storage
```

### 6. Acceder al sistema

Navega a la URL configurada en `APP_URL` y usa las credenciales iniciales:

| Campo | Valor |
|---|---|
| Usuario | `admin` |
| Contraseña | `Admin2026*` |

> **Cambia la contraseña del administrador inmediatamente** después del primer acceso.

---

## Estructura de Carpetas

```
limaro_sgc/
├── app/
│   ├── controllers/          # Controladores MVC
│   ├── core/                 # Núcleo del framework (Router, Controller, Model, Database, Auth...)
│   ├── helpers/              # Funciones globales (format, upload, audit)
│   ├── middlewares/          # AuthMiddleware, PermisoMiddleware
│   ├── models/               # Modelos con toda la lógica SQL (PDO)
│   └── views/                # Vistas PHP (layouts, partials, módulos)
├── config/
│   ├── config.php            # Constantes de la aplicación
│   ├── database.php          # Singleton PDO
│   └── routes.php            # Registro de rutas
├── docs/
│   └── FLUJO_ISO_7.5.md      # Flujo documental ISO 9001:2015 cl. 7.5
├── public/
│   ├── index.php             # Front controller + autoloader PSR-4
│   ├── .htaccess             # Rewrite rules
│   ├── assets/
│   │   ├── css/app.css       # Paleta Limaro (azul #1e5fbf + naranja #ff7a00)
│   │   └── js/app.js         # DataTables, sidebar, autocomplete
│   └── storage/
│       ├── documentos/       # Archivos de documentos cargados
│       ├── acuerdos/         # Archivos de acuerdos
│       └── organigrama/      # Foto de perfil de usuarios
├── sql/
│   ├── 01_migracion_correctiva.sql
│   └── 02_seed_inicial.sql
├── .env                      # Variables de entorno (NO comitear)
├── .env.example              # Plantilla de variables de entorno
└── README.md
```

---

## Módulos y URLs

### Autenticación
| Ruta | Descripción |
|---|---|
| `/auth/login` | Formulario de acceso |
| `/auth/logout` | Cerrar sesión |

> Login bloqueado 15 minutos tras 5 intentos fallidos.

---

### Dashboard
| Ruta | Descripción |
|---|---|
| `/home` | Panel principal con KPIs: documentos vigentes, solicitudes activas, tareas pendientes |

---

### Datos Empresa
| Ruta | Descripción |
|---|---|
| `/macroprocesos` | CRUD de macroprocesos |
| `/procesos` | CRUD de procesos (vinculados a macroproceso) |
| `/tipo-documentos` | CRUD de tipos de documento |
| `/documentos` | CRUD de documentos maestros |
| `/acuerdos` | CRUD de acuerdos (contratos, convenios) |

---

### Documentos
| Ruta | Descripción |
|---|---|
| `/documentos/vigentes` | Listado de documentos en estado VIGENTE |
| `/documentos/obsoletos` | Listado de documentos obsoletos |
| `/documentos/versionamiento` | Timeline de versiones por documento |
| `/documentos/acuerdos-vigentes` | Acuerdos activos |

---

### Solicitudes
| Ruta | Descripción |
|---|---|
| `/solicitudes/radicar` | Formulario radicar solicitud (CREACION / ACTUALIZACION / ELIMINACION) |
| `/solicitudes/mis-radicadas` | Solicitudes propias del usuario |
| `/solicitudes/gestion` | Gestión y asignación de solicitudes (admin/gestor) |
| `/solicitudes/finalizadas` | Solicitudes finalizadas |
| `/solicitudes/ver/{id}` | Detalle de una solicitud |

---

### Tareas
| Ruta | Descripción |
|---|---|
| `/tareas` | Mis tareas asignadas |
| `/tareas/elaborar/{id}` | Elaborar documento (adjuntar archivo) |
| `/tareas/revisar/{id}` | Revisión con comentarios |
| `/tareas/aprobar/{id}` | Aprobación final (transacción ISO) |
| `/tareas/devueltas` | Tareas devueltas para corrección |
| `/tareas/finalizadas` | Tareas completadas |

---

### Seguridad
| Ruta | Descripción |
|---|---|
| `/seguridad/usuarios` | CRUD de usuarios (vía SP `create_usuario`) |
| `/seguridad/cargos` | CRUD de cargos |
| `/seguridad/manual-funciones` | Manual de funciones por cargo |
| `/seguridad/roles` | CRUD de roles y matriz de permisos por módulo |

---

### Perfil
| Ruta | Descripción |
|---|---|
| `/perfil` | Mi perfil (datos + foto) |
| `/perfil/cambiar-clave` | Cambio de contraseña (AES_ENCRYPT) |

---

## Flujo Principal: Solicitud → Tarea → Aprobación

```
Usuario radica solicitud
        │
        ▼
[CREADA] ──► Gestor asigna a elaborador ──► [ASIGNADA]
        │
        ▼
Elaborador adjunta documento ──► [EN DESARROLLO]
        │
        ▼
Revisor revisa y emite concepto
  ├── Aprueba ──► Pasa a Aprobación
  └── Devuelve ──► Regresa al elaborador (estado DEVUELTO)
        │
        ▼
Aprobador da aprobación final
        │
        ▼  (TRANSACCIÓN ATÓMICA)
  ┌─────────────────────────────────────────────────┐
  │ 1. createVersionamiento(VIGENTE) — nueva versión│
  │ 2. Obsoletizar versión anterior                 │
  │ 3. Solicitud → FINALIZADA                       │
  │ 4. create_tarea_estado(FINALIZADO)              │
  └─────────────────────────────────────────────────┘
        │
        ▼
[FINALIZADA] — Documento VIGENTE disponible en listado
```

Ver diagrama detallado en [`docs/FLUJO_ISO_7.5.md`](docs/FLUJO_ISO_7.5.md).

---

## Seguridad Implementada

| Mecanismo | Implementación |
|---|---|
| CSRF | Token en todos los formularios POST (`csrfField()`) |
| SQL Injection | PDO con prepared statements en todos los modelos |
| XSS | `htmlspecialchars()` con `ENT_QUOTES\|ENT_HTML5` en vistas |
| Contraseñas | `AES_ENCRYPT(clave, 'kddbjw8b3d')` en MySQL |
| Sesiones | `session_regenerate_id(true)` tras login; cookie `httpOnly`, `Secure`, `SameSite=Lax` |
| Headers HTTP | `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin` |
| Bloqueo login | 5 intentos fallidos → bloqueado 15 minutos |
| Auditoría | Tabla `auditoria` con usuario, acción, IP, timestamp |
| Acceso a archivos | Descarga vía controlador autenticado (`/archivo/{id}`) — no ruta directa |

---

## Cumplimiento ISO 9001:2015 — Cláusula 7.5

| Requisito ISO | Implementación SGC |
|---|---|
| 7.5.1 — Generalidades | Documentos clasificados por tipo, proceso y macroproceso |
| 7.5.2 — Creación y actualización | Flujo solicitud → elaboración → revisión → aprobación |
| 7.5.2 — Identificación | Código único `SIGLA_TIPO-SIGLA_PROCESO-NNN` |
| 7.5.2 — Revisión y aprobación | Roles definidos: Elaborador, Revisor, Aprobador |
| 7.5.3 — Control | Estados VIGENTE/OBSOLETO, versionamiento con timeline |
| 7.5.3 — Disponibilidad | Documentos vigentes accesibles con descarga segura |
| 7.5.3 — Protección | Control de acceso por rol + módulo (tabla `rol_modulo`) |
| 7.5.3 — Preservación | Archivos físicos en storage + registro en BD |
| 7.5.3 — Retención | Documentos obsoletos conservados (no eliminados) |
| 7.5.3 — Disposición | Proceso de eliminación controlado por solicitud |

---

## Tecnologías Utilizadas

- **Backend:** PHP 8+ puro (sin framework) · PDO · Stored Procedures MySQL
- **Frontend:** Bootstrap 5.3 · DataTables 1.13 · Bootstrap Icons
- **Base de datos:** MariaDB 11.4 / MySQL 8+
- **Servidor:** Apache 2.4 + mod_rewrite

---

## Notas de Desarrollo

- Toda la lógica SQL reside en los **Models** — los controladores no contienen SQL inline.
- Los stored procedures (`create_usuario`, `create_solicitud`, `create_tarea`, etc.) encapsulan la lógica de negocio crítica.
- El código de documento sigue el patrón `SIGLA_TIPO-SIGLA_PROCESO-001` (ej. `PR-GC-001`).
- El sidebar se construye dinámicamente desde las tablas `modulo` y `rol_modulo` según el rol del usuario autenticado.
- Tamaño máximo de archivo: **20 MB**.
- Tipos de archivo permitidos: `pdf`, `docx`, `xlsx`, `pptx`, `jpg`, `png`.

---

*Limaro SGC — Cooperativa Coopaipe · Versión MVP 1.0 · 2026*
