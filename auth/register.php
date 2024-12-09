<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $privacy_accepted = isset($_POST['privacy_accepted']) ? 1 : 0;
    $terms_accepted = isset($_POST['terms_accepted']) ? 1 : 0;

    $errors = [];

    if (empty($username)) {
        $errors[] = "El nombre de usuario es obligatorio";
    }
    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    }
    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Las contraseñas no coinciden";
    }
    if (!$privacy_accepted || !$terms_accepted) {
        $errors[] = "Debes aceptar la política de privacidad y los términos y condiciones";
    }

    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();
        
        // Verificar si el usuario ya existe
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "El usuario o email ya existe";
        } else {
            // Crear el usuario
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password, privacy_accepted, terms_accepted) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password, $privacy_accepted, $terms_accepted])) {
                $_SESSION['user_id'] = $db->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = false;
                header("Location: /");
                exit;
            } else {
                $errors[] = "Error al crear el usuario";
            }
        }
    }
}

$pageTitle = 'Registro - Cotreball';
require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<main class="auth-container">
    <h1>Registro</h1>

    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <?php foreach ($errors as $error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="auth-form">
        <div class="form-group">
            <label for="username">Nombre de usuario</label>
            <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirmar Contraseña</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="privacy_accepted" name="privacy_accepted" required>
            <label for="privacy_accepted">He leído y acepto la <a href="/legal/privacy.php" target="_blank">Política de Privacidad</a> y el tratamiento de mis datos personales</label>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" id="terms_accepted" name="terms_accepted" required>
            <label for="terms_accepted">He leído y acepto los <a href="/legal/terms.php" target="_blank">Términos y Condiciones</a> del servicio</label>
        </div>

        <button type="submit" class="btn btn-primary">Registrarse</button>
    </form>

    <p class="auth-link">¿Ya tienes cuenta? <a href="/auth/login.php">Inicia sesión</a></p>
</main>

<?php require_once '../includes/footer.php'; ?> 