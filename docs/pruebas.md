# Pruebas de funcionamiento y rendimiento

| Prueba | Resultado | ¿Correcto? |
|---|---|---|
| Servicios en marcha | 3 servicios activos (nginx, wordpress, db) | Sí |
| Sitio y panel | Acceso correcto a / y /wp-admin | Sí |
| Subida de ficheros | Imagen subida sin errores en Medios | Sí |
| Enlaces permanentes | Entrada individual carga correctamente (sin 404) | Sí |
| Persistencia (down/up) | Contenido y configuración se mantienen tras reinicio | Sí |
| Seguridad (403) | xmlrpc.php devuelve 403 Forbidden | Sí |

## Rendimiento

- **Tiempos de respuesta (5 medidas):** 2.36s, 0.08s, 0.07s, 0.08s, 0.07s
- **Memoria por contenedor (`docker stats`):**
  - `wordpress`: ~94.2 MB
  - `db`: ~126.1 MB
  - `nginx`: ~14.3 MB
- **Interpretación:** La primera petición requiere un tiempo superior debido al arranque y compilación de scripts en PHP, mientras que las siguientes se estabilizan con respuestas menores a 0.1 segundos gracias al motor PHP-FPM.
