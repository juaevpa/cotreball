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
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];
    $available = isset($_POST['available']) ? 1 : 0;
    $data_processing_accepted = isset($_POST['data_processing_accepted']) ? 1 : 0;

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
    if (!$data_processing_accepted) {
        $errors[] = "Debes aceptar el tratamiento de datos";
    }

    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO spaces (name, description, address, city, price, price_month, lat, lng, available, user_id, data_processing_accepted)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$name, $description, $address, $city, $price, $price_month, $lat, $lng, $available, $_SESSION['user_id'], $data_processing_accepted])) {
            header('Location: /admin');
            exit;
        } else {
            $errors[] = "Error al crear el espacio";
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

        <form method="POST" class="space-form">
            <div class="form-group">
                <label for="name">Nombre del espacio</label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-group">
                <label for="address">Dirección</label>
                <input type="text" id="address" name="address" required value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                <div id="suggestions"></div>
            </div>

            <div class="form-group">
                <label for="city">Ciudad</label>
                <input type="text" id="city" name="city" required value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
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

            <input type="hidden" id="lat" name="lat" value="<?php echo isset($_POST['lat']) ? htmlspecialchars($_POST['lat']) : ''; ?>">
            <input type="hidden" id="lng" name="lng" value="<?php echo isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : ''; ?>">

            <div class="form-group checkbox-group">
                <input type="checkbox" id="available" name="available" <?php echo isset($_POST['available']) ? 'checked' : ''; ?>>
                <label for="available">Espacio disponible</label>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="data_processing_accepted" name="data_processing_accepted" required>
                <label for="data_processing_accepted">Acepto el tratamiento de los datos proporcionados según la <a href="/legal/privacy.php" target="_blank">Política de Privacidad</a></label>
            </div>

            <button type="submit" class="btn btn-primary">Crear Espacio</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar mapa
    const map = L.map('map').setView([40.4637, -3.7492], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker;

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
        if (query.length < 3) return;

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=es`)
            .then(response => response.json())
            .then(data => {
                suggestionsDiv.empty();
                data.forEach(place => {
                    const div = $('<div>')
                        .addClass('suggestion')
                        .text(place.display_name)
                        .on('click', function() {
                            addressInput.val(place.display_name);
                            latInput.val(place.lat);
                            lngInput.val(place.lon);
                            cityInput.val(place.address?.city || place.address?.town || place.address?.village || '');
                            
                            // Actualizar marcador y mapa
                            const newLatLng = [place.lat, place.lon];
                            if (marker) {
                                marker.setLatLng(newLatLng);
                            } else {
                                marker = L.marker(newLatLng).addTo(map);
                            }
                            map.setView(newLatLng, 15);
                            
                            suggestionsDiv.empty();
                        });
                    suggestionsDiv.append(div);
                });
            })
            .catch(error => console.error('Error:', error));
    }

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#suggestions, #address').length) {
            suggestionsDiv.empty();
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?> 