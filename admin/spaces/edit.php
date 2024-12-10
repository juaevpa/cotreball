<?php
session_start();
require_once '../../config/database.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Obtener el espacio
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /admin');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM spaces WHERE id = ?");
$stmt->execute([$id]);
$space = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$space) {
    header('Location: /admin');
    exit;
}

// Verificar si el usuario es el propietario o admin
if (!$_SESSION['is_admin'] && $space['user_id'] !== $_SESSION['user_id']) {
    header('Location: /admin');
    exit;
}

$stmtImages = $db->prepare("SELECT * FROM space_images WHERE space_id = ? ORDER BY is_primary DESC");
$stmtImages->execute([$id]);
$images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

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
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;

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

    if (isset($_POST['delete_image'])) {
        $imageId = $_POST['delete_image'];

        try {
            $db->beginTransaction();

            // Obtener la ruta de la imagen
            $stmt = $db->prepare("SELECT image_path FROM space_images WHERE id = ?");
            $stmt->execute([$imageId]);
            $imagePath = $stmt->fetchColumn();

            if ($imagePath && file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
                unlink($_SERVER['DOCUMENT_ROOT'] . $imagePath);
            }

            // Eliminar la imagen de la base de datos
            $stmt = $db->prepare("DELETE FROM space_images WHERE id = ?");
            $stmt->execute([$imageId]);

            $db->commit();
            header("Location: /admin/spaces/edit.php?id=" . $id);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Error al eliminar la imagen: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Actualizar información del espacio
            $stmt = $db->prepare("
                UPDATE spaces 
                SET name = ?, description = ?, address = ?, city = ?, 
                    price = ?, price_month = ?, lat = ?, lng = ?, available = ?,
                    email = ?, phone = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $name, $description, $address, $city, 
                $price, $price_month, $lat, $lng, $available,
                $email, $phone, $id
            ]);

            // Procesar nuevas imágenes
            if (!empty($_FILES['new_images']['name'][0])) {
                $uploadDir = '/uploads/spaces/' . $id . '/';
                if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $uploadDir)) {
                    mkdir($_SERVER['DOCUMENT_ROOT'] . $uploadDir, 0777, true);
                }

                foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $fileName = uniqid() . '_' . $_FILES['new_images']['name'][$key];
                        $filePath = $uploadDir . $fileName;

                        if (move_uploaded_file($tmp_name, $_SERVER['DOCUMENT_ROOT'] . $filePath)) {
                            $stmt = $db->prepare("INSERT INTO space_images (space_id, image_path, is_primary) VALUES (?, ?, ?)");
                            // La primera imagen será la principal si no hay otras imágenes
                            $isPrimary = empty($images) && $key === 0 ? 1 : 0;
                            $stmt->execute([$id, $filePath, $isPrimary]);
                        }
                    }
                }
            }

            $db->commit();
            header('Location: /admin');
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Error al actualizar el espacio: " . $e->getMessage();
        }
    }
}

$pageTitle = 'Editar Espacio - Cotreball';
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
        <h1>Editar Espacio</h1>

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
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($space['name']); ?>">
            </div>

            <div class="form-group">
                <label for="description">Descripción</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($space['description']); ?></textarea>
            </div>

            <div class="form-group">
                <label for="address">Dirección</label>
                <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($space['address']); ?>">
                <div id="suggestions"></div>
            </div>

            <div class="form-group">
                <label for="city">Ciudad</label>
                <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($space['city']); ?>">
            </div>

            <div id="map" style="height: 300px; margin-bottom: 1rem;"></div>

            <div class="form-group">
                <label for="price">Precio por día (€)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo isset($space['price']) && $space['price'] !== null ? number_format((float)$space['price'], 2, '.', '') : ''; ?>">
            </div>

            <div class="form-group">
                <label for="price_month">Precio mensual (€)</label>
                <input type="number" id="price_month" name="price_month" step="0.01" value="<?php echo isset($space['price_month']) && $space['price_month'] !== null ? number_format((float)$space['price_month'], 2, '.', '') : ''; ?>">
            </div>

            <input type="hidden" id="lat" name="lat" value="<?php echo htmlspecialchars($space['lat']); ?>">
            <input type="hidden" id="lng" name="lng" value="<?php echo htmlspecialchars($space['lng']); ?>">

            <div class="form-group checkbox-group">
                <input type="checkbox" id="available" name="available" <?php echo $space['available'] ? 'checked' : ''; ?>>
                <label for="available">Espacio disponible</label>
            </div>

            <div class="form-group">
                <label>Imágenes actuales</label>
                <div class="current-images">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $image): ?>
                            <div class="image-container">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Imagen del espacio">
                                <button type="submit" name="delete_image" value="<?php echo $image['id']; ?>" class="delete-image">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="new_images">Añadir nuevas imágenes</label>
                <input type="file" id="new_images" name="new_images[]" multiple accept="image/*">
                <small>Puedes seleccionar múltiples imágenes. La primera imagen será la principal si no hay otras imágenes.</small>
            </div>

            <div class="form-group">
                <label for="email">Email de contacto</label>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($space['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Teléfono de contacto</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($space['phone'] ?? ''); ?>">
            </div>

            <button type="submit" class="btn btn-primary">Actualizar Espacio</button>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar mapa
    const map = L.map('map').setView([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker = L.marker([<?php echo $space['lat']; ?>, <?php echo $space['lng']; ?>]).addTo(map);

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
                            marker.setLatLng(newLatLng);
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