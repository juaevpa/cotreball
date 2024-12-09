<?php
session_start();
require_once '../config/database.php';

// Verificar que es admin
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['space_id'])) {
    $db = Database::getInstance()->getConnection();
    
    // Primero eliminamos las imágenes asociadas
    $stmt = $db->prepare("SELECT image_path FROM space_images WHERE space_id = ?");
    $stmt->execute([$_POST['space_id']]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($images as $image_path) {
        $full_path = $_SERVER['DOCUMENT_ROOT'] . $image_path;
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    // Eliminamos el espacio (las imágenes se eliminarán automáticamente por la restricción ON DELETE CASCADE)
    $stmt = $db->prepare("DELETE FROM spaces WHERE id = ?");
    $stmt->execute([$_POST['space_id']]);
    
    header('Location: /admin');
    exit;
}

header('Location: /admin');
exit; 