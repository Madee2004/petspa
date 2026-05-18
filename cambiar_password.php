<?php
// C:\xampp\htdocs\petspa\cambiar_password.php
require 'auth_check.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['pass_actual'];
    $nueva  = $_POST['pass_nueva'];

    $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();

    if (password_verify($actual, $user['password_hash'])) {
        if (strlen($nueva) >= 8) {
            $nuevo_hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?")->execute([$nuevo_hash, $_SESSION['usuario_id']]);
            
            $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $log->execute(["Cambio de clave exitoso", "ID: ".$_SESSION['usuario_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            
            echo "<script>alert('Contraseña actualizada. Inicia sesión de nuevo.'); window.location.href='logout.php';</script>";
        } else { $error = "La nueva clave debe tener al menos 8 caracteres."; }
    } else { $error = "La contraseña actual es incorrecta."; }
}
?>