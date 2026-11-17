# Despliegue de WordPress con NGINX y MariaDB en Docker (RA2)

Proyecto de despliegue, personalización, aseguramiento y pruebas de rendimiento de un Sistema de Gestión de Contenidos (CMS) WordPress basado en una arquitectura multicontenedor con NGINX, PHP-FPM y MariaDB orquestados mediante Docker Compose.

---

## 📌 1. Descripción del Proyecto

Este repositorio contiene la infraestructura como código necesaria para desplegar un stack web de producción para WordPress compuesto por tres servicios desacoplados:

- **NGINX (reverse proxy & servidor web):** Recibe las conexiones HTTP en el puerto `8080`, gestiona la seguridad en capa perimetral, sirve archivos estáticos y delega el procesamiento de scripts PHP a través del protocolo FastCGI.
- **WordPress con PHP-FPM:** Ejecuta el núcleo del CMS y la lógica de negocio en PHP 8.1 FPM. Incluye optimizaciones de límites de carga y configuración dinámica de URL de acceso (`WP_HOME` / `WP_SITEURL`).
- **MariaDB:** Sistema de gestión de bases de datos relacional para el almacenamiento persistente de tablas, entradas, opciones y metadatos de WordPress.

---

## 📋 2. Requisitos Previos

- **Docker Engine** (versión 24.0 o superior) y **Docker Compose V2** (`docker compose`).
- **Git** instalado en el sistema.
- **Navegador web** moderno y cliente `curl` para validaciones de red.
- Puertos libres: `8080` (o el puerto configurado en el mapeo de host).

---

## 🚀 3. Cómo Arrancar el Proyecto desde Cero (Paso a Paso)

Sigue estos pasos para reproducir de forma exacta y autónoma el entorno completo:

### Paso 3.1: Clonar el repositorio
```bash
git clone https://github.com/<tu-usuario>/<tu-repositorio>.git
cd ra2-stack-sesion10
```

### Paso 3.2: Configurar las variables de entorno
Copia la plantilla de variables de entorno y ajusta las credenciales deseadas:
```bash
cp .env.example .env
```
Contenido de referencia del archivo `.env`:
```ini
MYSQL_ROOT_PASSWORD=root_pass_clase
WP_DB_NAME=wp_db
WP_DB_USER=wp_user
WP_DB_PASSWORD=wp_pass_clase
```
> [!IMPORTANT]
> El archivo `.env` está excluido del control de versiones mediante `.gitignore` para no exponer secretos en el repositorio.

### Paso 3.3: Arrancar los contenedores
Levanta la arquitectura en segundo plano:
```bash
docker compose up -d
```
Verifica que los tres servicios estén en ejecución:
```bash
docker compose ps
```

### Paso 3.4: Creación de la base de datos y usuario (si es una instalación limpia)
Si los volúmenes están recién creados, crea la base de datos y otorga privilegios al usuario:
```bash
docker compose exec db mariadb -u root -p${MYSQL_ROOT_PASSWORD} -e "CREATE DATABASE IF NOT EXISTS ${WP_DB_NAME}; CREATE USER IF NOT EXISTS '${WP_DB_USER}'@'%' IDENTIFIED BY '${WP_DB_PASSWORD}'; GRANT ALL PRIVILEGES ON ${WP_DB_NAME}.* TO '${WP_DB_USER}'@'%'; FLUSH PRIVILEGES;"
```

### Paso 3.5: Acceso al sitio
- **Sitio público:** Abre en el navegador `http://localhost:8080/`
- **Panel de administración:** `http://localhost:8080/wp-admin/`

---

## ☁️ 4. Despliegue en la Nube con GitHub Codespaces

El repositorio incluye soporte nativo para **GitHub Codespaces** mediante la configuración en `.devcontainer/devcontainer.json`:

1. En GitHub, sitúate en la rama `ra2-cms` o `main`.
2. Pulsa en el botón verde **Code** → pestaña **Codespaces** → **Create codespace**.
3. Una vez abierto el entorno web de VS Code, copia el archivo de variables:
   ```bash
   cp .env.example .env
   docker compose up -d
   ```
4. Dirígete a la pestaña **PORTS** (al lado del terminal).
5. Haz clic derecho sobre el puerto **8080** → **Port Visibility** → **Public**.
6. Haz clic en el icono del globo terráqueo o copia la dirección generada (`https://<nombre-codespace>-8080.app.github.dev`).
7. Gracias al bloque `WORDPRESS_CONFIG_EXTRA` configurado en `docker-compose.yml`, WordPress detectará las cabeceras `X-Forwarded-Host` y `X-Forwarded-Proto` y funcionará con HTTPS público sin necesidad de reconfiguración manual.

---

## 📂 5. Estructura del Proyecto

```text
.
├── .devcontainer/
│   └── devcontainer.json     # Configuración para ejecución en GitHub Codespaces (Docker-in-Docker y port forwarding)
├── .env.example              # Plantilla documentada de variables de entorno (no subir .env real)
├── .gitignore                # Reglas de exclusión de Git (ignora .env)
├── docker-compose.yml        # Orquestación multicontenedor con fijación de versiones y configuración dinámica
├── config/
│   └── uploads.ini           # Directivas PHP para ampliar tamaño de subidas y memoria (64M / 256M)
├── nginx/
│   └── default.conf          # Configuración de NGINX con FastCGI, try_files y directivas de seguridad
├── docs/
│   ├── personalizacion.md    # Detalle de la personalización del tema (GeneratePress), plugins, menú y páginas
│   ├── seguridad.md          # Medidas de endurecimiento (hardening), riesgos mitigados, límites y evidencias
│   └── pruebas.md            # Pruebas de funcionamiento, persistencia, rendimiento (docker stats) y publicación
└── README.md                 # Documentación técnica general del proyecto y guía de reproducción
```

---

## 📖 6. Enlaces a la Documentación Técnica

- 🎨 **[Personalización del sitio](docs/personalizacion.md):** Justificación de la elección del tema ligero GeneratePress, plugin de formularios WPForms Lite, jerarquía de páginas y configuración de límites PHP.
- 🛡️ **[Seguridad del CMS](docs/seguridad.md):** Endurecimiento en NGINX y WordPress (`xmlrpc.php`, `wp-config.php`, PHP en `uploads/`, `DISALLOW_FILE_EDIT`, ocultación de versión y roles).
- 🧪 **[Pruebas de funcionamiento y rendimiento](docs/pruebas.md):** Verificación de servicios, URLs amigables, persistencia de volúmenes, métricas con `curl` y análisis de consumo con `docker stats`.

---

## ⚙️ 7. Explicación de la Configuración y Resolución de Fallos

### Línea a línea del bloque dinámico de red (`docker-compose.yml`):
```yaml
WORDPRESS_CONFIG_EXTRA: |
  define('DISALLOW_FILE_EDIT', true);
  $$iaw_host = $$_SERVER['HTTP_X_FORWARDED_HOST'] ?? $$_SERVER['HTTP_HOST'];
  $$iaw_proto = $$_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http';
  define('WP_HOME', $$iaw_proto . '://' . $$iaw_host);
  define('WP_SITEURL', $$iaw_proto . '://' . $$iaw_host);
```
- `define('DISALLOW_FILE_EDIT', true);`: Deshabilita el editor integrado de plantillas y plugins desde el panel de administración web, mitigando la subida de código arbitrario.
- `$$iaw_host = $$_SERVER['HTTP_X_FORWARDED_HOST'] ?? $$_SERVER['HTTP_HOST'];`: Extrae la dirección y puerto solicitados por el cliente. Si existe un proxy inverso (como en GitHub Codespaces), prioriza la cabecera `X-Forwarded-Host`; en red local de aula utiliza la cabecera `HTTP_HOST`.
- `$$iaw_proto = $$_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'http';`: Detecta si la conexión llega mediante HTTPS a través del proxy perimetral o mediante HTTP simple.
- `define('WP_HOME', ...);` y `define('WP_SITEURL', ...);`: Sobrescriben las URL base guardadas en la base de datos en tiempo de ejecución, permitiendo que el mismo stack funcione simultáneamente en localhost, en la red de clase y en la nube.
- **Nota sobre el doble dólar (`$$`):** Docker Compose interpreta un solo `$` como una variable de interpolación de entorno. Al duplicarlo (`$$`), escapa la interpolación y envía un único carácter `$` literal al intérprete PHP.

### Fallo inducido habitual y solución:
- **Síntoma:** Al acceder a una entrada por su URL amigable (ej. `/lanzamiento-del-nuevo-portal-web/`), el navegador devuelve error `404 Not Found` de NGINX.
- **Causa:** Ausencia de la directiva `try_files $uri $uri/ /index.php?$args;` en la regla `location /` de NGINX, haciendo que el servidor busque un fichero físico en el disco en lugar de redirigir la petición al front controller `index.php` de WordPress.
- **Solución:** Configurar en `nginx/default.conf` la cláusula `try_files` dentro del bloque `location /` y reiniciar o recargar el contenedor NGINX (`docker compose exec nginx nginx -s reload`).
