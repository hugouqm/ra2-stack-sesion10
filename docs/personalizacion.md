# Personalización del sitio

- **Tema activo:** GeneratePress (versión 3.6.1).
- **Por qué lo elegí:** Es uno de los temas más rápidos, ligeros y estables para WordPress. Ocupa menos de 10 KB, no añade dependencias pesadas, ni plugins obligatorios ni contenido de relleno no deseado, transformando limpiamente el aspecto visual, la cabecera y la tipografía del sitio respetando al 100 % los estándares de accesibilidad y el editor de bloques nativo.
- **Plugin instalado:** WPForms Lite (versión 2.0.2.2, actualizado recientemente, más de 6 millones de instalaciones activas y valoración de 4.9/5 estrellas).
- **Por qué lo necesito:** Proporciona un sistema seguro y visual para construir formularios de contacto interactivos y accesibles en la página «Contacto». Evita publicar direcciones de correo electrónico en texto plano (previniendo el spam) y facilita la comunicación directa de los visitantes con los administradores del sitio.
- **Estructura de contenidos:**
  - **Páginas:**
    - «Inicio» (establecida como portada estática en *Ajustes → Lectura*, con bloques para «Novedades», «Tutoriales» y «Galería de medios»).
    - «Sobre el proyecto» (página informativa general).
    - «Tecnologías» (página hija con jerarquía bajo «Sobre el proyecto»).
    - «Contacto» (página de comunicación con formulario de contacto).
  - **Categorías y Entradas:**
    - Categoría *Novedades*: «Lanzamiento del nuevo portal web» y «Actualización de infraestructura con contenedores».
    - Categoría *Tutoriales*: «Guía: Despliegue de WordPress con NGINX y PHP-FPM».
  - **Menú principal:**
    - Ubicación: Menú principal asignado a la posición primaria del tema.
    - Jerarquía de navegación:
      1. Inicio
      2. Sobre el proyecto
         - Tecnologías (submenú / elemento dependiente de Sobre el proyecto)
      3. Contacto

---

## Configuraciones adicionales del entorno y servidor

- **Ajustes generales:**
  - Idioma: Español (`es_ES`).
  - Zona horaria: Madrid (`Europe/Madrid`).
  - Título del sitio: «Proyecto Web CMS».
  - Descripción: «Sitio web configurado con Docker y WordPress».
  - Enlaces permanentes: Estructura basada en *Nombre de la entrada* (`/%postname%/`), con reescritura gestionada por NGINX vía `try_files $uri $uri/ /index.php?$args;`.
  - Moderación de comentarios: Activada la aprobación manual de comentarios antes de su publicación.
- **Límites de PHP (persistidos en Git):**
  - Archivo `config/uploads.ini` montado en el contenedor en `/usr/local/etc/php/conf.d/uploads.ini:ro`.
  - `upload_max_filesize = 64M`
  - `post_max_size = 64M`
  - `memory_limit = 256M`
- **Evidencias gráficas:**
  - Captura antes del cambio de tema: `docs/captura-antes-tema.png` (tema inicial Twenty Twenty-Three).
  - Captura tras el cambio de tema: `docs/captura-despues-tema.png` (tema activo GeneratePress personalizado).
