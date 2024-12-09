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
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_admin'] = $user['is_admin'];
        if ($user['is_admin']) {
            header('Location: /admin');
        } else {
            header('Location: /');
        }
        exit;
    }

    $error = "Credenciales inválidas";
}

// Configuración de la página
$pageTitle = 'Iniciar Sesión - Cotreball';
$showSearch = false;

require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="auth-container">
    <a href="/" class="back-link">← Volver al inicio</a>
    <h2>Iniciar Sesión</h2>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/auth/login.php">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Iniciar Sesión</button>
    </form>
    
    <p>¿No tienes cuenta? <a href="/auth/register.php">Regístrate aquí</a></p>
</div>

<?php require_once '../includes/footer.php'; ?> 