<?php
session_start();
require_once '../../config/database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = Database::getInstance()->getConnection();
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO spaces (
                name, description, city, address, lat, lng, 
                price, price_month, available, user_id
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
            $_POST['price_month'],
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

// Configuración de la página
$pageTitle = 'Crear Espacio - Cotreball';
$extraStyles = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
];
$headScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
];

require_once '../../includes/head.php';
require_once '../../includes/header.php';
?>

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
                <div class="address-wrapper">
                    <input type="text" id="address" name="address" required>
                    <div id="address-suggestions" class="suggestions-list"></div>
                </div>
                <small class="form-help">
                    Introduce la dirección completa, por ejemplo: "Calle Gran Vía 28" o "Passeig de Gràcia 43"
                </small>
            </div>
            
            <div id="map" style="height: 300px; margin-bottom: 1rem;"></div>
            
            <input type="hidden" id="lat" name="lat" required>
            <input type="hidden" id="lng" name="lng" required>
            
            <div class="form-group">
                <div class="price-inputs">
                    <div class="price-input">
                        <label for="price">Precio por día:</label>
                        <div class="input-with-symbol">
                            <input type="number" id="price" name="price" step="0.01" required>
                            <span class="currency-symbol">€</span>
                        </div>
                    </div>
                    <div class="price-input">
                        <label for="price_month">Precio por mes:</label>
                        <div class="input-with-symbol">
                            <input type="number" id="price_month" name="price_month" step="0.01" required>
                            <span class="currency-symbol">€</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="checkbox-label">
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
    // Inicializar mapa
    const map = L.map('map').setView([40.4637, -3.7492], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker;

    // Función para obtener sugerencias de direcciones
    async function getSuggestions(query) {
        if (!query || query.length < 5) return [];
        
        const city = document.getElementById('city').value;
        if (!city) return [];
        
        const searchQuery = city ? `${query}, ${city}, Spain` : `${query}, Spain`;
        
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?` + 
                `format=json&q=${encodeURIComponent(searchQuery)}&limit=5&addressdetails=1&countrycodes=es`
            );
            const data = await response.json();
            return data.map(item => ({
                display: item.display_name,
                lat: item.lat,
                lon: item.lon
            }));
        } catch (error) {
            console.error('Error al obtener sugerencias:', error);
            return [];
        }
    }

    // Función para mostrar sugerencias
    function showSuggestions(suggestions) {
        const suggestionsList = document.getElementById('address-suggestions');
        suggestionsList.innerHTML = '';
        
        if (suggestions.length > 0) {
            suggestions.forEach(suggestion => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                div.textContent = suggestion.display;
                div.addEventListener('click', () => {
                    // Mantener el texto original del input y añadir la sugerencia solo si no hay número
                    const currentAddress = document.getElementById('address').value;
                    const hasNumber = /\d/.test(currentAddress);
                    if (!hasNumber) {
                        const addressParts = suggestion.display.split(',');
                        document.getElementById('address').value = addressParts[0].trim();
                    }
                    document.getElementById('lat').value = suggestion.lat;
                    document.getElementById('lng').value = suggestion.lon;
                    
                    // Actualizar marcador y mapa
                    const lat = parseFloat(suggestion.lat);
                    const lng = parseFloat(suggestion.lon);
                    if (marker) {
                        marker.setLatLng([lat, lng]);
                    } else {
                        marker = L.marker([lat, lng]).addTo(map);
                    }
                    map.setView([lat, lng], 15);
                    
                    suggestionsList.classList.remove('active');
                    addressInput.focus();
                });
                suggestionsList.appendChild(div);
            });
            suggestionsList.classList.add('active');
        } else {
            suggestionsList.classList.remove('active');
        }
    }

    // Actualizar mapa cuando se modifica la dirección o ciudad
    const addressInput = document.getElementById('address');
    const cityInput = document.getElementById('city');
    
    let timeoutId;
    
    function handleAddressChange() {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(async () => {
            const suggestions = await getSuggestions(addressInput.value);
            showSuggestions(suggestions);
        }, 500);
    }

    addressInput.addEventListener('input', handleAddressChange);
    cityInput.addEventListener('input', handleAddressChange);

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', (e) => {
        const suggestionsList = document.getElementById('address-suggestions');
        if (!e.target.closest('.address-wrapper')) {
            suggestionsList.classList.remove('active');
        }
    });

    // Validar formulario antes de enviar
    document.querySelector('form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const lat = document.getElementById('lat').value;
        const lng = document.getElementById('lng').value;
        
        if (!lat || !lng) {
            alert('Por favor, selecciona una ubicación válida del mapa o de las sugerencias');
            return;
        }
        
        this.submit();
    });
</script>

<?php require_once '../../includes/footer.php'; ?> 