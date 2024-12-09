<?php
session_start();
require_once 'config/database.php';

// Configuración de la página
$pageTitle = 'Sobre Cotreball - Espacios de Coworking en España';

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<div class="about-container">
    <section class="about-hero">
        <h1>Conectando Espacios y Profesionales</h1>
        <p class="lead">Cotreball es la plataforma que facilita el encuentro entre espacios de coworking y profesionales en España.</p>
    </section>

    <section class="about-section">
        <h2>¿Qué es Cotreball?</h2>
        <p>Cotreball nace de la necesidad de simplificar la búsqueda de espacios de trabajo compartido en España. Nuestra plataforma conecta a propietarios de espacios de coworking con profesionales, autónomos y empresas que buscan un lugar inspirador para trabajar.</p>
    </section>

    <section class="about-section">
        <h2>Nuestra Misión</h2>
        <p>Facilitar el acceso a espacios de trabajo flexibles y de calidad en toda España, fomentando la colaboración y el networking entre profesionales.</p>
        
        <div class="mission-points">
            <div class="point">
                <h3>Para Profesionales</h3>
                <p>Encuentra el espacio perfecto para trabajar, con búsqueda por ubicación y filtros específicos según tus necesidades.</p>
            </div>
            
            <div class="point">
                <h3>Para Espacios</h3>
                <p>Promociona tu espacio de coworking y conecta con profesionales que buscan un lugar como el tuyo.</p>
            </div>
        </div>
    </section>

    <section class="about-section">
        <h2>¿Por qué Cotreball?</h2>
        <div class="features">
            <div class="feature">
                <h3>Simplicidad</h3>
                <p>Interfaz intuitiva y fácil de usar para encontrar o listar espacios.</p>
            </div>
            
            <div class="feature">
                <h3>Transparencia</h3>
                <p>Información clara y detallada sobre cada espacio, incluyendo precios y disponibilidad.</p>
            </div>
            
            <div class="feature">
                <h3>Comunidad</h3>
                <p>Forma parte de una red creciente de espacios y profesionales en toda España.</p>
            </div>
        </div>
    </section>

    <section class="about-section cta-section">
        <h2>Únete a Cotreball</h2>
        <p>Ya sea que busques un espacio para trabajar o quieras promocionar tu coworking, Cotreball es tu plataforma.</p>
        <div class="cta-buttons">
            <a href="/auth/register.php" class="button">Regístrate Ahora</a>
            <a href="/" class="button secondary">Explorar Espacios</a>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?> 