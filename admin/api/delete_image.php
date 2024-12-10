<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

$imageId = $_POST['image_id'] ?? null;
if (!$imageId) {
    http_response_code(400);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener información de la imagen y verificar permisos
    $stmt = $db->prepare("SELECT i.*, s.user_id FROM space_images i 
                         JOIN spaces s ON i.space_id = s.id 
                         WHERE i.id = ?");
    $stmt->execute([$imageId]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image || (!$_SESSION['is_admin'] && $image['user_id'] !== $_SESSION['user_id'])) {
        http_response_code(403);
        exit;
    }

    // Eliminar el archivo físico
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $image['image_path'])) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $image['image_path']);
    }

    // Eliminar registro de la base de datos
    $stmt = $db->prepare("DELETE FROM space_images WHERE id = ?");
    $stmt->execute([$imageId]);

    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}