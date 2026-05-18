<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $email  = $_POST['email'];
    $ci     = $_POST['ci'];
    $pass   = $_POST['password'];
    
    // 1. Encriptación fuerte con BCrypt
    $password_hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

    // 2. Generar Token de activación y expiración (15 min)
    $token = bin2hex(random_bytes(5)); // Ejemplo: a1b2c3d4e5
    $expiracion = date('Y-m-d H:i:s', strtotime('+15 minutes'));

    try {
        $pdo->beginTransaction();

        // 3. Crear usuario con estado 'Inactivo'
        $stmt = $pdo->prepare("INSERT INTO usuarios (id_usuario, rol_id, nombre_completo, email, ci, password_hash, estado) 
                               VALUES (UUID(), 4, ?, ?, ?, ?, 'Inactivo')");
        $stmt->execute([$nombre, $email, $ci, $password_hash]);

        // 4. Guardar token temporal
        $stmt_temp = $pdo->prepare("INSERT INTO usuarios_temporales (id_temp, email, token_validacion, fecha_expiracion) 
                                    VALUES (UUID(), ?, ?, ?)");
        $stmt_temp->execute([$email, $token, $expiracion]);

        // 5. Registro en Auditoría (Logs)
        $stmt_log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) 
                                   VALUES (?, 'Cliente', ?, ?)");
        $stmt_log->execute(['Intento de auto-registro', $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        $pdo->commit();
        echo "Registro exitoso. Revisa tu correo, el token expira en 15 minutos.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error en el registro: " . $e->getMessage();
    }
}
?>