<?php
// C:\xampp\htdocs\petspa\admin_dashboard.php
require 'auth_check.php';
require 'db.php';

// SEGURIDAD: Solo el Administrador (Rol 1) puede entrar aquí
if ($_SESSION['rol'] != 1) {
    header("Location: dashboard.php?error=acceso_denegado");
    exit();
}

// 1. Obtener estadísticas rápidas
$total_clientes = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol_id = 4")->fetchColumn();
$total_groomers = $pdo->query("SELECT COUNT(*) FROM groomers")->fetchColumn();
$citas_hoy = $pdo->query("SELECT COUNT(*) FROM citas WHERE DATE(fecha_hora_inicio) = CURDATE()")->fetchColumn();

// 2. Obtener los últimos 5 Logs de Auditoría (Requerimiento de la docente)
$stmt_logs = $pdo->query("SELECT * FROM audit_logs ORDER BY fecha_hora DESC LIMIT 5");
$logs = $stmt_logs->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; color: white; min-height: 100vh; padding: 20px; }
        .main-content { flex-grow: 1; padding: 30px; }
        .stats-container { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); flex: 1; text-align: center; }
        .stat-card h3 { margin: 0; color: #7f8c8d; }
        .stat-card p { font-size: 24px; font-weight: bold; margin: 10px 0; color: #2c3e50; }
        table { width: 100%; background: white; border-collapse: collapse; border-radius: 10px; overflow: hidden; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
        .btn-crear { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Pet Spa Admin</h2>
    <hr>
    <p><b>Usuario:</b> <?php echo $_SESSION['rol'] == 1 ? 'Administrador' : 'Staff'; ?></p>
    <nav>
        <p><a href="admin_dashboard.php" style="color:white; text-decoration: none;">🏠 Inicio</a></p>
        <p><a href="recepcion_dashboard.php" style="color:#f1c40f; font-weight: bold; text-decoration: none;">🗓️ Calendario Maestro (Recepción)</a></p>
        <p><a href="gestion_personal.php" style="color:white; text-decoration: none;">👥 Gestión de Personal</a></p>
        <p><a href="gestion_clientes.php" style="color:white; text-decoration: none;">🐾 Ver Clientes</a></p>
        <br>
        <p><a href="admin_seguridad.php" class="btn">🔐 Cambiar mi Contraseña</a></p>
        <p><a href="logout.php" style="color: #e74c3c; font-weight: bold; text-decoration: none;">🚪 Cerrar Sesión</a></p>
    </nav>
</div>

<div class="main-content">
    <h1>Panel de Administración Central</h1>
    
    <div class="stats-container">
        <div class="stat-card">
            <h3>Clientes</h3>
            <p><?php echo $total_clientes; ?></p>
        </div>
        <div class="stat-card">
            <h3>Groomers</h3>
            <p><?php echo $total_groomers; ?></p>
        </div>
        <div class="stat-card">
            <h3>Citas Hoy</h3>
            <p><?php echo $citas_hoy; ?></p>
        </div>
    </div>

    <a href="crear_personal.php" class="btn-crear">+ Crear Nuevo Personal (Groomer/Recepción)</a>

    <h2>Logs de Auditoría Recientes (Trazabilidad)</h2>
    <table>
        <thead>
            <tr>
                <th>Fecha/Hora</th>
                <th>Acción</th>
                <th>IP</th>
                <th>Rol</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($logs as $log): ?>
            <tr>
                <td><?php echo $log['fecha_hora']; ?></td>
                <td><?php echo htmlspecialchars($log['accion']); ?></td>
                <td><?php echo $log['ip_address']; ?></td>
                <td><?php echo $log['rol_ejecutor']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>