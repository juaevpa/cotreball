<?php
session_start();
require_once '../config/database.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Asegurarnos de que obtenemos el estado actual de approved y el slug
$stmt = $db->prepare("
    SELECT spaces.*, 
           CASE 
               WHEN spaces.approved = 1 THEN 'Aprobado'
               ELSE 'Pendiente de aprobación'
           END as status,
           COALESCE(spaces.slug, CONCAT('espacio-', spaces.id)) as slug
    FROM spaces 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");

$stmt->execute([$_SESSION['user_id']]);
$spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="container">
    <h1>Mis Espacios</h1>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="success-messages">
            <p><?php echo htmlspecialchars($_SESSION['message']); ?></p>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php if (empty($spaces)): ?>
        <p>No tienes espacios creados.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Ciudad</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($spaces as $space): ?>
                    <tr>
                        <td>
                            <h3>
                                <a href="/espacio/<?php echo htmlspecialchars($space['slug']); ?>">
                                    <?php echo htmlspecialchars($space['name']); ?>
                                </a>
                            </h3>
                        </td>
                        <td><?php echo htmlspecialchars($space['city']); ?></td>
                        <td>
                            <?php if (!$space['approved']): ?>
                                <span class="status pending">Pendiente de aprobación</span>
                            <?php else: ?>
                                <span class="status approved">Aprobado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/user/spaces/edit.php?id=<?php echo $space['id']; ?>" class="button">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 