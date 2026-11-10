# INFORME RA3 · Práctica 1: Usuarios, perfiles y módulos

**Módulo:** Implantación de Aplicaciones Web (0376)  
**Curso:** 2º ASIR  
**Resultados de Aprendizaje y Criterios:** RA3 (CE a, b, c)  
**Rama de trabajo:** `ra3-admin`  

---

## 👥 Sección 1 · Roles, usuarios y permisos en WordPress

### 1.1. Capacidades clave identificadas con WP-CLI
Mediante la inspección de capacidades del rol colaborador (`./wp cap list contributor`):
- **Capacidad que permite publicar entradas:** `publish_posts` *(los colaboradores carecen de ella, por lo que únicamente pueden enviar a revisión con `edit_posts`)*.
- **Capacidad que permite subir ficheros multimedia:** `upload_files` *(los colaboradores carecen de ella, bloqueando la carga de imágenes al servidor)*.

### 1.2. Tabla de perfiles, permisos y comprobación práctica

| Usuario | Rol asignado | ¿Qué puede hacer en el sitio? | ¿Qué NO puede hacer? | Comprobación en interfaz web |
|---|---|---|---|---|
| `adminSesion10` | **Administrador** | Control absoluto: gestión de plugins, temas, ajustes del CMS, creación y edición de usuarios, volcado de datos. | Sin restricciones dentro de la aplicación. | Acceso completo al menú lateral izquierdo (`Ajustes`, `Plugins`, `Apariencia`, `Usuarios`, etc.). |
| `coord.web` | **Editor** | Publicar y editar entradas y páginas de cualquier usuario, gestionar y crear categorías, moderar comentarios, subir medios. | No puede modificar ajustes generales del sistema, ni instalar/activar plugins ni cambiar temas ni gestionar usuarios. | En el menú lateral solo aparecen: `Entradas`, `Medios`, `Páginas`, `Comentarios` y `Herramientas`. Faltan `Plugins` y `Ajustes`. |
| `profe.redes` | **Autor** | Redactar, subir imágenes, editar y publicar sus propias entradas (`publish_posts`). | No puede editar ni borrar contenidos de otros autores, ni crear páginas estáticas, ni moderar comentarios ajenos. | Solo ve sus propias entradas publicadas. Al intentar acceder a la entrada de la alumna no dispone de opción de edición. |
| `alumna.fct` | **Colaboradora** | Escribir y modificar sus borradores de entradas (`edit_posts`). | No puede publicar de forma autónoma (el botón muestra *Enviar para revisión*) ni subir imágenes (sin acceso a la biblioteca multimedia). | Al crear la entrada «Mi primera semana en prácticas», el botón de acción es *Enviar para revisión* en lugar de *Publicar*. |
| `familia.garcia` | **Suscriptor** | Acceder al panel para consultar y actualizar sus datos de perfil (nombre, contraseña, correo). | No puede crear ni editar ningún contenido, ni subir ficheros ni acceder a partes administrativas. | Su menú lateral se reduce exclusivamente a la pestaña `Perfil`. |

---

### 1.3. Creación del perfil a medida: Rol «Revisor»
Para cumplir con el requerimiento de un usuario que revise y publique contenidos de terceros **sin permisos para borrar nada**, se generó un nuevo rol clonado del rol Editor y se le sustrajeron 9 capacidades críticas:

- **Comando de clonación:**
  ```bash
  ./wp role create revisor "Revisor" --clone=editor
  ```
- **Capacidades eliminadas:**
  ```bash
  ./wp cap remove revisor delete_posts delete_others_posts delete_published_posts \
   delete_private_posts delete_pages delete_others_pages delete_published_pages \
   delete_private_pages manage_categories
  ```
- **Usuario de prueba creado:** `revisor.fp` (`revisor.fp@example.com`).
- **Verificación de capacidades de borrado:**
  ```bash
  ./wp cap list revisor | grep delete || echo "Sin capacidades de borrado"
  # Salida: Sin capacidades de borrado
  ```
- **Comprobación en la interfaz web:** Al iniciar sesión con `revisor.fp`, puede revisar y publicar entradas pendientes de otros autores, pero **no aparece el botón ni el enlace «Papelera»** ni en entradas ni en páginas.

---

### 1.4. Políticas de registro y seguridad
- **Estado de registro público (`users_can_register`):** `0` (desactivado). Se impide que cualquier visitante anónimo cree cuentas en el sitio.
- **Rol por defecto para nuevos usuarios (`default_role`):** `subscriber`. Si en el futuro se habilitara temporalmente el registro, los nuevos usuarios entrarán con el mínimo privilegio posible (Suscriptor), evitando elevaciones de privilegios no deseadas.

---

## 🧩 Sección 2 · Módulos (Plugins), selección y configuración

### 2.1. Requisito 1: Formulario de contacto con almacenamiento de mensajes (R1)
- **Módulos elegidos:** `Contact Form 7` (v5.9.8) + `Flamingo` (v2.6.4).
- **Justificación técnica:** Contact Form 7 proporciona una estructura semántica ligera y segura para formularios, mientras que Flamingo intercepta las peticiones enviadas y las almacena localmente en la base de datos (tipo de contenido `flamingo_inbound`), garantizando que no se pierda ningún mensaje.
- **Configuración realizada:**
  1. En el formulario se incorporó el campo desplegable obligatorio para ciclos formativos:
     ```text
     [select ciclo "ASIR" "DAW" "SMR"]
     ```
  2. En la pestaña *Configuración adicional* se añadió:
     ```text
     skip_mail: on
     ```
     *¿Por qué?:* El entorno de contenedores no dispone de un agente de transporte de correo local (MTA/Sendmail). La directiva `skip_mail: on` evita falsos errores de envío (caja naranja) y permite que Flamingo registre la totalidad de los datos en la base de datos de MariaDB con estado de éxito (caja verde).
  3. Integración en la página `/contacto/`: Se insertó el bloque correspondiente vinculando el formulario.
- **Evidencia de funcionamiento:** Se envió un mensaje de prueba con el ciclo `ASIR` y se verificó su almacenamiento en la tabla de Flamingo mediante WP-CLI (`./wp post list --post_type=flamingo_inbound`).

---

### 2.2. Requisito 2: Aviso de consentimiento de cookies (R2)

#### Comparativa de plugins candidatos (`./wp plugin search cookie`):
| Criterio | Candidato A: Cookie Compliance (Cookie Notice) | Candidato B: Complianz GDPR/CCPA |
|---|---|---|
| **Slug** | `cookie-notice` | `complianz-gdpr` |
| **Instalaciones activas** | 800.000+ | 1.000.000+ |
| **Valoración media** | 96 / 100 | 94 / 100 |
| **Fecha última actualización** | Reciente (noviembre 2026) | Reciente (noviembre 2026) |
| **Compatibilidad y carga** | Huella mínima en el DOM, no crea tablas pesadas ni dependencias externas. | Muy potente pero excesivamente pesado para un CMS básico (asistente largo, múltiples scripts frontend). |
| **Equilibrio visual de botones** | Permite botones «Aceptar» y «Rechazar» con idéntico peso visual y estilos CSS directos. | Configuración visual más compleja para equilibrar el peso visual de rechazo. |

- **Decisión final:** Se eligió **`cookie-notice`**.
- **Configuración aplicada:**
  - Aviso redactado en español cumpliendo directrices de la AEPD y el RGPD.
  - Botón «Aceptar» y botón «Rechazar» configurados con el mismo peso visual (`#00a99d`).
  - Enlace directo a la página informativa «Política de cookies» (`/politica-de-cookies/`).
  - Almacenamiento de la preferencia en el navegador del usuario para no reincidir en la solicitud una vez aceptado o rechazado.

---

### 2.3. Requisito 3: Registro y auditoría de actividad (R3)
- **Módulo elegido:** `Simple History` (v5.36.0).
- **Instalación:** Realizada mediante WP-CLI:
  ```bash
  ./wp plugin install simple-history --activate
  ```
- **Justificación técnica:** Registra de manera detallada y visual en el panel (`Escritorio → Historial`) y en base de datos (`wp_simple_history`): inicios de sesión exitosos, intentos de autenticación fallidos con IPs asociadas, activación/desactivación de módulos y cambios en páginas y entradas.
- **Prueba práctica efectuada:**
  1. Se simuló un intento de inicio de sesión fallido con el usuario `alumna.fct` introduciendo una contraseña incorrecta.
  2. A continuación, se efectuó el login con las credenciales correctas.
  3. Se comprobó la auditoría en la base de datos confirmando el registro de ambos eventos:
     - `Failed to login with username "alumna.fct" (incorrect password entered)`
     - `Logged in`

---

### 2.4. Mantenimiento y reducción de superficie de ataque
- Se auditaron los plugins inactivos y se eliminaron de manera definitiva del almacenamiento persistente:
  ```bash
  ./wp plugin delete akismet hello
  ```
  *Justificación:* Los plugins inactivos conservan su código fuente en el sistema de archivos (`wp-content/plugins/`). Si contienen vulnerabilidades sin parchear, pueden ser invocados directamente mediante peticiones HTTP específicas sin pasar por el núcleo de WordPress. La eliminación completa es una buena práctica de hardening.
