<?php
session_start();
require_once 'config/database.php';

$space_id = $_GET['id'] ?? null;
if (!$space_id) {
    header('Location: /');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT s.*, u.username as owner_name 
    FROM spaces s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ? AND (s.approved = 1 OR s.user_id = ?)
");
$stmt->execute([$space_id, $_SESSION['user_id'] ?? 0]);
$space = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$space) {
    header('Location: /');
    exit;
}

// Obtener imágenes del espacio
$stmt = $db->prepare("SELECT * FROM space_images WHERE space_id = ?");
$stmt->execute([$space_id]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuración de la página
$pageTitle = htmlspecialchars($space['name']) . ' - Cotreball';
$extraStyles = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
];
$headScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];

require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="space-detail">
        <div class="space-header">
            <h1><?php echo htmlspecialchars($space['name']); ?></h1>
            <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] === $space['user_id'] || $_SESSION['is_admin'])): ?>
                <div class="space-actions">
                    <a href="/admin/spaces/edit.php?id=<?php echo $space['id']; ?>" class="button">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <?php if ($_SESSION['is_admin']): ?>
                        <form method="POST" action="/admin/spaces/delete.php" style="display: inline;" 
                            onsubmit="return confirm('¿Estás seguro de que quieres eliminar este espacio?');">
                            <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
                            <button type="submit" class="button reject">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($images)): ?>
        <div class="space-gallery">
            <div class="main-image">
                <img src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                    alt="Imagen principal de <?php echo htmlspecialchars($space['name']); ?>">
            </div>
            <?php if (count($images) > 1): ?>
            <div class="thumbnail-grid">
                <?php foreach (array_slice($images, 1) as $image): ?>
                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                        alt="Imagen de <?php echo htmlspecialchars($space['name']); ?>"
                        onclick="showImage('<?php echo htmlspecialchars($image['image_path']); ?>')">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="space-content">
            <div class="space-info">
                <div class="info-section">
                    <h2>Detalles</h2>
                    <div class="price-tag">
                        <?php if (isset($space['price']) && $space['price'] !== null): ?>
                            <div class="price-item">
                                <span class="amount"><?php echo number_format($space['price'], 2); ?>€</span>
                                <span class="period">por día</span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($space['price_month']) && $space['price_month'] !== null): ?>
                            <div class="price-item">
                                <span class="amount"><?php echo number_format($space['price_month'], 2); ?>€</span>
                                <span class="period">por mes</span>
                            </div>
                        <?php endif; ?>

                        <?php if ((!isset($space['price']) || $space['price'] === null) && (!isset($space['price_month']) || $space['price_month'] === null)): ?>
                            <div class="price-item">
                                <span class="amount">Consultar precios</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="status-tag <?php echo $space['available'] ? 'available' : 'unavailable'; ?>">
                        <?php echo $space['available'] ? 'Disponible' : 'No disponible'; ?>
                    </div>
                </div>

                <div class="info-section">
                    <h2>Ubicación</h2>
                    <p class="location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($space['address']); ?><br>
                        <?php echo htmlspecialchars($space['city']); ?>
                    </p>
                    <div id="map" class="location-map"></div>
                </div>
                
                <div class="info-section">
                    <h2>Descripción</h2>
                    <div class="description">
                        <?php echo nl2br(htmlspecialchars($space['description'])); ?>
                    </div>
                </div>

                <div class="info-section">
                    <h2>Contacto</h2>
                    <p class="owner">Publicado por: <?php echo htmlspecialchars($space['owner_name']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para imágenes -->
<div id="imageModal" class="modal">
    <span class="modal-close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
    // Inicializar mapa
    const detailMap = L.map('map').setView([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(detailMap);
    
    L.marker([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>])
        .bindPopup("<?php echo htmlspecialchars($space['name']); ?>")
        .addTo(detailMap);

    // Modal de imágenes
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const closeBtn = document.querySelector('.modal-close');
    
    function showImage(src) {
        modal.style.display = "block";
        modalImg.src = src;
    }
    
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }
    
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    }
    
    document.querySelectorAll('.space-gallery img').forEach(img => {
        img.onclick = function() {
            showImage(this.src);
        }
    });

    // Forzar actualización del mapa
    setTimeout(() => {
        detailMap.invalidateSize();
    }, 100);
</script>

<?php require_once 'includes/footer.php'; ?> 