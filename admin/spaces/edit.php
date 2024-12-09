<?php
session_start();
require_once '../../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Obtener el espacio
$space_id = $_GET['id'] ?? null;
if (!$space_id) {
    header('Location: /');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT s.* FROM spaces s 
    WHERE s.id = ? AND (s.user_id = ? OR ? = true)
");
$stmt->execute([$space_id, $_SESSION['user_id'], $_SESSION['is_admin']]);
$space = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$space) {
    header('Location: /');
    exit;
}

// Obtener imágenes actuales
$stmt = $db->prepare("SELECT * FROM space_images WHERE space_id = ?");
$stmt->execute([$space_id]);
$current_images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Actualizar datos básicos
        $stmt = $db->prepare("
            UPDATE spaces 
            SET name = ?, description = ?, city = ?, address = ?, 
                lat = ?, lng = ?, price = ?, price_month = ?, available = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['city'],
            $_POST['address'],
            $_POST['lat'],
            $_POST['lng'],
            $_POST['price'],
            $_POST['price_month'],
            isset($_POST['available']) ? 1 : 0,
            $space_id
        ]);
        
        // Procesar nuevas imágenes
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../../uploads/spaces/';
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                $fileName = uniqid() . '_' . $_FILES['images']['name'][$key];
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmp_name, $filePath)) {
                    $stmt = $db->prepare("
                        INSERT INTO space_images (space_id, image_path, is_primary) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $space_id,
                        '/uploads/spaces/' . $fileName,
                        empty($current_images) && $key === 0 ? 1 : 0
                    ]);
                }
            }
        }
        
        // Eliminar imágenes marcadas
        if (!empty($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $image_id) {
                $stmt = $db->prepare("SELECT image_path FROM space_images WHERE id = ? AND space_id = ?");
                $stmt->execute([$image_id, $space_id]);
                $image = $stmt->fetch();
                
                if ($image) {
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $image['image_path'];
                    if (file_exists($fullPath)) {
                        unlink($fullPath);
                    }
                    
                    $stmt = $db->prepare("DELETE FROM space_images WHERE id = ?");
                    $stmt->execute([$image_id]);
                }
            }
        }
        
        $db->commit();
        header('Location: /space.php?id=' . $space_id);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al actualizar el espacio: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Espacio - Cotreball</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <header>
        <h1>Cotreball</h1>
        <nav class="main-nav">
            <a href="/" class="nav-link">Inicio</a>
            <?php if ($_SESSION['is_admin']): ?>
                <a href="/admin" class="nav-link">Panel Admin</a>
            <?php endif; ?>
            <a href="/auth/logout.php" class="nav-link">Cerrar Sesión</a>
        </nav>
    </header>

    <div class="container">
        <div class="auth-container" style="max-width: 600px;">
            <h2>Editar Espacio</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Nombre:</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($space['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción:</label>
                    <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($space['description']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="city">Ciudad:</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($space['city']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Dirección:</label>
                    <div class="address-wrapper">
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($space['address']); ?>" required>
                        <div id="address-suggestions" class="suggestions-list"></div>
                    </div>
                    <small class="form-help">
                        Introduce la dirección completa, por ejemplo: "Calle Gran Vía 28" o "Passeig de Gràcia 43"
                    </small>
                </div>
                
                <div id="map" style="height: 300px; margin-bottom: 1rem;"></div>
                
                <input type="hidden" id="lat" name="lat" value="<?php echo $space['lat']; ?>" required>
                <input type="hidden" id="lng" name="lng" value="<?php echo $space['lng']; ?>" required>
                
                <div class="form-group">
                    <div class="price-inputs">
                        <div class="price-input">
                            <label for="price">Precio por día:</label>
                            <div class="input-with-symbol">
                                <input type="number" id="price" name="price" step="0.01" value="<?php echo $space['price']; ?>" required>
                                <span class="currency-symbol">€</span>
                            </div>
                        </div>
                        <div class="price-input">
                            <label for="price_month">Precio por mes:</label>
                            <div class="input-with-symbol">
                                <input type="number" id="price_month" name="price_month" step="0.01" value="<?php echo $space['price_month']; ?>" required>
                                <span class="currency-symbol">€</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="available" <?php echo $space['available'] ? 'checked' : ''; ?>>
                        Disponible
                    </label>
                </div>
                
                <?php if (!empty($current_images)): ?>
                <div class="form-group">
                    <label>Imágenes actuales:</label>
                    <div class="current-images">
                        <?php foreach ($current_images as $image): ?>
                            <div class="image-item">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="Imagen del espacio" 
                                     style="width: 100px; height: 100px; object-fit: cover;">
                                <label>
                                    <input type="checkbox" name="delete_images[]" value="<?php echo $image['id']; ?>">
                                    Eliminar
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="images">Añadir imágenes:</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*">
                    <small>Puedes seleccionar múltiples imágenes nuevas</small>
                </div>
                
                <button type="submit" class="button">Guardar Cambios</button>
            </form>
        </div>
    </div>

    <script>
        // Inicializar mapa
        const map = L.map('map').setView([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker = L.marker([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>]).addTo(map);

        // Función para geocodificar la dirección
        async function geocodeAddress() {
            const address = document.getElementById('address').value;
            const city = document.getElementById('city').value;
            if (!address || !city) return;
            
            const fullAddress = `${address}, ${city}, Spain`;
            
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(fullAddress)}`);
                const data = await response.json();
                
                if (data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    
                    document.getElementById('lat').value = lat;
                    document.getElementById('lng').value = lng;
                    
                    marker.setLatLng([lat, lng]);
                    map.setView([lat, lng], 15);
                    
                    return true;
                } else {
                    alert('No se pudo encontrar la ubicación. Por favor, verifica la dirección.');
                    return false;
                }
            } catch (error) {
                console.error('Error al geocodificar:', error);
                alert('Error al buscar la ubicación. Por favor, inténtalo de nuevo.');
                return false;
            }
        }

        // Validar el formulario antes de enviar
        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Solo geocodificar si se ha modificado la dirección o la ciudad
            const addressChanged = addressInput.dataset.original !== addressInput.value;
            const cityChanged = cityInput.dataset.original !== cityInput.value;
            
            if (addressChanged || cityChanged) {
                if (await geocodeAddress()) {
                    this.submit();
                }
            } else {
                this.submit();
            }
        });

        // Actualizar mapa cuando se modifica la dirección o ciudad
        const addressInput = document.getElementById('address');
        const cityInput = document.getElementById('city');
        
        // Guardar valores originales
        addressInput.dataset.original = addressInput.value;
        cityInput.dataset.original = cityInput.value;
        
        let timeoutId;
        
        function handleAddressChange() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(geocodeAddress, 1000);
        }

        addressInput.addEventListener('input', handleAddressChange);
        cityInput.addEventListener('input', handleAddressChange);
    </script>
</body>
</html> 