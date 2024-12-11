<?php
session_start();
require_once '../../config/database.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$spaceId = $_GET['id'] ?? null;
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT * FROM spaces WHERE id = ? AND user_id = ?");
$stmt->execute([$spaceId, $_SESSION['user_id']]);
$space = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$space) {
    header('Location: /user/spaces.php');
    exit;
}

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $city = $_POST['city'];
    $address = $_POST['address'];
    $price = $_POST['price'] !== '' ? $_POST['price'] : null;
    $price_month = $_POST['price_month'] !== '' ? $_POST['price_month'] : null;

    $stmt = $db->prepare("
        UPDATE spaces SET name = ?, description = ?, city = ?, address = ?, price = ?, price_month = ?
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$name, $description, $city, $address, $price, $price_month, $spaceId, $_SESSION['user_id']]);

    $_SESSION['message'] = "Espacio actualizado.";
    header('Location: /user/spaces.php');
    exit;
}

require_once '../../includes/head.php';
require_once '../../includes/header.php';
?>

<div class="container">
    <h1>Editar Espacio</h1>

    <form method="POST" class="space-form">
        <div class="form-group">
            <label for="name">Nombre del espacio</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($space['name']); ?>">
        </div>

        <div class="form-group">
            <label for="description">Descripción</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($space['description']); ?></textarea>
        </div>

        <div class="form-group">
            <label for="city">Ciudad</label>
            <input type="text" id="city" name="city" required value="<?php echo htmlspecialchars($space['city']); ?>">
        </div>

        <div class="form-group">
            <label for="address">Dirección</label>
            <input type="text" id="address" name="address" required value="<?php echo htmlspecialchars($space['address']); ?>">
        </div>

        <div class="form-group">
            <label for="price">Precio por día (€)</label>
            <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($space['price']); ?>">
        </div>

        <div class="form-group">
            <label for="price_month">Precio mensual (€)</label>
            <input type="number" id="price_month" name="price_month" step="0.01" value="<?php echo htmlspecialchars($space['price_month']); ?>">
        </div>

        <button type="submit" class="button">Guardar cambios</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?> 