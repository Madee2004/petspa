<?php
// C:\xampp\htdocs\petspa\logout.php
require 'db.php';
session_start();

if (isset($_SESSION['usuario_id'])) {
    $rol = $_SESSION['rol'];
    
    // Registrar salida en Auditoría 
    $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $log->execute(["Cierre de sesión", "Rol $rol", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

    session_unset();
    session_destroy();

    // Redirección selectiva
    if ($rol == 4) {
        header("Location: portal_clientes.php?status=logout");
    } else {
        header("Location: portal_staff.php?status=logout");
    }
} else {
    header("Location: index.php");
}
exit();