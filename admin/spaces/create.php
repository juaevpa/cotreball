<?php
session_start();
require_once '../../config/database.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $price = $_POST['price'] !== '' ? $_POST['price'] : null;
    $price_month = $_POST['price_month'] !== '' ? $_POST['price_month'] : null;
    $lat = $_POST['lat'] ?? '';
    $lng = $_POST['lng'] ?? '';
    $available = isset($_POST['available']) ? 1 : 0;

    $errors = [];

    if (empty($name)) {
        $errors[] = "El nombre es obligatorio";
    }
    if (empty($description)) {
        $errors[] = "La descripción es obligatoria";
    }
    if (empty($address)) {
        $errors[] = "La dirección es obligatoria";
    }
    if (empty($city)) {
        $errors[] = "La ciudad es obligatoria";
    }
    if (empty($lat) || empty($lng)) {
        $errors[] = "Por favor, selecciona una ubicación en el mapa";
    }

    if (empty($_FILES['images']['name'][0])) {
        $errors[] = "Debes subir al menos una imagen";
    }

    if (empty($errors)) {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();

            // Generar slug a partir del nombre
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

            // Verificar si el slug ya existe y añadir un número si es necesario
            $stmt = $db->prepare("SELECT COUNT(*) FROM spaces WHERE slug = ?");
            $stmt->execute([$slug]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $originalSlug = $slug;
                $counter = 1;
                do {
                    $slug = $originalSlug . '-' . $counter;
                    $stmt->execute([$slug]);
                    $count = $stmt->fetchColumn();
                    $counter++;
                } while ($count > 0);
            }

            // Modificar la consulta INSERT para incluir el slug
            $stmt = $db->prepare("
                INSERT INTO spaces (name, description, address, city, price, price_month, lat, lng, available, user_id, slug)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$name, $description, $address, $city, $price, $price_month, $lat, $lng, $available, $_SESSION['user_id'], $slug]);
            $spaceId = $db->lastInsertId();

            $uploadDir = '../../uploads/spaces/' . $spaceId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $stmtImage = $db->prepare("
                INSERT INTO space_images (space_id, image_path, is_primary)
                VALUES (?, ?, ?)
            ");

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $fileName = uniqid() . '_' . $_FILES['images']['name'][$key];
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($tmp_name, $filePath)) {
                        $isPrimary = $key === 0 ? 1 : 0;
                        $relativePath = 'uploads/spaces/' . $spaceId . '/' . $fileName;
                        $stmtImage->execute([$spaceId, $relativePath, $isPrimary]);
                    }
                }
            }

            $db->commit();

            if (!$_SESSION['is_admin']) {
                $_SESSION['message'] = "Espacio enviado, esperando aprobación.";
            }

            header('Location: /user/spaces.php');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Error al crear el espacio: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Crear Espacio - Cotreball';
$extraStyles = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
];
$headScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://code.jquery.com/jquery-3.7.1.min.js'
];
require_once '../../includes/head.php';
require_once '../../includes/header.php';
?>

<div class="container">
    <div class="admin-form">
        <h1>Crear Nuevo Espacio</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Nombre del espacio</label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="city">Ciudad</label>
                <input type="text" id="city" name="city" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="address">Dirección</label>
                <input type="text" id="address" name="address" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                <div id="suggestions"></div>
            </div>

            <div id="map" style="height: 300px; margin-bottom: 1rem;"></div>

            <div class="form-group">
                <label for="price">Precio por día (€)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="price_month">Precio mensual (€)</label>
                <input type="number" id="price_month" name="price_month" step="0.01" value="<?php echo isset($_POST['price_month']) ? htmlspecialchars($_POST['price_month']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="images">Imágenes del espacio</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*" required>
                <span class="form-help">Puedes seleccionar múltiples imágenes. La primera será la imagen principal.</span>
            </div>

            <input type="hidden" id="lat" name="lat" value="<?php echo isset($_POST['lat']) ? htmlspecialchars($_POST['lat']) : ''; ?>">
            <input type="hidden" id="lng" name="lng" value="<?php echo isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : ''; ?>">

            <div class="form-group checkbox-group">
                <input type="checkbox" id="available" name="available" <?php echo isset($_POST['available']) ? 'checked' : ''; ?>>
                <label for="available">Espacio disponible</label>
            </div>

            <button type="submit" class="btn btn-primary">Crear Espacio</button>
        </form>
    </div>
</div>

<script>
// Inicializar mapa fuera del document.ready
const locationMap = L.map('map').setView([40.4637, -3.7492], 6);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(locationMap);

let locationMarker;

$(document).ready(function() {
    let typingTimer;
    const doneTypingInterval = 500;
    const addressInput = $('#address');
    const suggestionsDiv = $('#suggestions');
    const latInput = $('#lat');
    const lngInput = $('#lng');
    const cityInput = $('#city');

    addressInput.on('input', function() {
        clearTimeout(typingTimer);
        if (addressInput.val()) {
            typingTimer = setTimeout(getSuggestions, doneTypingInterval);
        } else {
            suggestionsDiv.empty();
        }
    });

    function getSuggestions() {
        const query = addressInput.val();
        const city = cityInput.val();

        if (query.length < 2) return;

        if (!city) {
            alert('Por favor, selecciona primero una ciudad');
            return;
        }

        const searchQuery = `${query}, ${city}, España`;
        
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&countrycodes=es&limit=5&addressdetails=1`)
            .then(response => response.json())
            .then(data => {
                suggestionsDiv.empty();
                
                const results = Array.isArray(data) ? data : [data];
                
                if (results.length === 0 || !results[0]) {
                    suggestionsDiv.append($('<div>').addClass('suggestion').text('No se encontraron resultados'));
                    return;
                }

                results.forEach(place => {
                    if (!place) return;
                    
                    let displayAddress = '';
                    if (place.address) {
                        const parts = [];
                        if (place.address.road || place.address.pedestrian) {
                            let street = place.address.road || place.address.pedestrian;
                            if (place.address.house_number) {
                                street += ` ${place.address.house_number}`;
                            }
                            parts.push(street);
                        }
                        if (place.address.suburb && place.address.suburb !== parts[0]) {
                            parts.push(place.address.suburb);
                        }
                        displayAddress = parts.join(', ');
                    }

                    if (!displayAddress) {
                        displayAddress = place.display_name.split(',')[0];
                    }

                    const div = $('<div>')
                        .addClass('suggestion')
                        .text(displayAddress)
                        .on('click', function() {
                            addressInput.val(displayAddress);
                            latInput.val(place.lat);
                            lngInput.val(place.lon);
                            
                            const newLatLng = [place.lat, place.lon];
                            if (locationMarker) {
                                locationMarker.setLatLng(newLatLng);
                            } else {
                                locationMarker = L.marker(newLatLng).addTo(locationMap);
                            }
                            locationMap.setView(newLatLng, 15);
                            
                            suggestionsDiv.empty();
                        });
                    suggestionsDiv.append(div);
                });
            })
            .catch(error => {
                suggestionsDiv.append($('<div>').addClass('suggestion').text('Error al buscar direcciones'));
            });
    }

    // Cerrar sugerencias al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#suggestions, #address').length) {
            suggestionsDiv.empty();
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?> 