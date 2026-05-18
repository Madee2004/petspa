<?php
// C:\xampp\htdocs\petspa\login_accion.php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass  = $_POST['password'];

    try {
        // 1. Buscar al usuario y verificar si no está bloqueado por intentos fallidos 
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND estado != 'Bloqueado'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // 2. Si el usuario existe, verificar la contraseña con hashing seguro 
        if ($user && password_verify($pass, $user['password_hash'])) {
            
            // 3. Verificar si la cuenta ya fue activada por el token de correo 
            if ($user['esta_verificado'] == 0) {
                die("Debes activar tu cuenta con el código enviado a tu correo antes de entrar. <a href='verificar.html'>Ir a activar</a>");
            }

            // 4. Iniciar Sesión y configurar el tiempo de inactividad de 30 min 
            $_SESSION['usuario_id'] = $user['id_usuario'];
            $_SESSION['nombre']     = $user['nombre_completo'];
            $_SESSION['rol']        = $user['rol_id'];
            $_SESSION['ultima_actividad'] = time();

            // 5. Registrar el acceso en los Audit Logs (Trazabilidad) 
            $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $log->execute(["Login exitoso", "Rol " . $user['rol_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

            // 6. Redirección según el Rol (Arquitectura de Roles) 
            if ($user['rol_id'] == 1) {
                header("Location: admin_dashboard.php");
            } elseif ($user['rol_id'] == 3) {
                header("Location: groomer_dashboard.php");
            } else {
                header("Location: dashboard.php"); // Clientes
            }
            exit();

        } else {
            // 7. Si falla, registrar intento fallido para el bloqueo de 5 intentos 
            $logError = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    
            // Enviamos exactamente 4 parámetros
            $logError->execute([
                "Intento de login fallido: $email", 
                "Visitante", 
                $_SERVER['REMOTE_ADDR'], 
                $_SERVER['HTTP_USER_AGENT']
            ]);
            
            echo "<h3>Error: Correo o contraseña incorrectos.</h3>";
            echo "<a href='portal_staff.php'>Volver a intentar</a>";
        }

    } catch (PDOException $e) {
        echo "Error del sistema: " . $e->getMessage();
    }
}
?>