<?php
// Archivo original y nuevo
$archivo_original = 'espacios_completo.txt';
$archivo_limpio = 'espacios_clean.txt';

// Verificar que el archivo original existe
if (!file_exists($archivo_original)) {
    die("El archivo original no existe.\n");
}

// Abrir archivos
$input = fopen($archivo_original, 'r');
$output = fopen($archivo_limpio, 'w');

if (!$input || !$output) {
    die("Error abriendo los archivos.\n");
}

$linea = 0;
$encabezados = null;

// Procesar cada línea
while (($fila = fgetcsv($input, 0, ",", '"')) !== FALSE) {
    $linea++;
    
    if ($linea === 1) {
        // Guardar encabezados
        $encabezados = $fila;
        // Escribir encabezados
        fputcsv($output, $encabezados);
        continue;
    }
    
    // Si la línea tiene más columnas que los encabezados, probablemente contiene datos de traducción
    if (count($fila) > count($encabezados)) {
        // Tomar solo las primeras columnas que coinciden con los encabezados
        $fila = array_slice($fila, 0, count($encabezados));
    }
    
    // Escribir la línea limpia
    fputcsv($output, $fila);
    
    echo "Procesada línea $linea\r";
}

// Cerrar archivos
fclose($input);
fclose($output);

echo "\n\nLimpieza completada. El nuevo archivo es 'espacios_clean.txt'\n";
?> 