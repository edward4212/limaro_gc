# Flujo Documental — ISO 9001:2015 Cláusula 7.5
### Limaro SGC · Cooperativa Coopaipe

---

## 1. Marco Normativo

La **cláusula 7.5 de ISO 9001:2015** establece los requisitos para el control de la **información documentada** del Sistema de Gestión de la Calidad (SGC). Los tres subapartados son:

| Apartado | Título | Qué exige |
|---|---|---|
| **7.5.1** | Generalidades | La organización debe contar con la información documentada requerida por la norma y la necesaria para sus operaciones. |
| **7.5.2** | Creación y actualización | Al crear o actualizar información documentada se debe asegurar la identificación, el formato y la revisión/aprobación adecuados. |
| **7.5.3** | Control | La información documentada debe estar disponible, protegida, distribuida, almacenada, preservada y dispuesta de forma controlada. |

---

## 2. Actores del Flujo

| Actor | Rol en el SGC | Descripción |
|---|---|---|
| **Solicitante** | Cualquier empleado | Radica solicitudes de creación, actualización o eliminación de documentos. |
| **Gestor Documental** | Administrador / Líder SGC | Asigna solicitudes y tareas; supervisa el ciclo completo. |
| **Elaborador** | Experto temático | Redacta o actualiza el documento y adjunta el archivo. |
| **Revisor** | Par o supervisor | Verifica la pertinencia técnica, coherencia y cumplimiento del formato. |
| **Aprobador** | Autoridad competente | Aprueba formalmente el documento; su acción dispara la publicación. |

---

## 3. Estados de la Solicitud

```
CREADA ──► ASIGNADA ──► EN DESARROLLO ──► FINALIZADA
```

| Estado | Significado |
|---|---|
| `CREADA` | La solicitud fue radicada; aún no tiene responsable asignado. |
| `ASIGNADA` | El gestor documental asignó la solicitud a un elaborador. |
| `EN DESARROLLO` | El elaborador está trabajando en el documento. |
| `FINALIZADA` | El ciclo completo terminó con la aprobación del documento. |

---

## 4. Estados de la Tarea

```
CREADO ──► REVISION ──► APROBACION ──► FINALIZADO
              │
              └──► DEVUELTO ──► (regresa al ELABORADOR)
```

| Estado tarea | Responsable | Acción |
|---|---|---|
| `CREADO` | Elaborador | Redacta el documento y adjunta archivo. |
| `REVISION` | Revisor | Revisa el documento; puede aprobar o devolver. |
| `APROBACION` | Aprobador | Aprueba definitivamente. |
| `DEVUELTO` | Elaborador | Corrige según comentarios del revisor/aprobador. |
| `FINALIZADO` | Sistema | Resultado de la aprobación; documento publicado como VIGENTE. |
| `CAMBIO` | Sistema/Gestor | Marca de cambio de asignación. |

---

## 5. Tipos de Solicitud

| Tipo | Descripción | Resultado al aprobar |
|---|---|---|
| `CREACION` | Nuevo documento que no existía. | Se crea la versión 1 como VIGENTE. |
| `ACTUALIZACION` | Modificación de un documento vigente. | Se crea nueva versión VIGENTE; la anterior pasa a OBSOLETO. |
| `ELIMINACION` | Retiro controlado de un documento. | El documento pasa a OBSOLETO (no se elimina físicamente). |

---

## 6. Flujo Completo — Diagrama Textual

```
╔══════════════════════════════════════════════════════════════════════╗
║                     FASE 1: RADICACIÓN                              ║
║                                                                      ║
║  Solicitante accede a /solicitudes/radicar                          ║
║  Selecciona: tipo (CREACION/ACTUALIZACION/ELIMINACION)              ║
║              documento/proceso afectado                              ║
║              descripción y justificación                             ║
║                                                                      ║
║  SP: create_solicitud()  →  solicitud.estado = CREADA               ║
╚══════════════════════════════════════════════════════════════════════╝
                              │
                              ▼
╔══════════════════════════════════════════════════════════════════════╗
║                     FASE 2: ASIGNACIÓN                              ║
║                                                                      ║
║  Gestor accede a /solicitudes/gestion                               ║
║  Revisa solicitudes en estado CREADA                                ║
║  Asigna a Elaborador + Revisor + Aprobador                          ║
║                                                                      ║
║  SP: create_tarea()  →  tarea.estado = CREADO                       ║
║  solicitud.estado  →  ASIGNADA                                      ║
╚══════════════════════════════════════════════════════════════════════╝
                              │
                              ▼
╔══════════════════════════════════════════════════════════════════════╗
║                     FASE 3: ELABORACIÓN                             ║
║                                                                      ║
║  Elaborador accede a /tareas/elaborar/{id}                          ║
║  Redacta el documento según plantilla del tipo                      ║
║  Adjunta el archivo (PDF/DOCX/XLSX/PPTX, máx. 20 MB)               ║
║  Agrega comentario de avance                                         ║
║                                                                      ║
║  SP: create_tarea_estado(REVISION)                                  ║
║  SP: create_comentario_sol()                                        ║
║  solicitud.estado  →  EN DESARROLLO                                 ║
╚══════════════════════════════════════════════════════════════════════╝
                              │
                              ▼
╔══════════════════════════════════════════════════════════════════════╗
║                     FASE 4: REVISIÓN                                ║
║                                                                      ║
║  Revisor accede a /tareas/revisar/{id}                              ║
║  Descarga y evalúa el documento elaborado                           ║
║  Agrega concepto y comentarios                                       ║
║                                                                      ║
║  Opción A — Aprobar:                                                ║
║    SP: create_tarea_estado(APROBACION)                              ║
║                                                                      ║
║  Opción B — Devolver:                                               ║
║    SP: create_tarea_estado(DEVUELTO)                                ║
║    Tarea regresa al elaborador para corrección                      ║
╚══════════════════════════════════════════════════════════════════════╝
                              │ (si aprueba)
                              ▼
╔══════════════════════════════════════════════════════════════════════╗
║                     FASE 5: APROBACIÓN FINAL                        ║
║                                                                      ║
║  Aprobador accede a /tareas/aprobar/{id}                            ║
║  Revisa el documento y el historial de revisión                     ║
║  Toma decisión final                                                 ║
║                                                                      ║
║  Opción A — Aprobar (TRANSACCIÓN ATÓMICA):                          ║
║  ┌──────────────────────────────────────────────────────────────┐   ║
║  │ BEGIN TRANSACTION                                            │   ║
║  │  1. SP: createVersionamiento(estado=VIGENTE)                 │   ║
║  │         → Inserta nuevo versionamiento con archivo actual    │   ║
║  │  2. UPDATE versionamiento SET estado=OBSOLETO                │   ║
║  │         WHERE doc_id = X AND id != nuevo_id                  │   ║
║  │  3. UPDATE solicitud SET estado_solicitud = FINALIZADA       │   ║
║  │  4. SP: create_tarea_estado(FINALIZADO)                      │   ║
║  │ COMMIT                                                       │   ║
║  └──────────────────────────────────────────────────────────────┘   ║
║                                                                      ║
║  Opción B — Devolver:                                               ║
║    SP: create_tarea_estado(DEVUELTO)                                ║
║    Puede devolver al revisor o directamente al elaborador           ║
╚══════════════════════════════════════════════════════════════════════╝
                              │ (si aprueba)
                              ▼
╔══════════════════════════════════════════════════════════════════════╗
║                     RESULTADO FINAL                                 ║
║                                                                      ║
║  • Nuevo versionamiento con estado VIGENTE creado                   ║
║  • Versiones anteriores marcadas como OBSOLETO                      ║
║  • Solicitud en estado FINALIZADA                                   ║
║  • Tarea en estado FINALIZADO                                       ║
║  • Documento disponible en /documentos/vigentes                     ║
║  • Versiones anteriores visibles en /documentos/obsoletos           ║
║  • Timeline completo en /documentos/versionamiento                  ║
╚══════════════════════════════════════════════════════════════════════╝
```

---

## 7. Ciclo de Vida del Documento

```
                    ┌─────────────────────────────────┐
                    │       DOCUMENTO MAESTRO          │
                    │  (tabla: documento)              │
                    │  Código: SIGLA_TIPO-PROC-NNN     │
                    └──────────────┬──────────────────┘
                                   │
                    ┌──────────────▼──────────────────┐
                    │     VERSIONAMIENTO               │
                    │  (tabla: versionamiento)         │
                    │                                  │
                    │  v1 ──► VIGENTE                  │
                    │  (al actualizar)                 │
                    │  v1 ──► OBSOLETO                 │
                    │  v2 ──► VIGENTE                  │
                    │  (al actualizar nuevamente)      │
                    │  v2 ──► OBSOLETO                 │
                    │  v3 ──► VIGENTE  ◄── actual      │
                    └─────────────────────────────────┘
```

### Estados de Versionamiento

| Estado | Descripción | Visible en |
|---|---|---|
| `VIGENTE` | Versión actual en uso. Solo puede haber una por documento. | `/documentos/vigentes` |
| `OBSOLETO` | Versión superada. Se conserva por trazabilidad y auditoría. | `/documentos/obsoletos` |

---

## 8. Identificación de Documentos (ISO 7.5.2.a)

El sistema asigna automáticamente un **código único** a cada documento:

```
SIGLA_TIPO - SIGLA_PROCESO - NNN

Ejemplo:
  PR  = Procedimiento (tipo de documento)
  GC  = Gestión de Calidad (proceso)
  001 = Número secuencial

Resultado: PR-GC-001
```

Cada versión lleva también:
- **Número de versión** (autoincremental por documento)
- **Fecha de vigencia** (fecha de aprobación)
- **Usuario aprobador** (trazabilidad)

---

## 9. Control de Acceso y Distribución (ISO 7.5.3.b/c)

### Matriz de acceso por rol

| Rol | Radicar | Elaborar | Revisar | Aprobar | Gestión | Admin |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Administrador | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Gestor Documental | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| Elaborador | ✓ | ✓ | — | — | — | — |
| Revisor | ✓ | — | ✓ | — | — | — |
| Aprobador | ✓ | — | — | ✓ | — | — |
| Consultor | ✓ | — | — | — | — | — |

> La matriz real se configura en la interfaz de `/seguridad/roles/permisos/{id}` y se almacena en la tabla `rol_modulo`.

### Descarga segura de archivos

Los archivos **no son accesibles directamente** por URL. Toda descarga pasa por `/archivo/{id}`, que:
1. Verifica que el usuario esté autenticado.
2. Verifica que el usuario tenga permiso al módulo correspondiente.
3. Registra la descarga en la tabla `auditoria`.
4. Envía el archivo con headers apropiados.

---

## 10. Preservación y Retención (ISO 7.5.3.d/e/f)

| Requisito | Implementación |
|---|---|
| **Preservación** | Archivos físicos en `/public/storage/documentos/` con nombre único (UUID + extensión). Registro en tabla `archivo`. |
| **Retención** | Los documentos obsoletos nunca se eliminan. Se marcan como `OBSOLETO` y permanecen disponibles para consulta y auditoría. |
| **Disposición** | La eliminación de un documento requiere una **solicitud de tipo `ELIMINACION`** que pasa por el mismo flujo de aprobación. Solo tras la aprobación el documento queda en estado OBSOLETO. |

---

## 11. Trazabilidad y Auditoría

Cada acción crítica registra una entrada en la tabla `auditoria`:

| Campo | Descripción |
|---|---|
| `usuario_id` | Usuario que realizó la acción |
| `accion` | Descripción de la acción (ej. `APROBAR_TAREA`, `LOGIN`) |
| `tabla_afectada` | Tabla sobre la que actuó |
| `registro_id` | ID del registro afectado |
| `ip_address` | IP del cliente |
| `created_at` | Timestamp de la acción |

---

## 12. Procedimientos de Emergencia

### Documento urgente sin ciclo completo
En casos excepcionales, el Gestor Documental puede:
1. Crear el documento directamente en `/empresa/documentos` con estado inicial.
2. Adjuntar el archivo vía versionamiento manual.
3. Registrar en `auditoria` la justificación de excepción (campo comentario).

> Esta excepción debe quedar documentada y ser aprobada formalmente a posteriori.

### Recuperación de versión anterior
1. Acceder a `/documentos/versionamiento`.
2. Buscar la versión deseada en el timeline.
3. Radicar una **solicitud de ACTUALIZACION** adjuntando el archivo de la versión anterior como nueva versión.

---

## 13. Correspondencia con ISO 9001:2015 Cláusula 7.5

### 7.5.1 — Generalidades

| Requisito | Cumplimiento |
|---|---|
| Información documentada requerida por la norma | Tipos de documento configurables: Procedimientos, Instructivos, Formatos, Políticas, etc. |
| Información documentada determinada por la organización | Módulo Datos Empresa: macroprocesos, procesos, tipos y documentos propios de Coopaipe. |

### 7.5.2 — Creación y actualización

| Requisito | Cumplimiento |
|---|---|
| Identificación y descripción | Código único, título, descripción, proceso, tipo. |
| Formato y medios | Soporte digital: PDF, DOCX, XLSX, PPTX. |
| Revisión y aprobación | Flujo obligatorio: Elaborar → Revisar → Aprobar antes de publicar. |

### 7.5.3 — Control de la información documentada

| Subrequisito | Cumplimiento |
|---|---|
| **a) Disponible y adecuada** | Documentos VIGENTE accesibles desde el menú, buscables por proceso/tipo. |
| **b) Protección adecuada** | Control de acceso por rol; descarga solo a usuarios autenticados. |
| **c) Distribución, acceso y uso** | Permisos granulares por módulo y rol; acceso por consulta o descarga según perfil. |
| **d) Almacenamiento y preservación** | Archivos físicos únicos + registro en BD + historial de versiones. |
| **e) Control de cambios** | Toda modificación requiere una solicitud formal y trazabilidad completa. |
| **f) Conservación y disposición** | Obsoletos conservados; eliminación solo por proceso de solicitud aprobada. |

---

## 14. Glosario

| Término | Definición |
|---|---|
| **Documento maestro** | Registro principal en tabla `documento`. Contiene metadatos permanentes. |
| **Versionamiento** | Instancia concreta de un documento en un momento dado. Tiene archivo adjunto y estado (VIGENTE/OBSOLETO). |
| **Solicitud** | Petición formal de cambio documental. Inicia el flujo. |
| **Tarea** | Unidad de trabajo asignada a un actor específico (elaborar, revisar, aprobar). |
| **Aprobación transaccional** | Operación atómica que simultáneamente publica la nueva versión y obsoletiza la anterior. |
| **Acuerdo** | Documento de tipo contractual o convenio gestionado en módulo separado. |
| **Macroproceso** | Agrupación de procesos de mayor nivel organizacional. |
| **Proceso** | Conjunto de actividades interrelacionadas que transforma entradas en salidas. |
| **Tipo de documento** | Clasificación del documento (Procedimiento, Instructivo, Formato, Política, etc.). |

---

*Limaro SGC — Cooperativa Coopaipe · ISO 9001:2015 Cláusula 7.5 · Versión 1.0 · 2026*
