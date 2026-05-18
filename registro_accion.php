<?php
// C:\xampp\htdocs\petspa\registro_accion.php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitización de datos básicos
    $email  = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $nombre = htmlspecialchars($_POST['nombre']);
    $pass   = $_POST['password'];

    // 2. Validación de política de contraseñas (mínimo 8 caracteres)
    if (strlen($pass) < 8) {
        die("La contraseña debe tener al menos 8 caracteres.");
    }

    // 3. Encriptación BCrypt fuerte
    $password_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

    // 4. Generación de Token único y expiración (15 minutos)
    $token = substr(str_shuffle("0123456789ABCDE"), 0, 10);
    $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    try {
        $pdo->beginTransaction();

        // 5. CORRECCIÓN: Insertar solo los campos que tenemos (3 placeholders para 3 variables)
        $sqlUser = "INSERT INTO usuarios (rol_id, nombre_completo, email, password_hash, estado) 
                    VALUES (4, ?, ?, ?, 'Inactivo')";
        $pdo->prepare($sqlUser)->execute([$nombre, $email, $password_hash]);

        // 6. Insertar en 'usuarios_temporales' para validación
        $sqlTemp = "INSERT INTO usuarios_temporales (email, token_validacion, fecha_expiracion) 
                    VALUES (?, ?, ?)";
        $pdo->prepare($sqlTemp)->execute([$email, $token, $expiracion]);

        // 7. Auditoría: Registrar el evento con IP y Navegador
        $sqlLog = "INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) 
                   VALUES (?, 'Cliente', ?, ?)";
        $pdo->prepare($sqlLog)->execute(['Auto-registro iniciado', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        $pdo->commit();

        require 'mailer.php';

        if (enviarToken($email, $token)) {
            header("Location: verificar.html?status=enviado");
        } else {
            echo "Usuario creado, pero el correo no pudo enviarse. Tu token es: $token";
        }
        
        // Simulación de envío de correo para pruebas
        //echo "<h2>¡Registro exitoso!</h2>";
        //echo "Tu código de activación es: <b style='font-size:24px; color:green;'>$token</b><br>";
        //echo "Recuerda que este código expirará en 15 minutos.<br><br>";
        echo "<a href='verificar.html'>Ir a activar mi cuenta</a>";

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == 23000) {
            echo "Error: El correo electrónico ya está registrado.";
        } else {
            echo "Error técnico: " . $e->getMessage();
        }
    }
}
?>