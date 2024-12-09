<?php
session_start();
require_once '../config/database.php';
require_once '../includes/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $db = Database::getInstance()->getConnection();
    
    // Verificar si el email existe
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Generar token y guardar
        $token = bin2hex(random_bytes(32));
        $expires = time() + (60 * 60); // 1 hora desde ahora
        
        error_log("Generando reset para usuario ID: " . $user['id']);
        error_log("Token generado: " . $token);
        error_log("Expira en timestamp: " . $expires);
        
        // Eliminar tokens anteriores del usuario
        $stmt = $db->prepare("DELETE FROM password_resets WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        $stmt = $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))");
        if ($stmt->execute([$user['id'], $token, $expires])) {
            error_log("Token guardado correctamente");
            // Enviar email
            if (EmailSender::sendPasswordResetEmail($email, $token)) {
                $_SESSION['message'] = "Se ha enviado un email con las instrucciones para restablecer tu contraseña.";
                error_log("Email enviado correctamente a: " . $email);
            } else {
                $_SESSION['error'] = "Error al enviar el email. Por favor, inténtalo de nuevo.";
                error_log("Error al enviar el email");
            }
        } else {
            error_log("Error al guardar el token: " . implode(", ", $stmt->errorInfo()));
            $_SESSION['error'] = "Error al procesar la solicitud";
        }
    } else {
        error_log("Email no encontrado: " . $email);
        // No revelar si el email existe o no por seguridad
        $_SESSION['message'] = "Si el email existe en nuestra base de datos, recibirás las instrucciones para restablecer tu contraseña.";
    }
    
    header('Location: /auth/login.php');
    exit;
}

$pageTitle = 'Recuperar Contraseña - Cotreball';
$showSearch = false;
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="auth-container">
    <h1>Recuperar Contraseña</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-messages">
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" class="auth-form">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <button type="submit">Enviar instrucciones</button>
    </form>
    
    <p class="auth-link"><a href="/auth/login.php">Volver al inicio de sesión</a></p>
</div>

<?php require_once '../includes/footer.php'; ?> 