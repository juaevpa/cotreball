<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Términos y Condiciones - Cotreball</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <h1>Cotreball</h1>
        <nav class="main-nav">
            <a href="/" class="nav-link">Inicio</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['is_admin']): ?>
                    <a href="/admin" class="nav-link">Panel Admin</a>
                <?php endif; ?>
                <a href="/admin/spaces/create.php" class="nav-link">Crear Espacio</a>
                <a href="/auth/logout.php" class="nav-link">Cerrar Sesión</a>
            <?php else: ?>
                <a href="/auth/login.php" class="nav-link">Iniciar Sesión</a>
                <a href="/auth/register.php" class="nav-link">Registrarse</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container" style="max-width: 800px; margin: 2rem auto; padding: 0 1rem;">
        <h1>Términos y Condiciones</h1>
        
        <h2>1. Introducción</h2>
        <p>Estos términos y condiciones rigen el uso del sitio web Cotreball y los servicios ofrecidos a través de él.</p>

        <h2>2. Uso del Servicio</h2>
        <p>Al utilizar Cotreball, usted acepta cumplir con estos términos y condiciones. El servicio está destinado a facilitar la búsqueda y publicación de espacios de coworking en España.</p>

        <h2>3. Registro y Cuentas de Usuario</h2>
        <p>Para publicar espacios, es necesario registrarse. Usted es responsable de mantener la confidencialidad de su cuenta y contraseña.</p>

        <h2>4. Publicación de Espacios</h2>
        <p>Los espacios publicados deben ser reales y la información proporcionada debe ser precisa y verdadera.</p>

        <h2>5. Responsabilidad</h2>
        <p>Cotreball actúa como intermediario y no es responsable de las transacciones entre usuarios.</p>

        <h2>6. Modificaciones</h2>
        <p>Nos reservamos el derecho de modificar estos términos en cualquier momento.</p>
    </div>

    <footer>
        <div class="footer-content">
            <div>© <?php echo date('Y'); ?> Cotreball</div>
            <div class="footer-links">
                <a href="/legal/terms.php">Términos y Condiciones</a>
                <a href="/legal/privacy.php">Política de Privacidad</a>
            </div>
        </div>
    </footer>
</body>
</html> 