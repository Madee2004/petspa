<?php
// C:\xampp\htdocs\petspa\editar_perfil.php
require 'auth_check.php';
require 'db.php';
if ($_SESSION['rol'] != 4) { 
    header("Location: index.php"); 
    exit(); 
}
$id_usuario = $_SESSION['usuario_id'];

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$u = $stmt->fetch();

if (!$u) { header("Location: logout.php"); exit(); }

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre'] ?? '');
    $ci = htmlspecialchars($_POST['ci'] ?? '');
    $tel = htmlspecialchars($_POST['telefono'] ?? '');
    $dir = htmlspecialchars($_POST['direccion'] ?? '');
    $foto_perfil = $u['foto_perfil'];

    // Procesar Foto de Perfil
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nuevo_nombre = "perfil_" . $id_usuario . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $nuevo_nombre)) {
                $foto_perfil = $nuevo_nombre;
            }
        }
    }

    try {
        $sql = "UPDATE usuarios SET nombre_completo = ?, ci = ?, telefono = ?, direccion = ?, foto_perfil = ? WHERE id_usuario = ?";
        $pdo->prepare($sql)->execute([$nombre, $ci, $tel, $dir, $foto_perfil, $id_usuario]);

        // Registrar en logs
        $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, ?, ?)");
        $log->execute(["Completó/Actualizó su perfil", "ID: $id_usuario", $_SERVER['REMOTE_ADDR']]);

        header("Location: dashboard.php?msg=perfil_actualizado");
        exit();
    } catch (Exception $e) {
        $mensaje = "<div style='background:#fee2e2; color:#dc2626; padding:10px; border-radius:5px; margin-bottom:15px;'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 400px; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; display: block; margin: 0 auto 15px; border: 3px solid #00b894; }
        input, textarea { width: 100%; padding: 12px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        label { font-weight: bold; color: #2d3436; font-size: 14px; }
        .btn { width: 100%; padding: 14px; background: #00b894; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="text-align:center; color: #00b894; margin-top:0;">📝 Completar Mi Perfil</h2>
        <img src="uploads/<?php echo $u['foto_perfil'] ?: 'default_user.png'; ?>" class="profile-img">
        
        <?php echo $mensaje; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" value="<?php echo htmlspecialchars($u['nombre_completo'] ?? ''); ?>" required>
            
            <label>Carnet de Identidad (CI):</label>
            <input type="text" name="ci" value="<?php echo htmlspecialchars($u['ci'] ?? ''); ?>" placeholder="Requerido para reservas" required>
            
            <label>Teléfono (WhatsApp):</label>
            <input type="tel" name="telefono" value="<?php echo htmlspecialchars($u['telefono'] ?? ''); ?>" required>
            
            <label>Dirección:</label>
            <textarea name="direccion" rows="3" required><?php echo htmlspecialchars($u['direccion'] ?? ''); ?></textarea>
            
            <label>Foto de Perfil (Opcional):</label>
            <input type="file" name="foto" accept="image/*">
            
            <button type="submit" class="btn">Guardar Información</button>
        </form>
        <br><a href="dashboard.php" style="display:block; text-align:center; color:#636e72; text-decoration:none;">← Cancelar y volver</a>
    </div>
</body>
</html>