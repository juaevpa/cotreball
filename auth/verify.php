<?php
session_start();
require_once '../config/database.php';

if (!isset($_GET['token'])) {
    header('Location: /');
    exit;
}

$token = $_GET['token'];
$db = Database::getInstance()->getConnection();

// Buscar el token
$stmt = $db->prepare("SELECT * FROM email_verifications WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$verification = $stmt->fetch(PDO::FETCH_ASSOC);

if ($verification) {
    // Marcar el usuario como verificado
    $stmt = $db->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
    $stmt->execute([$verification['user_id']]);
    
    // Eliminar el token usado
    $stmt = $db->prepare("DELETE FROM email_verifications WHERE id = ?");
    $stmt->execute([$verification['id']]);
    
    $_SESSION['message'] = "¡Email verificado correctamente! Ya puedes iniciar sesión.";
    header('Location: /auth/login.php');
} else {
    $_SESSION['error'] = "El enlace de verificación no es válido o ha expirado.";
    header('Location: /auth/login.php');
}
exit;
?> 