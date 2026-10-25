# Taisa Tools

Plugin de utilidades de mantenimiento para WordPress, con modo mantenimiento, snippet de `<head>`, ajustes de rendimiento, control de temporales/inodos y gestión de variantes de imágenes.

## Funciones nuevas

- **Temporales e inodos:** analiza `uploads`, `wp-content/cache`, `upgrade` y `languages`, pero solo propone nombres temporales o ficheros dentro de carpetas de caché conocidas. La eliminación siempre requiere selección y nonce.
- **Tamaños de medios:** permite impedir la generación futura de tamaños registrados y, en el frontal, omitir las variantes desactivadas en los metadatos para que WordPress recurra al original cuando un tema solicite ese tamaño. El análisis de uso y la limpieza de derivados se realiza desde Taisa Image Optimizer; Taisa Tools conserva la configuración compartida.
- **Actualizaciones:** el plugin está vinculado a `TaisaDigital/taisa-tools` y consulta la última release cada seis horas; no requiere configuración en `wp-config.php`.

Para un repositorio privado, define también `TAISA_TOOLS_GITHUB_TOKEN` en `wp-config.php`. Un repositorio público es más sencillo para instalaciones de clientes; el token debe ser de solo lectura y no debe guardarse en el repositorio.

## Seguridad y operación

Haz una copia de seguridad antes de borrar variantes. La desactivación de un tamaño afecta a futuras subidas y regeneraciones; no elimina archivos existentes hasta que se solicite expresamente. El filtro del frontal solo afecta a los tamaños configurados como desactivados; conserva los tamaños permitidos y la metadata completa en administración para que las herramientas de análisis puedan operar.

## Changelog

### 1.3.0

- Las variantes desactivadas se omiten en el frontal para que WordPress utilice el original cuando se solicite uno de esos tamaños.
- Se conserva íntegra la metadata en administración y se mantienen disponibles las variantes que no estén desactivadas.

### 1.2.0

- Separada la configuración de generación de tamaños de la inspección y limpieza de variantes.
- Migradas automáticamente las selecciones guardadas por versiones anteriores de Taisa Image Optimizer.

### 1.1.1

- Vinculado el actualizador al repositorio público `TaisaDigital/taisa-tools`.

### 1.1.0

- Añadido informe y limpieza selectiva de ficheros temporales/cachés.
- Añadida gestión de tamaños de imagen y borrado selectivo de variantes.
- Añadidas actualizaciones desde releases de GitHub.

