<?php
// Configuración de la base de datos
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'password' => '',
    'database' => 'cotreball'
];

// Función para generar un slug único
function generate_unique_slug($name, $mysqli) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
    $slug = preg_replace('/-+/', '-', $slug); // Eliminar guiones múltiples
    $slug = trim($slug, '-'); // Eliminar guiones al inicio y final
    
    $original_slug = $slug;
    $counter = 1;
    
    while(true) {
        $result = $mysqli->query("SELECT id FROM spaces WHERE slug = '" . $mysqli->real_escape_string($slug) . "'");
        if ($result->num_rows == 0) {
            return $slug;
        }
        $slug = $original_slug . '-' . $counter;
        $counter++;
    }
}

// Función para procesar y guardar una imagen
function save_image($url, $space_id) {
    if (empty($url)) return false;
    
    // Si la URL comienza con /uploads/, es una ruta local
    if (strpos($url, '/uploads/') === 0) {
        return ltrim($url, '/'); // Devolver la ruta sin el slash inicial
    }
    
    // Crear directorio si no existe
    $upload_dir = 'uploads/spaces/' . $space_id;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generar nombre único para la imagen
    $image_name = uniqid() . '.jpg';
    $local_path = $upload_dir . '/' . $image_name;
    
    // Descargar imagen
    if (copy($url, $local_path)) {
        return $local_path;
    }
    
    return false;
}

// Función para convertir precio a decimal
function parse_price($price) {
    if (empty($price)) return null;
    $price = str_replace(',', '.', $price);
    $price = preg_replace('/[^0-9.]/', '', $price);
    return !empty($price) ? floatval($price) : null;
}

// Función para limpiar texto
function clean_text($text) {
    // Eliminar etiquetas HTML y caracteres especiales
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(['</span>', '<span>', '</p>', '<p>'], '', $text);
    return trim($text);
}

// Crear conexión a la base de datos
$mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database']);

if ($mysqli->connect_error) {
    die("Error de conexión: " . $mysqli->connect_error . "\n");
}

// Establecer el conjunto de caracteres
$mysqli->set_charset("utf8mb4");

// Ruta al archivo CSV
$archivo_csv = 'espacios_clean.txt';

if (!file_exists($archivo_csv) || !is_readable($archivo_csv)) {
    die("El archivo CSV no existe o no es legible.\n");
}

$encabezados = [];
$datos = [];
$linea = 0;

// Leer el archivo CSV
if (($handle = fopen($archivo_csv, 'r')) !== FALSE) {
    while (($fila = fgetcsv($handle, 0, ",", '"')) !== FALSE) {
        $linea++;
        if (empty($encabezados)) {
            $encabezados = $fila;
            echo "Encabezados encontrados: " . count($encabezados) . "\n";
            print_r($encabezados);
        } else {
            $datos[] = array_combine($encabezados, $fila);
        }
    }
    fclose($handle);
}

echo "Total de registros a procesar: " . count($datos) . "\n\n";

// Preparar las consultas SQL
$stmt = $mysqli->prepare("INSERT INTO spaces (
    name, slug, description, city, address, lat, lng,
    phone, email, price_day, price_week, price_month,
    price_fixed, schedule, services, capacity, space_types,
    approved, created_at
) VALUES (
    ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?,
    1, NOW()
)");

$stmt_img = $mysqli->prepare("INSERT INTO space_images (space_id, image_path, is_primary, created_at) VALUES (?, ?, ?, NOW())");

if (!$stmt || !$stmt_img) {
    die("Error preparando las consultas: " . $mysqli->error . "\n");
}

// Limpiar tablas antes de importar
$mysqli->query("DELETE FROM space_images");
$mysqli->query("DELETE FROM spaces");

// Contador de espacios procesados
$espacios_procesados = 0;
$imagenes_procesadas = 0;

foreach ($datos as $espacio) {
    // Generar slug único
    $slug = generate_unique_slug($espacio['Nombre'], $mysqli);
    
    // Procesar precios
    $precio_dia = parse_price($espacio['precio_dia']);
    $precio_semana = parse_price($espacio['precio_semana']);
    $precio_mes = parse_price($espacio['precio_mes']);
    $precio_puesto_fijo = parse_price($espacio['precio_puesto_fijo']);
    
    // Limpiar campos de texto
    $nombre = clean_text($espacio['Nombre']);
    $detalles = clean_text($espacio['Detalles']);
    $direccion = clean_text($espacio['Dirección']);
    $horario = isset($espacio['horario']) ? clean_text($espacio['horario']) : null;
    $servicios = isset($espacio['servicios']) ? clean_text($espacio['servicios']) : null;
    $capacidad = isset($espacio['capacidad']) ? clean_text($espacio['capacidad']) : null;
    $tipos_espacio = isset($espacio['tipos_espacio']) ? clean_text($espacio['tipos_espacio']) : null;
    
    // Insertar el espacio
    $stmt->bind_param("sssssddssddddssss",
        $nombre,
        $slug,
        $detalles,
        $espacio['Ciudad'],
        $direccion,
        $espacio['Latitud'],
        $espacio['Longitud'],
        $espacio['phone'],
        $espacio['email'],
        $precio_dia,
        $precio_semana,
        $precio_mes,
        $precio_puesto_fijo,
        $horario,
        $servicios,
        $capacidad,
        $tipos_espacio
    );
    
    if ($stmt->execute()) {
        $space_id = $mysqli->insert_id;
        $espacios_procesados++;
        echo "Espacio importado: {$nombre}\n";
        
        // Procesar imágenes
        for ($i = 1; $i <= 4; $i++) {
            $url_key = "URL_Imagen{$i}";
            if (!empty($espacio[$url_key])) {
                $image_path = save_image($espacio[$url_key], $space_id);
                if ($image_path) {
                    $is_primary = ($i === 1) ? 1 : 0;
                    $stmt_img->bind_param("isi", $space_id, $image_path, $is_primary);
                    if ($stmt_img->execute()) {
                        $imagenes_procesadas++;
                    } else {
                        echo "Error guardando imagen {$i} para {$nombre}: " . $stmt_img->error . "\n";
                    }
                }
            }
        }
    } else {
        echo "Error importando espacio {$nombre}: " . $stmt->error . "\n";
    }
}

$stmt->close();
$stmt_img->close();
$mysqli->close();

echo "\nImportación completada:\n";
echo "- Espacios procesados: {$espacios_procesados}\n";
echo "- Imágenes procesadas: {$imagenes_procesadas}\n";
?> 