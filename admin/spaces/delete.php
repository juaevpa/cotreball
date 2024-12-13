<?php
session_start();
require_once '../../config/database.php';

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['space_id'])) {
    $pdo = Database::getInstance()->getConnection();
    
    try {
        $pdo->beginTransaction();
        
        // Obtener información del espacio antes de borrarlo
        $stmt = $pdo->prepare("SELECT * FROM spaces WHERE id = ?");
        $stmt->execute([$_POST['space_id']]);
        $space = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$space) {
            throw new Exception("El espacio no existe");
        }
        
        // Obtener todas las imágenes asociadas
        $stmt = $pdo->prepare("SELECT image_path FROM space_images WHERE space_id = ?");
        $stmt->execute([$_POST['space_id']]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Borrar archivos físicos de imágenes
        foreach ($images as $image_path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_path;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
        
        // Intentar eliminar el directorio de imágenes del espacio
        $spaceUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/spaces/' . $_POST['space_id'];
        if (is_dir($spaceUploadDir)) {
            // Eliminar cualquier archivo restante en el directorio
            $files = glob($spaceUploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Eliminar el directorio
            rmdir($spaceUploadDir);
        }
        
        // Borrar el espacio (las imágenes se borrarán automáticamente por la restricción ON DELETE CASCADE)
        $stmt = $pdo->prepare("DELETE FROM spaces WHERE id = ?");
        $stmt->execute([$_POST['space_id']]);
        
        $pdo->commit();
        
        $_SESSION['success_message'] = "El espacio \"{$space['name']}\" ha sido eliminado correctamente.";
        header('Location: /admin/spaces');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error al eliminar el espacio: " . $e->getMessage();
        header('Location: /admin/spaces');
    }
    
    exit;
}

// Si se intenta acceder directamente sin POST, redirigir
header('Location: /admin/spaces');
exit; 