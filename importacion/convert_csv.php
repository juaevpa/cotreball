<?php
// Archivo original y nuevo
$archivo_original = 'espacios_completo.txt';
$archivo_nuevo = 'espacios_semicolon.txt';

// Verificar que el archivo original existe
if (!file_exists($archivo_original)) {
    die("El archivo original no existe.\n");
}

// Abrir archivos
$input = fopen($archivo_original, 'r');
$output = fopen($archivo_nuevo, 'w');

if (!$input || !$output) {
    die("Error abriendo los archivos.\n");
}

// Función para convertir una línea
function convertLine($line) {
    // Dividir la línea en campos, respetando las comillas
    preg_match_all('/"[^"]*"|[^,]+/', $line, $matches);
    $fields = $matches[0];
    
    // Limpiar las comillas de los campos
    $fields = array_map(function($field) {
        return trim($field, '"');
    }, $fields);
    
    // Unir los campos con punto y coma
    return implode(';', $fields);
}

$linea = 0;
// Procesar cada línea
while (($line = fgets($input)) !== false) {
    $linea++;
    echo "Procesando línea $linea\r";
    
    // Convertir la línea
    $new_line = convertLine($line);
    
    // Escribir la nueva línea
    fwrite($output, $new_line . "\n");
}

// Cerrar archivos
fclose($input);
fclose($output);

echo "\n\nConversión completada. El nuevo archivo es 'espacios_semicolon.txt'\n";
?> 