<?php
session_start();
require_once 'config/database.php';
require_once 'includes/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $privacy_accepted = isset($_POST['privacy_accepted']) ? 1 : 0;

    $errors = [];

    if (empty($name)) {
        $errors[] = "El nombre es obligatorio";
    }
    if (empty($email)) {
        $errors[] = "El email es obligatorio";
    }
    if (empty($subject)) {
        $errors[] = "El asunto es obligatorio";
    }
    if (empty($message)) {
        $errors[] = "El mensaje es obligatorio";
    }
    if (!$privacy_accepted) {
        $errors[] = "Debes aceptar la política de privacidad";
    }

    if (empty($errors)) {
        $db = Database::getInstance()->getConnection();
        
        try {
            $stmt = $db->prepare("INSERT INTO contact_messages (name, email, subject, message, privacy_accepted) VALUES (?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $subject, $message, $privacy_accepted])) {
                // Enviar email de confirmación al usuario
                if (EmailSender::sendContactConfirmation($email, $name)) {
                    $_SESSION['message'] = "Gracias por tu mensaje. Te responderemos lo antes posible.";
                    header("Location: /contact.php");
                    exit;
                } else {
                    $errors[] = "Error al enviar el email de confirmación";
                }
            } else {
                $errors[] = "Error al guardar el mensaje";
            }
        } catch (PDOException $e) {
            $errors[] = "Error en el servidor. Por favor, inténtalo más tarde.";
        }
    }
}

$pageTitle = 'Contacto - Cotreball';
require_once 'includes/head.php';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="contact-container ">
        <h1>Contacto</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <form method="POST" class="contact-form">
            <div class="form-group">
                <label for="name">Nombre *</label>
                <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="subject">Asunto *</label>
                <input type="text" id="subject" name="subject" required value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="message">Mensaje *</label>
                <textarea id="message" name="message" required rows="5"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            </div>

            <div class="form-group checkbox-group">
                <input type="checkbox" id="privacy_accepted" name="privacy_accepted" required>
                <label for="privacy_accepted">He leído y acepto la <a href="/legal/privacy.php" target="_blank">Política de Privacidad</a> y el tratamiento de mis datos personales *</label>
            </div>
            <div class="form-group checkbox-group">
            <input type="checkbox" id="privacy_accepted" name="privacy_accepted" required="">
            <label for="privacy_accepted">He leído y acepto la <a href="/legal/privacy.php" target="_blank">Política de Privacidad</a> y el tratamiento de mis datos personales</label>
        </div>

            <p class="form-note">* Campos obligatorios</p>

            <button type="submit" class="btn btn-primary">Enviar mensaje</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 