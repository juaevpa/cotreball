<?php
session_start();
require_once 'config/database.php';

// Cargar datos de espacios de coworking
$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT s.*, u.username as owner_name 
    FROM spaces s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.approved = 1
    ORDER BY s.created_at DESC
");
$spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotreball - Espacios de Coworking en España</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Leaflet.js para el mapa -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
    <header>
        <h1>Cotreball</h1>
        <nav class="main-nav">
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
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Buscar por ciudad o provincia...">
        </div>
    </header>

    <main>
        <div id="map"></div>
        <div id="spacesList">
            <?php foreach ($spaces as $space): ?>
                <div class="space-card" 
                     data-id="<?php echo $space['id']; ?>"
                     data-lat="<?php echo $space['lat']; ?>"
                     data-lng="<?php echo $space['lng']; ?>">
                    <h3>
                        <a href="/space.php?id=<?php echo $space['id']; ?>">
                            <?php echo htmlspecialchars($space['name']); ?>
                        </a>
                    </h3>
                    <p class="location"><?php echo htmlspecialchars($space['city']); ?></p>
                    <p class="price"><?php echo number_format($space['price'], 2); ?>€/día</p>
                    <p class="description"><?php echo htmlspecialchars($space['description']); ?></p>
                    <p class="availability">
                        <span class="<?php echo $space['available'] ? 'available' : 'unavailable'; ?>">
                            <?php echo $space['available'] ? 'Disponible' : 'No disponible'; ?>
                        </span>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div>© <?php echo date('Y'); ?> Cotreball</div>
            <div class="footer-links">
                <a href="/legal/terms.php">Términos y Condiciones</a>
                <a href="/legal/privacy.php">Política de Privacidad</a>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html> 