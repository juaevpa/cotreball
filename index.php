<?php
session_start();
require_once 'config/database.php';

// Configuración de la página
$pageTitle = 'Cotreball - Espacios de Coworking en España';
$extraStyles = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
];
$headScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://code.jquery.com/jquery-3.7.1.min.js'
];
$scripts = [];

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

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

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
                    <?php if ((isset($space['price']) && $space['price'] !== null) || (isset($space['price_month']) && $space['price_month'] !== null)): ?>
                        <div class="prices">
                            <?php if (isset($space['price']) && $space['price'] !== null): ?>
                                <p class="price"><?php echo number_format($space['price'], 2); ?>€/día</p>
                            <?php endif; ?>
                            <?php if (isset($space['price_month']) && $space['price_month'] !== null): ?>
                                <p class="monthly-price"><?php echo number_format($space['price_month'], 2); ?>€/mes</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <p class="availability">
                        <span class="<?php echo $space['available'] ? 'available' : 'unavailable'; ?>">
                            <?php echo $space['available'] ? 'Disponible' : 'No disponible'; ?>
                        </span>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

<?php require_once 'includes/footer.php'; ?> 