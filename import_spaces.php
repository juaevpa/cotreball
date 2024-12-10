<?php
require_once 'config/database.php';

// Función para limpiar y formatear los datos
function cleanData($str) {
    if (empty($str)) return '';
    return trim(str_replace(array('â‚¬', 'Ã³', 'Ã©', 'Ã±', 'Ã¨'), array('€', 'ó', 'é', 'ñ', 'è'), $str));
}

// Función para extraer el precio del texto
function extractPrice($details) {
    if (empty($details)) return null;
    preg_match('/(\d+)€/', $details, $matches);
    return isset($matches[1]) ? floatval($matches[1]) : null;
}

// Función para descargar y guardar imagen
function downloadAndSaveImage($url, $spaceId) {
    if (empty($url)) return false;
    
    $uploadDir = 'uploads/spaces/';
    
    // Crear el directorio si no existe
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generar nombre único para la imagen
    $filename = uniqid() . '_' . md5($url) . '.jpg';
    $filepath = $uploadDir . $filename;
    
    try {
        // Configurar contexto para la descarga
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        // Intentar descargar la imagen
        $imageContent = @file_get_contents($url, false, $context);
        if ($imageContent === false) {
            error_log("No se pudo descargar la imagen: " . $url);
            return false;
        }
        
        // Guardar la imagen
        if (file_put_contents($filepath, $imageContent)) {
            return '/' . $filepath;
        }
        
    } catch (Exception $e) {
        error_log("Error al procesar la imagen: " . $e->getMessage());
        return false;
    }
    
    return false;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar si el archivo existe
    if (!file_exists('espacios.csv')) {
        throw new Exception("El archivo espacios.csv no existe");
    }
    
    // Leer el archivo CSV
    $file = fopen('espacios.csv', 'r');
    if ($file === false) {
        throw new Exception("No se pudo abrir el archivo CSV");
    }
    
    // Saltar la primera línea (encabezados)
    fgetcsv($file);
    
    // Preparar las consultas SQL
    $stmtSpace = $db->prepare("
        INSERT INTO spaces (
            name, city, address, description, 
            lat, lng, price_month, 
            available, approved, user_id
        ) VALUES (
            ?, ?, ?, ?, 
            ?, ?, ?, 
            1, 1, 1
        )
    ");
    
    $stmtImage = $db->prepare("
        INSERT INTO space_images (
            space_id, image_path, is_primary
        ) VALUES (
            ?, ?, ?
        )
    ");
    
    // Contador de espacios importados
    $importados = 0;
    $errores = 0;
    
    // Leer y procesar cada línea
    while (($line = fgetcsv($file)) !== FALSE) {
        if (count($line) < 7) {
            error_log("Línea incompleta en CSV: " . implode(',', $line));
            $errores++;
            continue;
        }
        
        $db->beginTransaction();
        
        try {
            $name = cleanData($line[0]);
            $city = cleanData($line[1]);
            $address = cleanData($line[2]);
            $details = cleanData($line[3]);
            $lat = !empty($line[4]) ? floatval($line[4]) : 0;
            $lng = !empty($line[5]) ? floatval($line[5]) : 0;
            $price = extractPrice($details);
            
            // Validar datos obligatorios
            if (empty($name) || empty($city) || empty($address)) {
                throw new Exception("Datos obligatorios faltantes");
            }
            
            // Insertar el espacio
            $stmtSpace->execute([
                $name,
                $city,
                $address,
                $details,
                $lat,
                $lng,
                $price
            ]);
            
            $spaceId = $db->lastInsertId();
            
            // Procesar la imagen si existe
            if (!empty($line[6])) {
                $imagePath = downloadAndSaveImage($line[6], $spaceId);
                if ($imagePath) {
                    $stmtImage->execute([
                        $spaceId,
                        $imagePath,
                        1
                    ]);
                }
            }
            
            $db->commit();
            $importados++;
            echo "Importado: $name\n";
            
        } catch (Exception $e) {
            $db->rollBack();
            $errores++;
            error_log("Error al importar $name: " . $e->getMessage());
            echo "Error al importar $name: " . $e->getMessage() . "\n";
        }
    }
    
    fclose($file);
    echo "\nImportación completada:\n";
    echo "- Espacios importados: $importados\n";
    echo "- Errores encontrados: $errores\n";
    
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
?> 