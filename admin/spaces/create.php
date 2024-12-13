<?php
session_start();
require_once '../../config/database.php';

// Verificar si el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit;
}

$pdo = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generar slug único
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Verificar si el slug ya existe
        $stmt = $pdo->prepare("SELECT id FROM spaces WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $base_slug = $slug;
            $counter = 1;
            do {
                $slug = $base_slug . '-' . $counter;
                $stmt->execute([$slug]);
                $counter++;
            } while ($stmt->fetch());
        }

        // Preparar la consulta
        $stmt = $pdo->prepare("
            INSERT INTO spaces (
                name, slug, description, city, address, 
                lat, lng, phone, email,
                price_day, price_week, price_month, price_fixed,
                schedule, services, capacity, space_types,
                approved, user_id, created_at
            ) VALUES (
                :name, :slug, :description, :city, :address,
                :lat, :lng, :phone, :email,
                :price_day, :price_week, :price_month, :price_fixed,
                :schedule, :services, :capacity, :space_types,
                :approved, :user_id, NOW()
            )
        ");

        // Convertir precios a NULL si están vacíos
        $prices = ['price_day', 'price_week', 'price_month', 'price_fixed'];
        foreach ($prices as $price) {
            $_POST[$price] = !empty($_POST[$price]) ? str_replace(',', '.', $_POST[$price]) : null;
        }

        // Ejecutar la consulta
        $stmt->execute([
            'name' => $_POST['name'],
            'slug' => $slug,
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
            'user_id' => $_SESSION['user_id']
        ]);

        $space_id = $pdo->lastInsertId();

        // Procesar imágenes
        if (!empty($_FILES['images']['name'][0])) {
            $upload_dir = '../../uploads/spaces/' . $space_id;
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $stmt = $pdo->prepare("
                INSERT INTO space_images (space_id, image_path, is_primary, created_at)
                VALUES (?, ?, ?, NOW())
            ");

            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . $_FILES['images']['name'][$key];
                    $filepath = $upload_dir . '/' . $filename;
                    
                    if (move_uploaded_file($tmp_name, $filepath)) {
                        $relative_path = 'uploads/spaces/' . $space_id . '/' . $filename;
                        $is_primary = ($key === 0) ? 1 : 0;
                        $stmt->execute([$space_id, $relative_path, $is_primary]);
                    }
                }
            }
        }

        header('Location: /espacio/' . $slug);
        exit;

    } catch (PDOException $e) {
        $error = "Error al crear el espacio: " . $e->getMessage();
    }
}

// Configuración de la página
$pageTitle = "Crear Nuevo Espacio - Cotreball";
$extraStyles = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://cdn.jsdelivr.net/npm/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.min.css'
];
$headScripts = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://cdn.jsdelivr.net/npm/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.min.js'
];

require_once '../../includes/head.php';
require_once '../../includes/header.php';
?>

<div class="container">
    <h1>Crear Nuevo Espacio</h1>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-form">
        <div class="form-group">
            <label for="name">Nombre *</label>
            <input type="text" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label for="description">Descripción *</label>
            <textarea id="description" name="description" required></textarea>
        </div>

        <div class="form-section">
            <h3>Ubicación</h3>
            
            <div class="form-group">
                <label for="city">Ciudad *</label>
                <input type="text" id="city" name="city" required>
            </div>

            <div class="form-group">
                <label for="address">Dirección *</label>
                <input type="text" id="address" name="address" required readonly 
                       placeholder="Primero selecciona una ciudad">
            </div>

            <div class="form-row" style="display: none;">
                <div class="form-group">
                    <label for="lat">Latitud *</label>
                    <input type="number" id="lat" name="lat" step="any" required>
                </div>

                <div class="form-group">
                    <label for="lng">Longitud *</label>
                    <input type="number" id="lng" name="lng" step="any" required>
                </div>
            </div>

            <div id="map" style="height: 300px;"></div>
        </div>

        <div class="form-section">
            <h3>Precios</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="price_day">Precio por día</label>
                    <input type="number" id="price_day" name="price_day" step="0.01">
                </div>

                <div class="form-group">
                    <label for="price_week">Precio por semana</label>
                    <input type="number" id="price_week" name="price_week" step="0.01">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="price_month">Precio por mes</label>
                    <input type="number" id="price_month" name="price_month" step="0.01">
                </div>

                <div class="form-group">
                    <label for="price_fixed">Precio puesto fijo</label>
                    <input type="number" id="price_fixed" name="price_fixed" step="0.01">
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Detalles Adicionales</h3>

            <div class="form-group">
                <label for="schedule">Horario</label>
                <textarea id="schedule" name="schedule"></textarea>
            </div>

            <div class="form-group">
                <label for="services">Servicios</label>
                <textarea id="services" name="services"></textarea>
            </div>

            <div class="form-group">
                <label for="capacity">Capacidad</label>
                <textarea id="capacity" name="capacity"></textarea>
            </div>

            <div class="form-group">
                <label for="space_types">Tipos de Espacio</label>
                <textarea id="space_types" name="space_types"></textarea>
            </div>
        </div>

        <div class="form-section">
            <h3>Contacto</h3>
            
            <div class="form-group">
                <label for="phone">Teléfono</label>
                <input type="tel" id="phone" name="phone">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email">
            </div>
        </div>

        <div class="form-section">
            <h3>Imágenes</h3>
            
            <div class="form-group">
                <label for="images">Imágenes (máximo 4)</label>
                <input type="file" id="images" name="images[]" multiple accept="image/*" max="4">
                <small>La primera imagen será la imagen principal</small>
            </div>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="approved" checked>
                Aprobar espacio inmediatamente
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Crear Espacio</button>
            <a href="/admin/spaces" class="button secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
    // Inicializar mapa
    const map = L.map('map').setView([40.4168, -3.7038], 6); // Centro en España
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker;

    // Inicializar geocodificador personalizado
    const geocoder = L.Control.geocoder({
        defaultMarkGeocode: false,
        geocoder: new L.Control.Geocoder.Nominatim({
            geocodingQueryParams: {
                countrycodes: 'es', // Limitar a España
                limit: 5
            }
        })
    }).addTo(map);

    // Función para actualizar el marcador y los campos
    function updateLocation(latlng, address = '') {
        const lat = latlng.lat;
        const lng = latlng.lng;
        
        document.getElementById('lat').value = lat.toFixed(8);
        document.getElementById('lng').value = lng.toFixed(8);
        
        if (address) {
            document.getElementById('address').value = address;
        }

        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng).addTo(map);
        }
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
        geocoder.options.geocoder.geocode(searchQuery, function(results) {
            if (results.length > 0) {
                const result = results[0];
                updateLocation(result.center, result.name);
            }
        });
    }

    // Evento de cambio en el campo de dirección
    document.getElementById('address').addEventListener('input', function() {
        if (this.value.length > 3) { // Empezar búsqueda después de 3 caracteres
            searchAddress(this.value);
        }
    });

    // Modificar el evento del geocodificador
    geocoder.on('markgeocode', function(e) {
        const result = e.geocode;
        const latlng = result.center;
        updateLocation(latlng, result.name);
    });

    // Evento de cambio de ciudad
    document.getElementById('city').addEventListener('change', function() {
        const addressInput = document.getElementById('address');
        if (this.value) {
            addressInput.removeAttribute('readonly');
            addressInput.placeholder = "Escribe una dirección";
        } else {
            addressInput.setAttribute('readonly', 'readonly');
            addressInput.value = '';
            addressInput.placeholder = "Primero selecciona una ciudad";
        }
    });

    // Actualizar coordenadas cuando se hace clic en el mapa
    map.on('click', function(e) {
        updateLocation(e.latlng);
    });

    // Actualizar marcador cuando se cambian los inputs manualmente
    document.getElementById('lat').addEventListener('change', function() {
        const lat = parseFloat(this.value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (!isNaN(lat) && !isNaN(lng)) {
            updateLocation(L.latLng(lat, lng));
        }
    });

    document.getElementById('lng').addEventListener('change', function() {
        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(this.value);
        if (!isNaN(lat) && !isNaN(lng)) {
            updateLocation(L.latLng(lat, lng));
        }
    });

    // Búsqueda por dirección manual
    document.getElementById('address').addEventListener('change', function() {
        const address = this.value;
        if (address) {
            geocoder.options.geocoder.geocode(address, function(results) {
                if (results.length > 0) {
                    const result = results[0];
                    updateLocation(result.center, result.name);
                    
                    if (result.properties.city) {
                        document.getElementById('city').value = result.properties.city;
                    } else if (result.properties.town) {
                        document.getElementById('city').value = result.properties.town;
                    }
                }
            });
        }
    });

    // Modificar el evento de cambio de ciudad
    document.getElementById('city').addEventListener('change', function() {
        const addressInput = document.getElementById('address');
        if (this.value) {
            addressInput.removeAttribute('readonly');
            addressInput.placeholder = "Escribe una dirección";
        } else {
            addressInput.setAttribute('readonly', 'readonly');
            addressInput.value = '';
            addressInput.placeholder = "Primero selecciona una ciudad";
        }
    });

    // Asegurarse de que el campo de dirección esté readonly inicialmente
    document.addEventListener('DOMContentLoaded', function() {
        const addressInput = document.getElementById('address');
        if (!document.getElementById('city').value) {
            addressInput.setAttribute('readonly', 'readonly');
        }
    });
</script>

<?php require_once '../../includes/footer.php'; ?> 