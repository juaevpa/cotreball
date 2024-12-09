<?php

class EmailSender {
    private static function sendEmail($to, $subject, $message) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Cotreball <noreply@cotreball.test>',
            'Reply-To: noreply@cotreball.test',
            'X-Mailer: PHP/' . phpversion()
        ];

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    public static function sendVerificationEmail($email, $token) {
        $subject = 'Verifica tu cuenta en Cotreball';
        $verificationLink = 'http://cotreball.test/auth/verify.php?token=' . $token;
        
        $message = "
            <h1>Bienvenido a Cotreball</h1>
            <p>Por favor, haz clic en el siguiente enlace para verificar tu cuenta:</p>
            <p><a href='{$verificationLink}'>{$verificationLink}</a></p>
            <p>Este enlace expirará en 24 horas.</p>
        ";
        
        return self::sendEmail($email, $subject, $message);
    }

    public static function sendPasswordResetEmail($email, $token) {
        $subject = 'Recuperar contraseña - Cotreball';
        $resetLink = 'http://cotreball.test/auth/reset-password.php?token=' . $token;
        
        $message = "
            <h1>Recuperar contraseña</h1>
            <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace:</p>
            <p><a href='{$resetLink}'>{$resetLink}</a></p>
            <p>Este enlace expirará en 1 hora.</p>
            <p>Si no has solicitado este cambio, puedes ignorar este email.</p>
        ";
        
        return self::sendEmail($email, $subject, $message);
    }
} 