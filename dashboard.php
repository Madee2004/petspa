<?php
// C:\xampp\htdocs\petspa\dashboard.php
require 'auth_check.php';
require 'db.php';

// 1. Obtener datos del cliente
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$u = $stmt->fetch();

// 2. Obtener mascotas
$stmtM = $pdo->prepare("SELECT * FROM mascotas WHERE propietario_id = ?");
$stmtM->execute([$_SESSION['usuario_id']]);
$lista_mascotas = $stmtM->fetchAll();

$perfil_completo = (!empty($u['ci']) && !empty($u['telefono']) && !empty($u['direccion']));
$tiene_mascotas = (count($lista_mascotas) > 0);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Control - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f0f2f5; }
        .sidebar { width: 280px; background: #2d3436; color: white; height: 100vh; padding: 30px; position: fixed; display: flex; flex-direction: column; }
        .main-content { margin-left: 340px; padding: 40px; width: 100%; }
        
        .profile-section { text-align: center; margin-bottom: 30px; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #00b894; margin-bottom: 10px; }
        
        .nav-links a { color: #bdc3c7; text-decoration: none; display: block; padding: 12px 0; border-bottom: 1px solid #3d4a4d; transition: 0.3s; }
        .nav-links a:hover { color: white; padding-left: 10px; }
        .btn-logout { color: #ff7675 !important; margin-top: auto; font-weight: bold; }

        .alerta { background: #ffeaa7; padding: 15px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #fab1a0; color: #d35400; }
        .mascota-card { display: flex; align-items: center; gap: 20px; background: white; padding: 20px; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .btn-accion { padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-edit { background: #e1f5fe; color: #01579b; border: 1px solid #b3e5fc; }
        .btn-delete { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-active { background: #00b894; color: white; padding: 12px 20px; border-radius: 8px; display: inline-block; text-decoration: none; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="profile-section">
            <img src="uploads/<?php echo $u['foto_perfil']; ?>" class="profile-img">
            <h3><?php echo htmlspecialchars($u['nombre_completo']); ?></h3>
            <p style="font-size: 12px; color: #00b894;">Cliente Verificado</p>
        </div>

        <nav class="nav-links">
            <a href="dashboard.php">🏠 Inicio</a>
            <a href="editar_perfil.php">📝 Editar Mi Perfil</a>
            <a href="cambiar_password.php">🔐 Seguridad</a>
            <a href="logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
        </nav>
    </div>
    

    <div class="main-content">
        <h1>Bienvenido, <?php echo explode(' ', $u['nombre_completo'])[0]; ?></h1>

        <?php if (!$perfil_completo): ?>
            <div class="alerta">
                ⚠️ <b>Perfil Incompleto:</b> Para agendar citas necesitamos tu CI y Dirección.
            </div>
        <?php endif; ?>

        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>🐾 Mis Mascotas</h3>
                <a href="agregar_mascota.php" class="btn-active">+ Registrar Mascota</a>
            </div>
            
            <?php if ($tiene_mascotas): ?>
                <?php foreach ($lista_mascotas as $m): ?>
                    <div class="mascota-card">
                        <img src="uploads/<?php echo $m['foto_url']; ?>" style="width:70px; height:70px; border-radius:50%; object-fit:cover;">
                        <div style="flex-grow: 1;">
                            <strong style="font-size: 18px;"><?php echo htmlspecialchars($m['nombre']); ?></strong><br>
                            <small><?php echo $m['especie']; ?> • <?php echo $m['peso_actual']; ?> kg</small>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <a href="editar_perfil_mascota.php?id=<?php echo $m['id_mascota']; ?>" class="btn-accion btn-edit">⚙️ Editar</a>
                            <a href="eliminar_mascota.php?id=<?php echo $m['id_mascota']; ?>" 
                               class="btn-accion btn-delete" 
                               onclick="return confirm('¿Estás seguro de eliminar a esta mascota?')">🗑️ Eliminar</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #636e72; font-style: italic;">No tienes mascotas registradas.</p>
            <?php endif; ?>
        </section>

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">

        <section>
            <h3>📅 Próximas Citas</h3>
            <?php if ($perfil_completo && $tiene_mascotas): ?>
                <a href="agendar_cita.php" class="btn-active">Agendar Nueva Cita</a>
            <?php else: ?>
                <p style="color: #e74c3c;">Completa tu perfil y registra una mascota para habilitar las reservas.</p>
            <?php endif; ?>
        </section>
        <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">
        
        <section>
            <h3>🧾 Historial de Mis Citas</h3>
            <?php
            // Consultar las citas del cliente
            $sqlMisCitas = "SELECT c.*, m.nombre as mascota, u.nombre_completo as groomer 
                            FROM citas c 
                            JOIN mascotas m ON c.mascota_id = m.id_mascota 
                            JOIN groomers g ON c.groomer_id = g.id_groomer
                            JOIN usuarios u ON g.usuario_id = u.id_usuario
                            WHERE m.propietario_id = ? ORDER BY c.fecha_hora_inicio DESC";
            $mis_citas = $pdo->prepare($sqlMisCitas);
            $mis_citas->execute([$_SESSION['usuario_id']]);
            $citas_cliente = $mis_citas->fetchAll();
            
            // Lógica rápida para cancelar cita si el cliente hace clic
            if (isset($_GET['cancelar_cita'])) {
                $pdo->prepare("UPDATE citas SET estado = 'Cancelada' WHERE id_cita = ?")->execute([$_GET['cancelar_cita']]);
                echo "<script>window.location.href='dashboard.php';</script>";
            }
            ?>

            <?php if (count($citas_cliente) > 0): ?>
                <table style="width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <tr style="background: #2d3436; color: white;">
                        <th style="padding: 12px; text-align: left;">Fecha / Hora</th>
                        <th style="padding: 12px; text-align: left;">Mascota</th>
                        <th style="padding: 12px; text-align: left;">Estilista</th>
                        <th style="padding: 12px; text-align: left;">Estado</th>
                        <th style="padding: 12px; text-align: center;">Acción</th>
                    </tr>
                    <?php foreach ($citas_cliente as $cita): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><b><?php echo date('d/m/Y', strtotime($cita['fecha_hora_inicio'])); ?></b><br><small><?php echo date('H:i A', strtotime($cita['fecha_hora_inicio'])); ?></small></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($cita['mascota']); ?><br><small><?php echo $cita['servicio']; ?></small></td>
                        <td style="padding: 12px;"><?php echo htmlspecialchars($cita['groomer']); ?></td>
                        <td style="padding: 12px;"><b><?php echo $cita['estado']; ?></b></td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($cita['estado'] === 'Pendiente' || $cita['estado'] === 'Confirmada'): ?>
                                <a href="dashboard.php?cancelar_cita=<?php echo $cita['id_cita']; ?>" style="background: #e74c3c; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px;" onclick="return confirm('¿Seguro que deseas cancelar esta cita?');">Cancelar</a>
                            <?php else: ?>
                                <span style="color: #bdc3c7; font-size: 12px;">No disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="color: #7f8c8d;">Aún no has agendado ninguna cita.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>