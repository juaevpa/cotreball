<?php
session_start();
require_once 'config/database.php';

// Obtener la conexión a la base de datos
$pdo = Database::getInstance()->getConnection();

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

// Obtener la consulta de búsqueda
$searchQuery = $_GET['search'] ?? '';

// Cargar datos de espacios de coworking
if (!empty($searchQuery)) {
    $stmt = $pdo->prepare("SELECT * FROM spaces WHERE approved = 1 AND city LIKE :search or name LIKE :search  ORDER BY created_at DESC");
    $searchParam = "%{$searchQuery}%";
    $stmt->bindParam(':search', $searchParam);
} else {
    $stmt = $pdo->prepare("SELECT * FROM spaces WHERE approved = 1 ORDER BY created_at DESC");
}

$stmt->execute();
$spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<main>
    <div id="map"></div>
    <div id="spacesList">
        <?php if (empty($spaces)): ?>
            <div class="no-results">
                <p>No se encontraron espacios de coworking que coincidan con tu búsqueda.</p>
                <?php if ($searchQuery): ?>
                    <p>Prueba con otros términos de búsqueda o <a href="index.php">ver todos los espacios</a>.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($spaces as $space): ?>
                <div class="space-card" 
                     data-id="<?php echo $space['id']; ?>"
                     data-lat="<?php echo $space['lat']; ?>"
                     data-lng="<?php echo $space['lng']; ?>">
                    <h3>
                        <a href="/espacio/<?php echo htmlspecialchars($space['slug']); ?>">
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
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
    // Inicializar el mapa
    var map = L.map('map').setView([40.4168, -3.7038], 6); // Centrado en España

    // Añadir capa de mapa
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Añadir marcadores
    <?php foreach ($spaces as $space): ?>
        L.marker([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>])
            .addTo(map)
            .bindPopup("<?php echo addslashes("<div class=\"map-popup\"><h3><a href=\"/espacio/{$space['slug']}\">" . htmlspecialchars($space['name']) . "</a></h3><p>" . htmlspecialchars($space['city']) . "</p><a href=\"/espacio/{$space['slug']}\" class=\"button\">Ver detalles</a></div>"); ?>");
    <?php endforeach; ?>
</script>

<?php require_once 'includes/footer.php'; ?>