<?php
// C:\xampp\htdocs\petspa\eliminar_mascota.php
require 'auth_check.php';
require 'db.php';

if (isset($_GET['id'])) {
    $id_mascota = $_GET['id'];
    $usuario_id = $_SESSION['usuario_id'];

    // Solo borra si el propietario coincide con la sesión actual
    $stmt = $pdo->prepare("DELETE FROM mascotas WHERE id_mascota = ? AND propietario_id = ?");
    $stmt->execute([$id_mascota, $usuario_id]);

    // Opcional: Registrar en logs
    $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Cliente', ?)");
    $log->execute(["Mascota eliminada ID: $id_mascota", $_SERVER['REMOTE_ADDR']]);
}

header("Location: dashboard.php?msg=eliminado");
exit();