<?php
// C:\xampp\htdocs\petspa\mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Función para enviar tokens a clientes/empleados
function enviarToken($emailDestino, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'caguilarm@fcpn.edu.bo';
        $mail->Password   = 'ojrniczchuelphaq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('caguilarm@fcpn.edu.bo', 'Pet Spa El Despertar');
        $mail->addAddress($emailDestino);

        $mail->isHTML(true);
        $mail->Subject = 'Tu Código de Acceso - Pet Spa';
        $mail->Body    = "<h2>Tu código es: $token</h2><p>Vence en 15 min.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}

// Función para alertar al Admin sobre cambios de Staff
function enviarAlertaAdmin($adminEmail, $nombreGroomer, $nuevaEspecialidad) {
    $mail = new PHPMailer(true); // CORREGIDO: Ahora usa el alias correctamente
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'caguilarm@fcpn.edu.bo';
        $mail->Password   = 'ojrniczchuelphaq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        $mail->setFrom('caguilarm@fcpn.edu.bo', 'Alerta Pet Spa');
        $mail->addAddress($adminEmail);

        $mail->isHTML(true);
        $mail->Subject = 'NOTIFICACIÓN: El Groomer actualizó su perfil';
        $mail->Body    = "
            <div style='background:#fff3cd; padding:20px; border:1px solid #ffeeba;'>
                <h3>Aviso de Cambio de Datos</h3>
                <p>El empleado <b>$nombreGroomer</b> ha modificado su información profesional.</p>
                <p><b>Especialidad declarada:</b> $nuevaEspecialidad</p>
                <hr>
                <p>Revisa los Audit Logs para más detalles sobre esta acción.</p>
            </div>";
        $mail->send();
        return true;
    } catch (Exception $e) { return false; }
}
?>