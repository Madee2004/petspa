<?php
// C:\xampp\htdocs\petspa\editar_perfil_groomer.php

// 1. FORZAR ERRORES (Elimina esto una vez que funcione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'auth_check.php';
require 'db.php';

// Verificamos que sea Groomer (Rol 3) 
if ($_SESSION['rol'] != 3) {
    header("Location: index.php");
    exit();
}

$mensaje = "";

// 2. Obtener datos actuales (incluyendo el Turno)
try {
    $stmt = $pdo->prepare("SELECT u.*, g.especialidad FROM usuarios u JOIN groomers g ON u.id_usuario = g.usuario_id WHERE u.id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $g = $stmt->fetch();
} catch (Exception $e) {
    die("Error al cargar perfil: " . $e->getMessage());
}

// 3. Procesar Formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $tel = htmlspecialchars($_POST['telefono']);
    $esp = htmlspecialchars($_POST['especialidad']);

    try {
        $pdo->beginTransaction();

        // Actualizar datos de usuario y especialidad 
        $upUser = $pdo->prepare("UPDATE usuarios SET nombre_completo = ?, telefono = ? WHERE id_usuario = ?");
        $upUser->execute([$nombre, $tel, $_SESSION['usuario_id']]);

        $upGroomer = $pdo->prepare("UPDATE groomers SET especialidad = ? WHERE usuario_id = ?");
        $upGroomer->execute([$esp, $_SESSION['usuario_id']]);

        // Trazabilidad: Registrar en Logs 
        $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, 'Groomer', ?, ?)");
        $log->execute(["Perfil actualizado por staff", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        $pdo->commit();

        // 4. Intentar notificar al Admin
        if (file_exists('mailer.php')) {
            require_once 'mailer.php';
            $admin_email = 'caguilarm@fcpn.edu.bo';
            // Enviamos la alerta con el turno actual del empleado
            enviarAlertaAdmin($admin_email, $nombre, $esp . " (Turno: " . $g['turno'] . ")");
            $mensaje = "<div style='color:green; padding:10px; border:1px solid green;'>✅ Perfil actualizado y Administrador notificado.</div>";
        } else {
            $mensaje = "<div style='color:orange;'>⚠️ Perfil guardado, pero mailer.php no se encontró.</div>";
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $mensaje = "<div style='color:red;'>❌ Error crítico: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil Staff - Pet Spa</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 400px; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #0984e3; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .info-turno { background: #e1f5fe; padding: 10px; border-radius: 5px; color: #01579b; margin-bottom: 15px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Mi Perfil Staff</h2>
        <div class="info-turno">
            🕒 <b>Turno Asignado:</b> <?php echo htmlspecialchars($g['turno']); ?>
            <br><small>(Solo el administrador puede cambiar tu horario)</small>
        </div>
        
        <?php echo $mensaje; ?>
        
        <form method="POST">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($g['nombre_completo']); ?>" required>
            
            <label>Teléfono:</label>
            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($g['telefono'] ?? ''); ?>" placeholder="Añadir teléfono">
            
            <label>Especialidad técnica:</label>
            <input type="text" name="especialidad" value="<?php echo htmlspecialchars($g['especialidad']); ?>" required>
            
            <button type="submit" class="btn">Guardar y Notificar Cambios</button>
        </form>
        <br>
        <a href="groomer_dashboard.php" style="color: #636e72; text-decoration: none;">← Volver al Panel</a>
    </div>
</body>
</html>