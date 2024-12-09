<?php
session_start();
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidad - Cotreball</title>
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
        <h1>Política de Privacidad</h1>

        <h2>1. Información que Recopilamos</h2>
        <p>Recopilamos información personal cuando se registra, publica espacios o interactúa con nuestro sitio.</p>

        <h2>2. Uso de la Información</h2>
        <p>Utilizamos su información para proporcionar y mejorar nuestros servicios, procesar transacciones y comunicarnos con usted.</p>

        <h2>3. Protección de Datos</h2>
        <p>Implementamos medidas de seguridad para proteger su información personal de acuerdo con el RGPD.</p>

        <h2>4. Cookies</h2>
        <p>Utilizamos cookies para mejorar su experiencia de navegación y analizar el uso del sitio.</p>

        <h2>5. Sus Derechos</h2>
        <p>Tiene derecho a acceder, rectificar y eliminar sus datos personales.</p>

        <h2>6. Contacto</h2>
        <p>Para cualquier consulta sobre privacidad, contacte con nosotros en privacy@cotreball.com</p>
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