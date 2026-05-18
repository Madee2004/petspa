<?php
// C:\xampp\htdocs\petspa\admin_seguridad.php
require 'auth_check.php';
require 'db.php';

// Seguridad: Solo Admin
if ($_SESSION['rol'] != 1) { header("Location: index.php"); exit(); }

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['pass_actual'];
    $nueva = $_POST['pass_nueva'];

    $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $admin = $stmt->fetch();

    if (password_verify($actual, $admin['password_hash'])) {
        if (strlen($nueva) >= 8) {
            $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?")->execute([$hash, $_SESSION['usuario_id']]);
            
            // Trazabilidad 
            $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Admin', ?)");
            $log->execute(["Cambio de contraseña administrativa exitoso", $_SERVER['REMOTE_ADDR']]);
            
            $mensaje = "<div style='color:green;'>✅ Contraseña actualizada correctamente.</div>";
        } else { $mensaje = "<div style='color:red;'>❌ La nueva clave debe tener al menos 8 caracteres.</div>"; }
    } else { $mensaje = "<div style='color:red;'>❌ La contraseña actual es incorrecta.</div>"; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguridad Admin - Pet Spa</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding-top: 50px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn { background: #6c5ce7; color: white; border: none; padding: 12px; width: 100%; border-radius: 6px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🔐 Seguridad del Administrador</h2>
        <?php echo $mensaje; ?>
        <form method="POST">
            <label>Contraseña Actual:</label>
            <input type="password" name="pass_actual" required>
            <label>Nueva Contraseña:</label>
            <input type="password" name="pass_nueva" required>
            <button type="submit" class="btn">Actualizar Credenciales</button>
        </form>
        <br><a href="admin_dashboard.php" style="color: #636e72; text-decoration: none;">← Volver al Panel</a>
    </div>
</body>
</html>