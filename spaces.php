<?php
session_start();
require_once 'config/database.php';

// Redirigir a la home si no está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: /');
    exit;
}

// Obtener la conexión a la base de datos
$pdo = Database::getInstance()->getConnection();

// Obtener todos los espacios aprobados
$stmt = $pdo->prepare("
    SELECT * FROM spaces 
    WHERE approved = 1 
    ORDER BY created_at DESC
");
$stmt->execute();
$spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuración de la página
$pageTitle = 'Espacios - Cotreball';
require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<div class="container">
    <h1>Espacios de Coworking</h1>
    
    <div class="spaces-grid">
        <?php foreach ($spaces as $space): ?>
            <div class="space-card">
                <h3>
                    <a href="/espacio/<?php echo htmlspecialchars($space['slug']); ?>">
                        <?php echo htmlspecialchars($space['name']); ?>
                    </a>
                </h3>
                <p class="location"><?php echo htmlspecialchars($space['city']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 