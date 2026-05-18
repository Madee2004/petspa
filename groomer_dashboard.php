<?php
// C:\xampp\htdocs\petspa\groomer_dashboard.php
require 'auth_check.php';
require 'db.php';

if ($_SESSION['rol'] != 3) { header("Location: index.php"); exit(); }

$stmt = $pdo->prepare("SELECT u.*, g.especialidad FROM usuarios u JOIN groomers g ON u.id_usuario = g.usuario_id WHERE u.id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$g = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Groomer Panel - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f8f9fa; }
        .sidebar { width: 260px; background: #2c3e50; color: white; height: 100vh; padding: 25px; position: fixed; }
        .main { margin-left: 310px; padding: 40px; width: 100%; }
        .btn-edit { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; margin: 15px 0; }
        .sidebar a:hover { color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>✂️ Staff Grooming</h2>
        <p><b><?php echo $g['nombre_completo']; ?></b></p>
        <p style="background: #34495e; padding: 5px; border-radius: 3px;">
        🕒 Turno: <?php echo htmlspecialchars($g['turno']); ?>
        </p>
        <hr>
        <a href="editar_perfil_groomer.php">📝 Editar Perfil</a>
        <a href="perfil_seguridad.php">🔐 Seguridad</a>
        <a href="logout.php" style="color:#e74c3c;">🚪 Cerrar Sesión</a>
    </div>
    <div class="main">
        <h1>Mi Perfil Profesional</h1>
        <div style="background:white; padding:30px; border-radius:10px;">
            <p><b>Especialidad:</b> <?php echo $g['especialidad']; ?></p>
            <p><b>Correo:</b> <?php echo $g['email']; ?></p>
            <p><b>Teléfono:</b> <?php echo $g['telefono'] ?? 'No registrado'; ?></p>
            <br>
            <a href="editar_perfil_groomer.php" class="btn-edit">Actualizar mis datos</a>
        </div>
    </div>
</body>
</html>