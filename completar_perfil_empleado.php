<?php
// C:\xampp\htdocs\petspa\completar_perfil_empleado.php
require 'auth_check.php';
require 'db.php';

// Solo para empleados con cambio pendiente
if ($_SESSION['rol'] == 4 || $user_data['cambio_password_pendiente'] == 0) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pass = $_POST['password'];
    $tel  = $_POST['telefono'];
    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        $pdo->beginTransaction();
        // 1. Actualizar Usuario 
        $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, telefono = ?, cambio_password_pendiente = 0 WHERE id_usuario = ?");
        $stmt->execute([$hash, $tel, $_SESSION['usuario_id']]);

        // 2. Si es Groomer, actualizar su especialidad o info extra
        if ($_SESSION['rol'] == 3 && isset($_POST['especialidad'])) {
            $stmtG = $pdo->prepare("UPDATE groomers SET especialidad = ? WHERE usuario_id = ?");
            $stmtG->execute([$_POST['especialidad'], $_SESSION['usuario_id']]);
        }

        $pdo->commit();
        header("Location: dashboard_empleado.php?msg=perfil_listo");
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al actualizar: " . $e->getMessage();
    }
}
?>