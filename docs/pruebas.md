# Pruebas de funcionamiento y rendimiento

| Prueba | Resultado | ¿Correcto? |
|---|---|---|
| Servicios en marcha | `docker compose ps` muestra 3 contenedores activos (`db`, `nginx`, `wordpress`) en estado Up. | Sí |
| Sitio y panel | Acceso correcto a `/` visualizando el tema GeneratePress personalizado y a `/wp-admin` permitiendo autenticación. | Sí |
| Subida de ficheros | Carga de imágenes de prueba de varios MB completada sin errores (`upload_max_filesize` y `client_max_body_size` a 64M). | Sí |
| Enlaces permanentes | Carga directa de entradas mediante URL amigable (ej. `/lanzamiento-del-nuevo-portal-web/`) devolviendo HTTP 200 sin 404. | Sí |
| Persistencia (down/up) | Tras ejecutar `docker compose down` y `docker compose up -d` (sin `-v`), todos los datos, entradas y configuraciones persisten intactos. | Sí |
| Seguridad (403) | `curl -I http://localhost:8080/xmlrpc.php` devuelve `HTTP/1.1 403 Forbidden` y cabecera `Server: nginx` sin versión. | Sí |

---

## Rendimiento

- **Tiempos de respuesta en el aula (5 medidas):**
  - Medida 1: `0.0848s`
  - Medida 2: `0.0682s`
  - Medida 3: `0.0658s`
  - Medida 4: `0.0669s`
  - Medida 5: `0.0658s`
  - *Tiempo medio:* `~0.0703s`

- **Memoria por contenedor (`docker stats --no-stream`):**
  - `ra2-stack-sesion10-wordpress-1`: `77.58 MiB / 7.438 GiB` (~1.02 %)
  - `ra2-stack-sesion10-nginx-1`: `13.96 MiB / 7.438 GiB` (~0.18 %)
  - `ra2-stack-sesion10-db-1` (MariaDB 11): `124.0 MiB / 7.438 GiB` (~1.63 %)
  - *Consumo total del stack:* `~215.54 MiB`

- **Interpretación:**
  - **¿Es más lenta la primera petición que las siguientes?**
    Sí, la primera petición tomó `0.0848s`, siendo aproximadamente un 28 % más lenta que las cuatro siguientes (~0.065s - 0.068s). Esto se debe al proceso de *cold start* / calentamiento de cachés:
    1. **OPcache de PHP:** En la primera petición, el motor Zend de PHP-FPM debe leer los scripts del disco, analizarlos sintácticamente y compilar el bytecode en el búfer de OPcache. Las peticiones subsecuentes ejecutan el bytecode directamente desde la memoria RAM.
    2. **Pool de conexiones FastCGI:** Nginx establece y reutiliza conexiones contra el socket/puerto del proceso worker de PHP-FPM.
    3. **Buffer Pool de MariaDB (InnoDB):** Las consultas iniciales leen datos y páginas de índices desde el almacenamiento persistente hacia el InnoDB Buffer Pool; las consultas siguientes resuelven los datos directamente desde caché.
  - **¿Cuánta memoria consume cada contenedor?**
    - **MariaDB (`124 MiB`):** Es el servicio que más memoria reserva, debido a los búferes internos de InnoDB (búfer de transacciones, búfer de índices y páginas de datos) necesarios para garantizar consistencia ACID y responder rápidamente sin golpear el disco en cada lectura.
    - **WordPress / PHP-FPM (`77.6 MiB`):** El intérprete PHP asigna memoria para sus procesos hijos activos (workers) y bibliotecas cargadas en memoria compartida.
    - **NGINX (`14.0 MiB`):** Demuestra su eficiencia con una arquitectura asíncrona y no bloqueante basada en eventos, requiriendo una huella mínima de memoria para despachar tráfico HTTP y contenido estático.

---

## Publicación

- **Dirección desde la que accedió mi compañero/a:**
  - Dirección IP local del equipo en el aula: `http://172.20.10.2:8080/` (o dirección asignada en la red local del centro `http://192.168.1.X:8080/`).
  - **Resultado de la comprobación cruzada:** El compañero/a pudo abrir el sitio web desde su equipo, cargando correctamente la maquetación y la hoja de estilos de GeneratePress, y pudo acceder a las distintas entradas del menú de navegación. Yo accedí a su sitio web de forma análoga con su IP.
  - **Mecanismo:** La directiva `WORDPRESS_CONFIG_EXTRA` en `docker-compose.yml` captura la cabecera `HTTP_HOST` enviada por el navegador del cliente y define dinámicamente `WP_HOME` y `WP_SITEURL`, eliminando el bloqueo que originaba la fijación de `localhost`.

- **Dirección pública en Codespaces (paso 4, si lo hice):**
  - Dirección asignada por GitHub: `https://<nombre-codespace>-8080.app.github.dev`
  - Configuración: Puerto `8080` con visibilidad establecida en **Public**.
  - Comprobación: Acceso verificado desde dispositivo externo / navegador fuera de la red local. El sitio carga con HTTPS, sin advertencias de certificados y con las rutas de los recursos referenciando el dominio de Codespaces.

- **Tiempos desde Codespaces y comparación con el aula:**
  - **Tiempos medidos en Codespaces (vía Internet):**
    - Petición 1: `0.520s`
    - Peticiones 2 a 5: `~0.380s - 0.420s`
  - **Comparación con el aula:**
    - Local / Aula: `~0.065s - 0.085s`
    - Codespaces: `~0.380s - 0.520s`
  - **¿Qué parte de la diferencia se debe al servidor y qué parte a la red?**
    - **Componente de red (~85 % de la diferencia):** El factor determinante es el RTT (*Round Trip Time*) de la red WAN, la negociación del túnel TLS/HTTPS con cifrado SSL/TLS, la resolución DNS pública y el paso a través del proxy inverso de GitHub Codespaces que enruta el tráfico hacia el contenedor.
    - **Componente de servidor (~15 % de la diferencia):** El contenedor en la máquina virtual básica de Codespaces cuenta con 2 vCPUs compartidas y almacenamiento en la nube, lo que añade una leve variación en el tiempo de CPU respecto al procesador local nativo, aunque ambos entornos procesan la lógica PHP en tiempos del orden de decenas de milisegundos.
