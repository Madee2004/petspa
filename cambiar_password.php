<?php
// C:\xampp\htdocs\petspa\cambiar_password.php
require 'auth_check.php';
require 'db.php';

$mensaje = "";
$id_usuario = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = $_POST['pass_actual'];
    $nueva = $_POST['pass_nueva'];
    $confirmar = $_POST['pass_confirmar'];

    // Obtener la contraseña actual de la base de datos
    $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $user = $stmt->fetch();

    // Verificamos si la contraseña coincide (soporta contraseñas en texto plano antiguas o encriptadas con BCrypt)
    if (password_verify($actual, $user['contrasena']) || $actual === $user['contrasena']) {
        if ($nueva === $confirmar) {
            // Encriptar la nueva contraseña
            $hash = password_hash($nueva, PASSWORD_BCRYPT);
            
            // Actualizar en la base de datos
            $update = $pdo->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?");
            $update->execute([$hash, $id_usuario]);
            
            // Guardar en auditoría
            $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, ?, ?)");
            $log->execute(["Cambio de contraseña de seguridad", "ID: $id_usuario", $_SERVER['REMOTE_ADDR']]);

            $mensaje = "<div style='background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:15px;'>✅ Contraseña actualizada correctamente.</div>";
        } else {
            $mensaje = "<div style='background:#fee2e2; color:#dc2626; padding:10px; border-radius:5px; margin-bottom:15px;'>❌ Las contraseñas nuevas no coinciden.</div>";
        }
    } else {
        $mensaje = "<div style='background:#fee2e2; color:#dc2626; padding:10px; border-radius:5px; margin-bottom:15px;'>❌ La contraseña actual es incorrecta.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Seguridad - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px; }
        input { width: 100%; padding: 12px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        label { font-weight: bold; color: #2d3436; font-size: 14px; }
        .btn { width: 100%; padding: 14px; background: #e17055; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="text-align:center; color: #e17055; margin-top:0;">🔐 Cambiar Contraseña</h2>
        <p style="text-align:center; font-size: 13px; color: #636e72;">Por seguridad, te pedimos tu contraseña actual.</p>
        
        <?php echo $mensaje; ?>

        <form method="POST">
            <label>Contraseña Actual:</label>
            <input type="password" name="pass_actual" required>
            
            <label>Nueva Contraseña:</label>
            <input type="password" name="pass_nueva" required minlength="6">
            
            <label>Confirmar Nueva Contraseña:</label>
            <input type="password" name="pass_confirmar" required minlength="6">
            
            <button type="submit" class="btn">Actualizar Contraseña</button>
        </form>
        <br><a href="dashboard.php" style="display:block; text-align:center; color:#636e72; text-decoration:none;">← Volver al Panel</a>
    </div>
</body>
</html>