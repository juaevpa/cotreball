<?php
session_start();
require_once '../config/database.php';

// Configuración de la página
$pageTitle = 'Panel de Administración - Cotreball';

// Verificar que es administrador
if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit;
}

// Cargar espacios pendientes de aprobación
$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT s.*, u.username as owner_name 
    FROM spaces s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.approved = 0 
    ORDER BY s.created_at DESC
");
$pending_spaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/head.php';
require_once '../includes/header.php';
?>

<div class="container">
    <h2>Panel de Administración</h2>
    
    <h3>Espacios Pendientes de Aprobación</h3>
    <?php if (empty($pending_spaces)): ?>
        <p>No hay espacios pendientes de aprobación.</p>
    <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Ciudad</th>
                    <th>Propietario</th>
                    <th>Fecha</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_spaces as $space): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($space['name']); ?></td>
                        <td><?php echo htmlspecialchars($space['city']); ?></td>
                        <td><?php echo htmlspecialchars($space['owner_name']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($space['created_at'])); ?></td>
                        <td>
                            <a href="/space.php?id=<?php echo $space['id']; ?>" class="button">Ver</a>
                            <form method="POST" action="/admin/approve_space.php" style="display: inline;">
                                <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
                                <button type="submit" class="button approve">Aprobar</button>
                            </form>
                            <form method="POST" action="/admin/reject_space.php" style="display: inline;">
                                <input type="hidden" name="space_id" value="<?php echo $space['id']; ?>">
                                <button type="submit" class="button reject">Rechazar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?> 