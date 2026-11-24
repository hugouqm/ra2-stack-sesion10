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



---

## 💾 Sección 3 · Copias de seguridad automatizadas y prueba de restauración (Práctica 2 · CE d)

### 3.1. Estrategia de copias de seguridad del stack
Para garantizar la continuidad del servicio y poder reconstruir el sitio web en cualquier equipo con Docker sin arrastrar archivos innecesarios ni duplicar el núcleo:
- **Base de datos (`db_data`):** Se realiza un volcado lógico con `mariadb-dump --single-transaction --no-tablespaces` comprimido al vuelo en `backups/db_AAAAMMDD-HHMMSS.sql.gz`. Contiene todas las tablas relacionales (usuarios, páginas, entradas, opciones, mensajes de Flamingo y auditoría de Simple History).
- **Archivos dinámicos (`wp_data`):** Se empaqueta exclusivamente el directorio `wp-content/` en `backups/wp-content_AAAAMMDD-HHMMSS.tar.gz`. El núcleo de WordPress (`wp-admin`, `wp-includes`) no se copia porque ya viene provisto por la imagen oficial del contenedor Docker.
- **Rotación:** El script conserva únicamente las últimas 7 copias (`$CONSERVAR=7`), purgando automáticamente las copias obsoletas para no saturar el espacio en disco.

---

### 3.2. Prueba de desastre controlado y restauración
Para verificar que las copias son realmente recuperables, se ejecutó una simulación de desastre:

1. **Copia previa al desastre:**
   ```bash
   ./scripts/backup.sh
   # Generó backups/db_20261118-170720.sql.gz y backups/wp-content_20261118-170720.tar.gz
   ```

2. **Provocación del desastre:**
   - **Borrado forzado de entrada:** Se eliminó la entrada ID 54 (*«Configuración de VLANs y direccionamiento»*) omitiendo la papelera:
     ```bash
     ./wp post delete 54 --force
     ```
   - **Eliminación de plugin crítico:** Se eliminó por completo el plugin `flamingo`:
     ```bash
     ./wp plugin delete flamingo
     ```
   - **Comprobación:** La entrada desapareció del listado de posts y el plugin Flamingo dejó de estar disponible en el panel.

3. **Ejecución de la restauración:**
   - Se ejecutó el script de recuperación indicando la marca temporal de la copia y confirmando la acción:
     ```bash
     ./scripts/restore.sh 20261118-170720
     ```
   - El script volcó el fichero SQL en MariaDB, reemplazó el directorio `wp-content` y reasignó la propiedad al usuario `www-data:www-data`.

4. **Verificación post-restauración:**
   - La entrada ID 54 reapareció en `./wp post list` con todo su contenido intacto.
   - El plugin `flamingo` reapareció activo en `./wp plugin list`.
   - Se comprobó en la base de datos que el mensaje de contacto recibido en la práctica anterior (*«Consulta matricula ASIR»*) se restauró íntegramente.

---

### 3.3. Automatización de copias en el aula
En el entorno Linux del aula se programa la ejecución automática mediante el demonio `cron`:
```bash
# Editar tareas programadas
crontab -e

# Línea añadida (ejecución martes y miércoles al terminar la clase a las 13:30):
30 13 * * 2,3 cd $HOME/iaw && ./scripts/backup.sh >> backups/backup.log 2>&1
```


---

## 📦 Sección 4 · Importar y exportar contenidos en distintos formatos (Práctica 3 · CE e)

### 4.1. Análisis de los cuatro formatos y sus casos de uso
| Formato | ¿Qué contiene? | Caso de uso principal | Ventajas y limitaciones |
|---|---|---|---|
| **WXR (WordPress eXtended RSS / XML)** | Entradas, páginas, categorías, etiquetas, autores, menús, comentarios y metadatos. | Mover contenidos entre distintas instalaciones de WordPress. | **Ventaja:** Independiente de la base de datos o prefijos de tabla. **Limitación:** Solo sirve entre CMS WordPress; no incluye plugins ni configuración general del servidor. |
| **CSV (Comma-Separated Values)** | Estructura tabular plana con cabecera y una fila por cada registro. | Importar o exportar usuarios y listados masivos desde hojas de cálculo. | **Ventaja:** Compatible con Excel, Calc y scripts de automatización. **Limitación:** No puede representar estructuras jerárquicas complejas o metadatos anidados sin serialización. |
| **JSON (REST API / WP-CLI)** | Datos estructurados en pares clave-valor estándar. | Interoperabilidad e integración con aplicaciones externas, apps móviles o frontend desacoplado (*headless*). | **Ventaja:** Estándar web universal, ligero y legible por máquinas y navegadores. **Limitación:** Mayor tamaño relativo que un volcado binario. |
| **SQL** | Instrucciones DDL/DML (`CREATE TABLE`, `INSERT INTO`) de la base de datos completa o tablas concretas. | Copias de seguridad completas (DRP) y migraciones de servidor idéntico. | **Ventaja:** Copia exacta al 100 % del estado del gestor. **Limitación:** Alto riesgo si se filtra (incluye credenciales y hashes) y dependencia del motor MariaDB/MySQL. |

---

### 4.2. Exportación del sitio en formato WXR (Paso 2)
Se generó el archivo de exportación WXR completo mediante WP-CLI sin necesidad de interactuar manualmente con la interfaz gráfica:
```bash
mkdir -p exportaciones
./wp export --stdout > exportaciones/sitio_$(date +%Y%m%d).xml
```
- **Conteo de elementos exportados:** `31` bloques `<item>` detectados.
- **Desglose por tipo de contenido (`wp:post_type`):**
  - `page`: 6 páginas (Inicio, Sobre el proyecto, Tecnologías, Contacto, Política de cookies, etc.).
  - `post`: 5 entradas de noticias y novedades.
  - `attachment`: 3 imágenes y medios adjuntos.
  - `nav_menu_item` / `wp_navigation`: 5 elementos de menú de navegación.
  - `wpcf7_contact_form`: 1 formulario de contacto Contact Form 7.
  - `flamingo_inbound` / `flamingo_contact`: 9 registros de mensajes y contactos guardados.

---

### 4.3. Importación de contenidos y usuarios (Pasos 3 y 4)
- **Plugin importador oficial:** Se instaló y activó `wordpress-importer` mediante WP-CLI (`./wp plugin install wordpress-importer --activate`).
- **Importación de contenidos WXR (`contenidos_asir.xml`):**
  - Comando ejecutado: `./wp import /datos/contenidos_asir.xml --authors=create`.
  - La directiva `--authors=create` analiza los autores definidos en el XML; si un autor ya existe (ej. `coord.web`), le reasigna las entradas automáticamente, y si no existe, crea el usuario correspondiente.
  - Resultado: Se importaron 6 entradas temáticas (en categorías *Noticias*, *Eventos*, *Prácticas*) y 2 páginas estáticas («Horario del ciclo» y «Normas del aula técnica»).
- **Importación de usuarios vía CSV (`usuarios.csv`):**
  - Se utiliza el comando `./wp user import-csv /datos/usuarios.csv`.
  - **Actualización vs duplicación:** Si el usuario ya existe en WordPress (como `coord.web`, `profe.redes`, `alumna.fct`, `familia.garcia`), WP-CLI actualiza sus metadatos (`updated`) sin duplicar la cuenta ni modificar sus contraseñas. Los nuevos usuarios (`profe.bd`, `alumno.fct`, `exalumno.2025`) se crean (`created`).
  - **Seguridad en el CSV:** El archivo CSV **nunca incluye contraseñas** en texto plano. WordPress genera automáticamente una contraseña aleatoria y segura para cada usuario nuevo importado.
- **Verificación en el CMS:**
  ```text
  ID  post_type  post_title
  101 post       Jornada de puertas abiertas del ciclo ASIR
  102 post       Charla: contenedores en producción
  103 post       Nuevo armario de comunicaciones en el aula
  104 post       Práctica: el stack de WordPress en contenedores
  105 post       Empresas colaboradoras para la formación en empresa
  106 post       Hackathon de ciberseguridad
  107 page       Horario del ciclo
  108 page       Normas del aula técnica
  ```
  Total de usuarios tras la importación: 11 usuarios registrados en el sistema.

---

### 4.4. Exportación en listados planos (CSV) y estructurados (JSON) (Paso 5)
Se generaron listados selectivos usando el modificador universal `--format`:
```bash
# Exportar listado de entradas a CSV
./wp post list --post_type=post --fields=ID,post_title,post_date,post_status --format=csv > exportaciones/entradas.csv

# Exportar listado de usuarios a JSON
./wp user list --fields=user_login,user_email,roles --format=json > exportaciones/usuarios.json
```
Ambos ficheros son inmediatamente legibles por herramientas de ofimática (LibreOffice Calc con codificación UTF-8 y delimitador coma) y sistemas de procesamiento de datos en JSON.

---

### 4.5. Consumo de la API REST nativa de WordPress (Paso 6)
WordPress expone públicamente sus puntos finales de lectura bajo el espacio de nombres `/wp-json/wp/v2/`:
```bash
# Consultar las tres últimas entradas públicas
curl -s "http://localhost:8080/wp-json/wp/v2/posts?per_page=3&_fields=id,title,link"

# Consultar los autores públicos del sitio
curl -s "http://localhost:8080/wp-json/wp/v2/users?_fields=id,name"
```
- **¿Por qué funciona sin autenticación?:** Los artículos publicados y los nombres de sus autores son información pública por naturaleza en la web. La API REST permite que lectores RSS, aplicaciones móviles o sitios externos consuman estos datos sin credenciales.

---

### 4.6. Seguridad: exportación SQL y análisis de riesgos (Paso 7)
Se exportó de forma aislada la tabla de usuarios del CMS:
```bash
docker compose exec -T db sh -c 'exec mariadb-dump --no-tablespaces -u"$WP_DB_USER" -p"$WP_DB_PASSWORD" "$WP_DB_NAME" wp_users' > exportaciones/wp_users.sql
```
- **Hashes detectados en el volcado:**
  Al inspeccionar el fichero se comprueba que las contraseñas están protegidas mediante funciones criptográficas de un solo sentido (bcrypt, prefijo `$wp$2y$10$...` o phpass `$P$...`).
- **Riesgo asociado:**
  Aunque las contraseñas no se almacenan en texto plano, un atacante que consiga acceso al archivo `.sql` puede realizar ataques de fuerza bruta offline o ataques por diccionario con herramientas como *Hashcat* sin ser detectado por el cortafuegos ni por el plugin `limit-login-attempts`.
- **Medida preventiva:**
  Las carpetas `backups/` y `exportaciones/` están **estrictamente ignoradas en `.gitignore`** para asegurar que ningún archivo con volcados SQL, datos personales de usuarios o mensajes de formularios se suba jamás al repositorio público en GitHub.

---

## 🎨 Sección 5 · Plantillas, temas hijo y edición del sitio (FSE) (Práctica 4 · CE f)

### 5.1. Conceptos clave de la edición completa del sitio (FSE)
| Concepto | Definición técnica | Implementación en Twenty Twenty-Five / IAW Hijo |
|---|---|---|
| **Tema** | Conjunto completo de estilos, plantillas y lógica visual del CMS. | `Twenty Twenty-Five` (tema padre) y `iaw-hijo` (tema hijo). |
| **Plantilla (`wp_template`)** | Estructura que define cómo se muestra un tipo específico de contenido. | `single` (entradas individuales), `page` (páginas), `wp-custom-template-pagina-informativa` (plantilla personalizada). |
| **Parte de plantilla (`wp_template_part`)** | Componente modular y reutilizable incrustado en varias plantillas. | `header` (cabecera), `footer` (pie de página). |
| **Patrón** | Composición prediseñada de bloques que puede insertarse y modificarse en contenidos o plantillas. | Patrones integrados de Twenty Twenty-Five para navegación y comentarios. |
| **Estilos globales (`theme.json`)** | Definición jerárquica de variables CSS, colores, tipografías y espaciados sin tocar PHP ni CSS manual. | Configuración en `temas/iaw-hijo/theme.json` con paleta corporativa ASIR (fondo `#F4F7FA`, texto `#21295C`, enlaces `#065A82`). |

---

### 5.2. Creación del tema hijo `iaw-hijo`
Se generó el tema hijo utilizando WP-CLI:
```bash
./wp scaffold child-theme iaw-hijo --parent_theme=twentytwentyfive --theme_name="IAW Hijo" --activate
```
- **Estructura del tema hijo:**
  - `style.css`: Cabecera declarativa indicando `Template: twentytwentyfive`.
  - `functions.php`: Encolado de estilos del padre e hijo y filtro de red para peticiones locales.
  - `theme.json`: Herencia y sobreescritura de estilos globales (colores de fondo, textos, títulos y enlaces).
- **Ubicación y versionado:**
  - El tema queda versionado en el repositorio en la carpeta `temas/iaw-hijo/` y sincronizado en el volumen de WordPress en `/var/www/html/wp-content/themes/iaw-hijo/`.

---

### 5.3. Modificación de plantillas y partes de plantilla
1. **Plantilla de entradas individuales (`single`):**
   - Se incorporó bajo el título el bloque de autor (`core/post-author`) y el bloque de taxonomías/categorías (`core/post-terms` para `category`).
2. **Parte de plantilla de pie de página (`footer`):**
   - Se sustituyó el texto genérico por: `Ciclo ASIR · Implantación de Aplicaciones Web · 2026`.
   - Se añadió el bloque de iconos sociales con el icono RSS enlazando al canal `/feed/`.
3. **Plantilla personalizada («Página informativa»):**
   - Creada como `wp-custom-template-pagina-informativa`. Contiene cabecera, título y contenido a ancho amplio sin barras laterales ni comentarios, cerrando con el pie.
   - Asignada a la página *«Horario del ciclo»* (ID 107).

---

### 5.4. Persistencia: ¿Dónde se guardan las personalizaciones?
Al consultar con WP-CLI las entradas especiales:
```bash
./wp post list --post_type=wp_template,wp_template_part --fields=ID,post_name,post_type,post_modified
```
- **Salida obtenida:**
  ```text
  ID   post_name                              post_type         post_modified
  113  single                                 wp_template       2026-11-24 16:14:07
  114  footer                                 wp_template_part  2026-11-24 16:14:07
  115  wp-custom-template-pagina-informativa  wp_template       2026-11-24 16:14:07
  ```
- **Conclusión técnica fundamental:**
  - Las ediciones visuales realizadas en el editor del sitio **NO modifican los ficheros físicos del tema** (en `wp-content/themes/iaw-hijo/` solo permanecen `style.css`, `functions.php` y `theme.json`).
  - Las plantillas modificadas se guardan en la **base de datos MariaDB** (`wp_posts` con `post_type=wp_template` o `wp_template_part`).
  - **Consecuencia para la administración de sistemas:** El script de copia de seguridad de la base de datos (`./scripts/backup.sh`) preserva intacto todo el diseño y las plantillas creadas.

---

## 📡 Sección 6 · Sindicación de contenidos (RSS y Atom) (Práctica 4 · CE g)

### 6.1. Comparativa técnica entre RSS 2.0 y Atom 1.0 (con ejemplos reales del sitio)
A partir de la inspección de los canales `http://localhost:8080/feed/` y `http://localhost:8080/feed/atom/`:

| Característica | RSS 2.0 | Atom 1.0 | Ejemplo real extraído de nuestro sitio |
|---|---|---|---|
| **Elemento raíz** | `<rss version="2.0"><channel>` | `<feed xmlns="http://www.w3.org/2005/Atom">` | RSS abre con `<rss version="2.0">`, Atom con `<feed>`. |
| **Elemento por entrada** | `<item>` | `<entry>` | `<item>` engloba cada post en RSS; `<entry>` en Atom. |
| **Formato de fecha** | `<pubDate>` (RFC 822 / RFC 2822) | `<updated>` / `<published>` (ISO 8601) | RSS: `Tue, 10 Nov 2026 15:30:25 +0000`<br>Atom: `2026-11-10T15:30:25Z` |
| **Identificador unívoco** | `<guid>` (opcional) | `<id>` (obligatorio) | RSS: `<guid isPermaLink="false">http://localhost:8080/?p=54</guid>`<br>Atom: `<id>http://localhost:8080/?p=54</id>` |
| **Organismo / Estándar** | Especificación Harvard / RSS Advisory Board | RFC 4287 (IETF) | Atom es un estándar formal de Internet más estricto con espacios de nombres XML. |

---

### 6.2. Configuración y optimización de los canales
Se ajustaron los parámetros de publicación mediante WP-CLI:
```bash
./wp option set posts_per_rss 5
./wp option set rss_use_excerpt 1
```
- **Verificación:**
  - Elementos emitidos en el feed general: `5` entradas (`curl -s http://localhost:8080/feed/ | grep -c '<item>'` devuelve `5`).
  - Bloques de contenido completo: `0` bloques `content:encoded` (`grep -c 'content:encoded'` devuelve `0`).
- **Justificación técnica:** Publicar únicamente el extracto (`excerpt`) reduce el consumo de ancho de banda del servidor web, previene el raspado automático de contenido íntegro y fomenta las visitas al portal para leer el artículo completo.

---

### 6.3. Sindicación como productor y como consumidor
1. **Integración como productor (Emisión de contenidos):**
   - Icono RSS en el pie del sitio enlazando a `/feed/`.
   - Enlace directo en el menú de navegación para la suscripción temática a eventos: `/category/eventos/feed/`.
2. **Integración como consumidor (Agregación externa y cruzada):**
   - Se publicó la página `/noticias-del-sector/` con dos bloques RSS activos:
     1. **Canal oficial de noticias:** `https://wordpress.org/news/feed/` (5 noticias con fecha y extracto).
     2. **Canal cruzado de red/aula:** `http://10.249.96.38:8080/feed/` (sindicación de entradas entre puestos del aula mediante IP de red local, habilitada a través del filtro `http_request_host_is_external` en `functions.php`).

