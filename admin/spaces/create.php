<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO spaces (
                name, description, city, address, lat, lng, 
                price, available, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['city'],
            $_POST['address'],
            $_POST['lat'],
            $_POST['lng'],
            $_POST['price'],
            isset($_POST['available']) ? 1 : 0,
            $_SESSION['user_id']
        ]);
        
        $space_id = $db->lastInsertId();
        
        // Procesar imágenes
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = '../../uploads/spaces/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
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
                        $key === 0 ? 1 : 0
                    ]);
                }
            }
        }
        
        $db->commit();
        header('Location: /space.php?id=' . $space_id);
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = "Error al crear el espacio: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Espacio - Cotreball</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Leaflet.js para el mapa -->
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
            <h2>Crear Nuevo Espacio</h2>
            
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Nombre:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Descripción:</label>
                    <textarea id="description" name="description" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="city">Ciudad:</label>
                    <input type="text" id="city" name="city" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Dirección:</label>
                    <input type="text" id="address" name="address" required>
                </div>
                
                <div id="map" style="height: 300px; margin-bottom: 1rem;"></div>
                
                <div class="form-group">
                    <label for="lat">Latitud:</label>
                    <input type="number" id="lat" name="lat" step="any" required>
                </div>
                
                <div class="form-group">
                    <label for="lng">Longitud:</label>
                    <input type="number" id="lng" name="lng" step="any" required>
                </div>
                
                <div class="form-group">
                    <label for="price">Precio por día:</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="available" checked>
                        Disponible
                    </label>
                </div>
                
                <div class="form-group">
                    <label for="images">Imágenes:</label>
                    <input type="file" id="images" name="images[]" multiple accept="image/*">
                    <small>Puedes seleccionar múltiples imágenes. La primera será la principal.</small>
                </div>
                
                <button type="submit" class="button">Crear Espacio</button>
            </form>
        </div>
    </div>

    <script>
        // Inicializar mapa para seleccionar ubicación
        const map = L.map('map').setView([40.4637, -3.7492], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let marker;

        // Añadir marcador al hacer clic
        map.on('click', function(e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            
            document.getElementById('lat').value = lat;
            document.getElementById('lng').value = lng;
            
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
        });
    </script>
</body>
</html> 