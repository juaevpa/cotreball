<?php
session_start();
require_once '../../config/database.php';

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['space_id'])) {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        // Primero obtenemos las imágenes para borrarlas del sistema de archivos
        $stmt = $db->prepare("SELECT image_path FROM space_images WHERE space_id = ?");
        $stmt->execute([$_POST['space_id']]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Borrar archivos físicos
        foreach ($images as $image_path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $image_path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Borrar el espacio (las imágenes se borrarán automáticamente por la restricción ON DELETE CASCADE)
        $stmt = $db->prepare("DELETE FROM spaces WHERE id = ?");
        $stmt->execute([$_POST['space_id']]);
        
        $db->commit();
        header('Location: /');
        
    } catch (Exception $e) {
        $db->rollBack();
        die("Error al eliminar el espacio: " . $e->getMessage());
    }
    
    exit;
}

header('Location: /');
exit; 