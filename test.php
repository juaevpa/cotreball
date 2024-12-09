<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "¡Conexión exitosa a la base de datos!";
    
    // Verificar permisos de escritura
    $uploadDir = 'uploads/spaces/';
    if (is_writable($uploadDir)) {
        echo "<br>El directorio de uploads tiene permisos correctos";
    } else {
        echo "<br>ERROR: El directorio de uploads no tiene permisos de escritura";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 