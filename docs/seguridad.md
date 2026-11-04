# Seguridad del gestor

| Comprobación | Antes | Después |
|---|---|---|
| Cabecera Server | Visible (nginx/x.x.x) | Oculta (nginx) |
| /xmlrpc.php | 200 OK | 403 Forbidden |
| /wp-config.php | Accesible | 403 Forbidden |
| PHP en uploads | Ejecuta código (2) | 403 Forbidden |
| Edición de ficheros desde el panel | Habilitada | Deshabilitada |
| Límite de intentos de login | Sin límite | Bloqueo activo |

## Medidas aplicadas y por qué
- **server_tokens off;**: Oculta la versión de Nginx en las cabeceras HTTP.
- **location deny all**: Bloquea el acceso a `xmlrpc.php`, `wp-config.php` y la ejecución de archivos PHP en `/uploads/`.
- **DISALLOW_FILE_EDIT**: Evita la modificación de archivos de temas y plugins desde el panel de control.
- **Plugin de reintentos y roles**: Protege frente a ataques de fuerza bruta y restringe privilegios administrativos.

