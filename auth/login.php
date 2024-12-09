<?php
session_start();
require_once '../config/database.php';

// Procesar el formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Solo verificar email si el usuario no viene de un reset de contraseña
        if (!$user['email_verified'] && !isset($_SESSION['password_reset_completed'])) {
            $_SESSION['error'] = "Por favor, verifica tu email antes de iniciar sesión.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            // Limpiar la variable de sesión si existe
            if (isset($_SESSION['password_reset_completed'])) {
                unset($_SESSION['password_reset_completed']);
            }
            if ($user['is_admin']) {
                header('Location: /admin');
            } else {
                header('Location: /');
            }
            exit;
        }
    } else {
        $_SESSION['error'] = "Credenciales inválidas";
    }
}

// Configuración de la página
$pageTitle = 'Iniciar Sesión - Cotreball';
$showSearch = false;

require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="auth-container">
    <h1>Iniciar Sesión</h1>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-messages">
            <p class="error"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="success-messages">
            <p class="success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="/auth/login.php" class="auth-form">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Iniciar Sesión</button>
    </form>
    
    <p class="auth-link">¿Has olvidado tu contraseña? <a href="/auth/forgot-password.php">Recupérala aquí</a></p>
    <p class="auth-link">¿No tienes cuenta? <a href="/auth/register.php">Regístrate aquí</a></p>
</div>

<?php require_once '../includes/footer.php'; ?> 