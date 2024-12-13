<?php
session_start();
require_once '../../config/database.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /login');
    exit;
}

// Obtener el espacio
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /admin');
    exit;
}
$pdo = Database::getInstance()->getConnection();

$stmt = $pdo->prepare("SELECT * FROM spaces WHERE id = ?");
$stmt->execute([$id]);
$space = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$space) {
    header('Location: /admin');
    exit;
}

$stmtImages = $pdo->prepare("SELECT * FROM space_images WHERE space_id = ? ORDER BY is_primary DESC");
$stmtImages->execute([$id]);
$images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Procesar eliminación de imagen si se solicita
        if (isset($_POST['delete_image'])) {
            $imageId = $_POST['delete_image'];
            
            // Obtener la ruta de la imagen
            $stmt = $pdo->prepare("SELECT image_path FROM space_images WHERE id = ?");
            $stmt->execute([$imageId]);
            $imagePath = $stmt->fetchColumn();

            if ($imagePath && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $imagePath)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $imagePath);
            }

            // Eliminar la imagen de la base de datos
            $stmt = $pdo->prepare("DELETE FROM space_images WHERE id = ?");
            $stmt->execute([$imageId]);

            $pdo->commit();
            header("Location: /admin/spaces/edit.php?id=" . $id);
            exit;
        }

        // Convertir precios a NULL si están vacíos
        $prices = ['price_day', 'price_week', 'price_month', 'price_fixed'];
        foreach ($prices as $price) {
            $_POST[$price] = !empty($_POST[$price]) ? str_replace(',', '.', $_POST[$price]) : null;
        }

        // Actualizar información del espacio
        $stmt = $pdo->prepare("
            UPDATE spaces 
            SET name = :name,
                description = :description,
                city = :city,
                address = :address,
                lat = :lat,
                lng = :lng,
                phone = :phone,
                email = :email,
                price_day = :price_day,
                price_week = :price_week,
                price_month = :price_month,
                price_fixed = :price_fixed,
                schedule = :schedule,
                services = :services,
                capacity = :capacity,
                space_types = :space_types,
                approved = :approved
            WHERE id = :id
        ");
        
        $stmt->execute([
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'city' => $_POST['city'],
            'address' => $_POST['address'],
            'lat' => $_POST['lat'],
            'lng' => $_POST['lng'],
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'price_day' => $_POST['price_day'],
            'price_week' => $_POST['price_week'],
            'price_month' => $_POST['price_month'],
            'price_fixed' => $_POST['price_fixed'],
            'schedule' => $_POST['schedule'],
            'services' => $_POST['services'],
            'capacity' => $_POST['capacity'],
            'space_types' => $_POST['space_types'],
            'approved' => isset($_POST['approved']) ? 1 : 0,
            'id' => $id
        ]);

        // Procesar nuevas imágenes
        if (!empty($_FILES['new_images']['name'][0])) {
            $uploadDir = 'uploads/spaces/' . $id . '/';
            $fullUploadDir = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir;
            
            if (!is_dir($fullUploadDir)) {
                mkdir($fullUploadDir, 0777, true);
            }

            $stmt = $pdo->prepare("
                INSERT INTO space_images (space_id, image_path, is_primary, created_at)
                VALUES (?, ?, ?, NOW())
            ");

            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . $_FILES['new_images']['name'][$key];
                    $filepath = $fullUploadDir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $relativePath = $uploadDir . $filename;
                        $isPrimary = empty($images) && $key === 0 ? 1 : 0;
                        $stmt->execute([$id, $relativePath, $isPrimary]);
                    }
                }
            }
        }

        $pdo->commit();
        header('Location: /espacio/' . $space['slug']);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al actualizar el espacio: " . $e->getMessage();
    }
}

$pageTitle = 'Editar Espacio - Cotreball';
$extraStyles = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css'
];
$headScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js'
];
require_once '../../includes/head.php';
require_once '../../includes/header.php';
?>

<div class="container">
    <h1>Editar Espacio</h1>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-form">
        <div class="form-group">
            <label for="name">Nombre *</label>
            <input type="text" id="name" name="name" required 
                value="<?php echo htmlspecialchars($space['name']); ?>">
        </div>

        <div class="form-group">
            <label for="description">Descripción *</label>
            <textarea id="description" name="description" required><?php 
                echo htmlspecialchars($space['description']); 
            ?></textarea>
        </div>

        <div class="form-section">
            <h3>Ubicación</h3>
            
            <div class="form-group">
                <label for="city">Ciudad *</label>
                <input type="text" id="city" name="city" required 
                    value="<?php echo htmlspecialchars($space['city']); ?>">
            </div>

            <div class="form-group">
                <label for="address">Dirección *</label>
                <input type="text" id="address" name="address" required 
                    value="<?php echo htmlspecialchars($space['address']); ?>"
                    placeholder="Primero selecciona una ciudad">
            </div>

            <div class="form-row" style="display: none;">
                <div class="form-group">
                    <label for="lat">Latitud *</label>
                    <input type="number" id="lat" name="lat" step="any" required 
                        value="<?php echo htmlspecialchars($space['lat']); ?>">
                </div>

                <div class="form-group">
                    <label for="lng">Longitud *</label>
                    <input type="number" id="lng" name="lng" step="any" required 
                        value="<?php echo htmlspecialchars($space['lng']); ?>">
                </div>
            </div>

            <div id="map" style="height: 300px;"></div>
        </div>

        <div class="form-section">
            <h3>Precios</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price_day">Precio por día</label>
                    <input type="number" id="price_day" name="price_day" step="0.01" 
                        value="<?php echo isset($space['price_day']) ? number_format($space['price_day'], 2, '.', '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="price_week">Precio por semana</label>
                    <input type="number" id="price_week" name="price_week" step="0.01" 
                        value="<?php echo isset($space['price_week']) ? number_format($space['price_week'], 2, '.', '') : ''; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price_month">Precio por mes</label>
                    <input type="number" id="price_month" name="price_month" step="0.01" 
                        value="<?php echo isset($space['price_month']) ? number_format($space['price_month'], 2, '.', '') : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="price_fixed">Precio puesto fijo</label>
                    <input type="number" id="price_fixed" name="price_fixed" step="0.01" 
                        value="<?php echo isset($space['price_fixed']) ? number_format($space['price_fixed'], 2, '.', '') : ''; ?>">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Detalles Adicionales</h3>

            <div class="form-group">
                <label for="schedule">Horario</label>
                <textarea id="schedule" name="schedule"><?php 
                    echo htmlspecialchars($space['schedule'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="services">Servicios</label>
                <textarea id="services" name="services"><?php 
                    echo htmlspecialchars($space['services'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="capacity">Capacidad</label>
                <textarea id="capacity" name="capacity"><?php 
                    echo htmlspecialchars($space['capacity'] ?? ''); 
                ?></textarea>
            </div>

            <div class="form-group">
                <label for="space_types">Tipos de Espacio</label>
                <textarea id="space_types" name="space_types"><?php 
                    echo htmlspecialchars($space['space_types'] ?? ''); 
                ?></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>Contacto</h3>
            
            <div class="form-group">
                <label for="phone">Teléfono</label>
                <input type="tel" id="phone" name="phone" 
                    value="<?php echo htmlspecialchars($space['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                    value="<?php echo htmlspecialchars($space['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-section">
            <h3>Imágenes Actuales</h3>
            <div class="current-images">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $image): ?>
                        <div class="image-container">
                            <img src="/<?php echo htmlspecialchars($image['image_path']); ?>" 
                                alt="Imagen del espacio">
                            <button type="submit" name="delete_image" value="<?php echo $image['id']; ?>" 
                                class="button delete-image">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                            <?php if ($image['is_primary']): ?>
                                <span class="primary-badge">Principal</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="new_images">Añadir nuevas imágenes (máximo 4 en total)</label>
                <input type="file" id="new_images" name="new_images[]" multiple accept="image/*">
                <small>La primera imagen será la principal si no hay otras imágenes</small>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="approved" <?php echo $space['approved'] ? 'checked' : ''; ?>>
                Espacio aprobado
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Guardar Cambios</button>
            <a href="/admin/spaces" class="button secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
    // Inicializar mapa
    const map = L.map('map').setView([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker = L.marker([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>]).addTo(map);

    // Inicializar geocodificador
    const geocoder = L.Control.Geocoder.nominatim({
        geocodingQueryParams: {
            countrycodes: 'es',
            limit: 5
        }
    });

    // Función para actualizar ubicación
    function updateLocation(latlng, address = '') {
        const lat = latlng.lat;
        const lng = latlng.lng;
        
        document.getElementById('lat').value = lat.toFixed(8);
        document.getElementById('lng').value = lng.toFixed(8);
        
        if (address) {
            document.getElementById('address').value = address;
        }

        marker.setLatLng(latlng);
        map.setView(latlng, 15);
    }

    // Función para buscar dirección
    function searchAddress(address) {
        const city = document.getElementById('city').value;
        if (!city) {
            alert('Por favor, introduce primero la ciudad');
            return;
        }

        const searchQuery = `${address}, ${city}, España`;
        geocoder.geocode(searchQuery, function(results) {
            if (results.length > 0) {
                const result = results[0];
                updateLocation(result.center, result.name);
            }
        });
    }

    // Evento de cambio en el campo de dirección
    let timeoutId;
    document.getElementById('address').addEventListener('input', function() {
        clearTimeout(timeoutId);
        const address = this.value;
        
        if (address.length > 3) {
            timeoutId = setTimeout(() => {
                searchAddress(address);
            }, 500);
        }
    });

    // Evento de cambio de ciudad
    document.getElementById('city').addEventListener('change', function() {
        const addressInput = document.getElementById('address');
        addressInput.value = '';
        
        if (this.value) {
            addressInput.removeAttribute('readonly');
            addressInput.placeholder = "Escribe una dirección";
        } else {
            addressInput.setAttribute('readonly', 'readonly');
            addressInput.placeholder = "Primero selecciona una ciudad";
        }
    });

    // Actualizar coordenadas cuando se hace clic en el mapa
    map.on('click', function(e) {
        updateLocation(e.latlng);
    });

    // Eliminar el atributo readonly del campo de dirección al cargar la página
    // si hay una ciudad seleccionada
    window.addEventListener('load', function() {
        const cityInput = document.getElementById('city');
        const addressInput = document.getElementById('address');
        
        if (cityInput.value) {
            addressInput.removeAttribute('readonly');
            addressInput.placeholder = "Escribe una dirección";
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?> 