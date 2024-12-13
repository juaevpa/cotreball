<?php
/**
 * Importa espacios de coworking desde un archivo CSV a la base de datos de WordPress.
 */

// Incluir el archivo de configuración de WordPress
require_once('wp-load.php');

// Ruta al archivo CSV
$archivo_csv = 'espacios.txt';
// Abrir el archivo CSV
if (!file_exists($archivo_csv) || !is_readable($archivo_csv)) {
    die("El archivo CSV no existe o no es legible.");
}

$encabezados = [];
$datos = [];

// Leer el archivo CSV
if (($handle = fopen($archivo_csv, 'r')) !== FALSE) {
    while (($fila = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if (empty($encabezados)) {
            $encabezados = $fila;
        } else {
            $datos[] = array_combine($encabezados, $fila);
        }
    }
    fclose($handle);
}

// Iterar sobre cada fila de datos y crear una entrada personalizada en WordPress
foreach ($datos as $espacio) {
    // Crear un nuevo post de tipo 'espacio_coworking'
    $post_id = wp_insert_post([
        'post_title'    => $espacio['Nombre'],
        'post_content'  => $espacio['Detalles'],
        'post_status'   => 'publish',
        'post_type'     => 'espacio_coworking',
    ]);

    if ($post_id) {
        // Agregar metadatos personalizados
        update_post_meta($post_id, 'ciudad', $espacio['Ciudad']);
        update_post_meta($post_id, 'direccion', $espacio['Dirección']);
        update_post_meta($post_id, 'latitud', $espacio['Latitud']);
        update_post_meta($post_id, 'longitud', $espacio['Longitud']);
        update_post_meta($post_id, 'url_imagen', $espacio['URL_Imagen1']);
        update_post_meta($post_id, 'telefono', $espacio['phone']);
        update_post_meta($post_id, 'email', $espacio['email']);
        update_post_meta($post_id, 'url', $espacio['URL']);

        // Descargar y establecer la imagen destacada
        $imagen_url = $espacio['URL_Imagen1'];
        $imagen_id = descargar_imagen_desde_url($imagen_url, $post_id);
        if ($imagen_id) {
            set_post_thumbnail($post_id, $imagen_id);
        }

        echo "Espacio '{$espacio['Nombre']}' importado exitosamente.<br>";
    } else {
        echo "Error al importar el espacio '{$espacio['Nombre']}'.<br>";
    }
}

/**
 * Descarga una imagen desde una URL y la adjunta al post.
 *
 * @param string $url URL de la imagen.
 * @param int $post_id ID del post al que se adjuntará la imagen.
 * @return int|false ID de la imagen adjunta o false en caso de error.
 */
function descargar_imagen_desde_url($url, $post_id) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    // Descargar la imagen al servidor
    $tmp = download_url($url);

    if (is_wp_error($tmp)) {
        return false;
    }

    // Obtener el nombre de la imagen
    $nombre_archivo = basename(parse_url($url, PHP_URL_PATH));

    // Preparar el array para la subida
    $archivo = [
        'name'     => $nombre_archivo,
        'type'     => mime_content_type($tmp),
        'tmp_name' => $tmp,
        'error'    => 0,
        'size'     => filesize($tmp),
    ];

    // Subir la imagen a la biblioteca de medios
    $id_imagen = media_handle_sideload($archivo, $post_id);

    // Verificar si hubo un error
    if (is_wp_error($id_imagen)) {
        @unlink($tmp);
        return false;
    }

    return $id_imagen;
}
?> 