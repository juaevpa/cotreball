<?php
session_start();
require_once 'config/database.php';

// Configuración de la página
$pageTitle = 'Sobre Cotreball - Espacios de Coworking en España';

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<div class="about-content">
    <div class="about-hero">
        <h1>Conectamos espacios de coworking con profesionales</h1>
        <p class="lead">La plataforma que facilita encontrar y publicar espacios de trabajo compartido en toda España</p>
    </div>
    
    <section class="about-section">
        <h2>¿Cómo funciona Cotreball?</h2>
        
        <div class="process-steps">
            <div class="step">
                <i class="fas fa-user-plus"></i>
                <h3>1. Regístrate gratis</h3>
                <p>Crear una cuenta en Cotreball es completamente gratuito y solo te llevará unos minutos.</p>
            </div>

            <div class="step">
                <i class="fas fa-building"></i>
                <h3>2. Añade tu espacio</h3>
                <p>Como usuario registrado, puedes publicar cualquier espacio de coworking o trabajo compartido. El proceso es sencillo y guiado.</p>
            </div>

            <div class="step">
                <i class="fas fa-check-circle"></i>
                <h3>3. Verificación y publicación</h3>
                <p>Nuestro equipo revisará la información proporcionada para garantizar la calidad y veracidad de los espacios listados. Una vez aprobado, tu espacio será visible para todos los usuarios.</p>
            </div>
        </div>
    </section>

    <section class="about-section">
        <h2>¿Por qué usar Cotreball?</h2>
        <ul class="benefits-list">
            <li><i class="fas fa-check"></i> Publicación gratuita de espacios de coworking</li>
            <li><i class="fas fa-check"></i> Proceso de verificación que garantiza la calidad de los listados</li>
            <li><i class="fas fa-check"></i> Plataforma especializada en espacios de trabajo compartido</li>
            <li><i class="fas fa-check"></i> Búsqueda fácil por ciudad o provincia</li>
        </ul>
    </section>

    <section class="about-section">
        <h2>¿Qué es Cotreball?</h2>
        <p>Cotreball es una plataforma colaborativa que nace con el objetivo de conectar espacios de coworking con profesionales y empresas que buscan un lugar de trabajo flexible. Facilitamos que los gestores de espacios puedan dar visibilidad a sus instalaciones, mientras ayudamos a los profesionales a encontrar el espacio perfecto para sus necesidades.</p>
        <p>Nuestro nombre, "Cotreball", combina "co" (colaboración) y "treball" (trabajo en valenciano), reflejando nuestra misión de fomentar el trabajo colaborativo y crear una comunidad vibrante de profesionales y espacios de trabajo compartido.</p>
    </section>

    <section class="about-section">
        <h2>¿Listo para empezar?</h2>
        <p>Únete a nuestra comunidad y forma parte de la red de espacios de coworking más completa.</p>
        <div class="cta-buttons">
            <a href="/auth/register.php" class="btn btn-primary">Registrarse</a>
            <a href="/admin/spaces/create.php" class="btn btn-secondary">Crear Espacio</a>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?> 