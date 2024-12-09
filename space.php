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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($space['name']); ?> - Cotreball</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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

    <div class="space-detail">
        <h1><?php echo htmlspecialchars($space['name']); ?></h1>
        
        <?php if (!empty($images)): ?>
        <div class="space-images">
            <?php foreach ($images as $image): ?>
                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                     alt="Imagen de <?php echo htmlspecialchars($space['name']); ?>">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="space-info">
            <p class="price"><?php echo number_format($space['price'], 2); ?>€/día</p>
            <p class="location"><?php echo htmlspecialchars($space['city']); ?></p>
            <p class="address"><?php echo htmlspecialchars($space['address']); ?></p>
            <p class="description"><?php echo nl2br(htmlspecialchars($space['description'])); ?></p>
            
            <div class="availability">
                <span class="<?php echo $space['available'] ? 'available' : 'unavailable'; ?>">
                    <?php echo $space['available'] ? 'Disponible' : 'No disponible'; ?>
                </span>
            </div>
        </div>

        <div id="map" style="height: 400px;"></div>
    </div>

    <script>
        const map = L.map('map').setView([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        L.marker([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>])
            .bindPopup("<?php echo htmlspecialchars($space['name']); ?>")
            .addTo(map);
    </script>
</body>
</html> 