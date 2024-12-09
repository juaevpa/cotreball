<?php
session_start();
require_once '../config/database.php';

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['space_id'])) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("UPDATE spaces SET approved = 1 WHERE id = ?");
    $stmt->execute([$_POST['space_id']]);
}

header('Location: /admin');
exit; 