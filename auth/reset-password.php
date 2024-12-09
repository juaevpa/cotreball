<?php
session_start();
require_once '../config/database.php';

if (!isset($_GET['token'])) {
    header('Location: /');
    exit;
}

$token = $_GET['token'];
$db = Database::getInstance()->getConnection();

// Verificar token
$stmt = $db->prepare("SELECT *, UNIX_TIMESTAMP(expires_at) as expires_unix FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch(PDO::FETCH_ASSOC);

// Depuración
error_log("Token recibido: " . $token);
error_log("Tiempo actual: " . time());
if ($reset) {
    error_log("Token encontrado, expira en timestamp: " . $reset['expires_unix']);
    error_log("¿Ha expirado? " . ($reset['expires_unix'] < time() ? "Sí" : "No"));
}

if (!$reset || $reset['expires_unix'] < time()) {
    error_log("No se encontró el token o ha expirado");
    if ($reset) {
        error_log("Token encontrado pero expiró. Diferencia de tiempo: " . ($reset['expires_unix'] - time()) . " segundos");
    } else {
        error_log("Token no encontrado en la base de datos");
    }
    
    $_SESSION['error'] = "El enlace para restablecer la contraseña no es válido o ha expirado.";
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden";
    } else {
        // Actualizar contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt->execute([$hashed_password, $reset['user_id']])) {
            // Eliminar token usado
            $stmt = $db->prepare("DELETE FROM password_resets WHERE id = ?");
            $stmt->execute([$reset['id']]);
            
            // Marcar que se completó un reset de contraseña
            $_SESSION['password_reset_completed'] = true;
            $_SESSION['message'] = "Contraseña actualizada correctamente. Ya puedes iniciar sesión.";
            header('Location: /auth/login.php');
            exit;
        } else {
            $_SESSION['error'] = "Error al actualizar la contraseña";
        }
    }
}

$pageTitle = 'Restablecer Contraseña - Cotreball';
$showSearch = false;
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="auth-container">
    <h1>Restablecer Contraseña</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-messages">
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="auth-form">
        <div class="form-group">
            <label for="password">Nueva Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirmar Nueva Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit">Cambiar Contraseña</button>
    </form>
</div>

<?php require_once '../includes/footer.php'; ?> 