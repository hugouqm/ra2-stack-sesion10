# Seguridad del gestor

| Comprobación | Antes | Después |
|---|---|---|
| Cabecera Server | `Server: nginx/1.30.5` (expone la versión exacta) | `Server: nginx` (versión oculta mediante `server_tokens off;`) |
| /xmlrpc.php | `HTTP/1.1 405 Method Not Allowed` (archivo accesible y procesado por PHP) | `HTTP/1.1 403 Forbidden` (bloqueado por Nginx) |
| /wp-config.php | `HTTP/1.1 200 OK` (accesible públicamente, procesado por PHP) | `HTTP/1.1 403 Forbidden` (bloqueado por Nginx) |
| PHP en uploads | Se ejecutó el fichero `test.php` (devolvió `2`) | `HTTP/1.1 403 Forbidden` (Nginx bloquea ejecución de PHP en uploads) |
| Edición de ficheros desde el panel | Habilitada (editor de temas y plugins visible en wp-admin) | Desactivada (`define('DISALLOW_FILE_EDIT', true);` en docker-compose) |
| Límite de intentos de login | Intentos ilimitados (vulnerable a ataques de fuerza bruta) | Bloqueo temporal activado tras varios intentos fallidos (Limit Login Attempts) |

## Medidas aplicadas y por qué

1. **Ocultación de la versión del servidor (`server_tokens off;`)**:
   - **Por qué**: Evita revelar la versión específica de Nginx en las cabeceras HTTP de respuesta. Esto dificulta a un atacante identificar vulnerabilidades conocidas (CVEs) asociadas a una versión concreta.

2. **Cabeceras de seguridad HTTP (`X-Content-Type-Options` y `X-Frame-Options`)**:
   - `X-Content-Type-Options: "nosniff"`: Evita que el navegador intente adivinar el tipo MIME del contenido, mitigando ataques basados en MIME confusion y subida de archivos trampa.
   - `X-Frame-Options: "SAMEORIGIN"`: Impide que páginas del sitio se carguen dentro de un `<iframe>` en dominios externos, previniendo ataques de clickjacking.

3. **Bloqueo de acceso a `xmlrpc.php` (`location = /xmlrpc.php { deny all; }`)**:
   - **Por qué**: El endpoint XML-RPC es frecuentemente explotado para ataques de amplificación DDoS (pingback attacks) y ataques de fuerza bruta distribuidos. Al bloquearlo se cierra este vector sin afectar la funcionalidad web estándar.

4. **Protección de `wp-config.php` (`location = /wp-config.php { deny all; }`)**:
   - **Por qué**: Contiene credenciales críticas de la base de datos y claves de salting de WordPress. Bloquear las peticiones directas desde Nginx añade una capa de defensa en profundidad que evita fugas de información sensible si el intérprete PHP llegase a fallar.

5. **Bloqueo de ejecución de PHP en la carpeta de uploads (`location ~* ^/wp-content/uploads/.*\.php$ { deny all; }`)**:
   - **Por qué**: La carpeta de subidas debe almacenar únicamente contenido estático (imágenes, documentos). Si un atacante o plugin vulnerable permitiese subir un fichero `.php`, Nginx rechazará su ejecución con un error 403, neutralizando la ejecución remota de código (RCE).

6. **Desactivación del editor de ficheros desde el panel (`DISALLOW_FILE_EDIT`)**:
   - **Por qué**: Si las credenciales del administrador se ven comprometidas, esta directiva impide que el atacante pueda modificar código PHP de temas o plugins directamente desde la interfaz web de administración.

7. **Principio de menor privilegio y gestión de identidades**:
   - **Usuario Editor**: Se ha creado un usuario con rol `Editor` para las operaciones cotidianas de publicación y redacción, reservando la cuenta `Administrador` únicamente para tareas de mantenimiento y configuración técnica.
   - **Ofuscación de nombre público**: Se ha establecido un nombre público («Mostrar públicamente como») distinto al nombre de login de la cuenta administrativa, dificultando la enumeración de usuarios.

8. **Protección contra fuerza bruta mediante plugin de límite de intentos**:
   - **Por qué**: Se ha instalado y activado el plugin *Limit Login Attempts Reloaded*, bloqueando temporalmente el acceso por IP tras 4 intentos fallidos de autenticación en `/wp-login.php`.

9. **Actualizaciones de seguridad**:
   - **Por qué**: Se han aplicado las actualizaciones pendientes de temas y complementos para asegurar que no persistan vulnerabilidades conocidas ya parcheadas por los desarrolladores.
