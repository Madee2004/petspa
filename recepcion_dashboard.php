<?php
// C:\xampp\htdocs\petspa\recepcion_dashboard.php
require 'auth_check.php';
require 'db.php';

// Seguridad: Solo Personal de Recepción (Rol 2) o Administrador (Rol 1)
if ($_SESSION['rol'] != 2 && $_SESSION['rol'] != 1) { 
    header("Location: index.php"); 
    exit(); 
}
$mensaje = "";

// ACCIÓN 1: Confirmar Cita
if (isset($_GET['action']) && isset($_GET['id_cita'])) {
    $stmt = $pdo->prepare("UPDATE citas SET estado = 'Confirmada' WHERE id_cita = ?");
    $stmt->execute([$_GET['id_cita']]);
    
    $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Recepcion', ?)");
    $log->execute(["Cita ID " . $_GET['id_cita'] . " confirmada.", $_SERVER['REMOTE_ADDR']]);
    
    header("Location: recepcion_dashboard.php?msg=confirmado");
    exit();
}

// ACCIÓN 2: Procesar Pagos (REQUERIMIENTO RÚBRICA)
if (isset($_POST['pay']) && isset($_POST['id_cita'])) {
    $metodo = $_POST['metodo_pago'];
    $id_cita = $_POST['id_cita'];
    
    try {
        $stmt = $pdo->prepare("UPDATE citas SET pago_estado = 'Pagado', metodo_pago = ? WHERE id_cita = ?");
        $stmt->execute([$metodo, $id_cita]);
        
        $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Recepcion', ?)");
        $log->execute(["Pago de Cita ID $id_cita registrado vía $metodo", $_SERVER['REMOTE_ADDR']]);
        
        $mensaje = "<div class='alert success'>✅ Pago registrado correctamente vía $metodo.</div>";
    } catch (Exception $e) {
        $mensaje = "<div class='alert error'>❌ Error al cobrar: " . $e->getMessage() . "</div>";
    }
}

// OBTENER INVENTARIO CRÍTICO (REQUERIMIENTO RÚBRICA)
$alertas_stock = $pdo->query("SELECT * FROM inventario WHERE cantidad <= stock_minimo")->fetchAll();

// OBTENER CITAS (Calendario Maestro)
$sqlMaster = "SELECT c.*, m.nombre as mascota, m.especie, u_cli.nombre_completo as cliente, u_gro.nombre_completo as groomer
              FROM citas c 
              JOIN mascotas m ON c.mascota_id = m.id_mascota 
              JOIN usuarios u_cli ON m.propietario_id = u_cli.id_usuario
              JOIN groomers g ON c.groomer_id = g.id_groomer 
              JOIN usuarios u_gro ON g.usuario_id = u_gro.id_usuario 
              ORDER BY c.fecha_hora_inicio DESC";
$citas_maestras = $pdo->query($sqlMaster)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Recepción - Maestro Citas</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f8f9fa; display: flex; }
        .sidebar { width: 260px; background: #2c3e50; color: white; height: 100vh; padding: 25px; position: fixed; }
        .main { margin-left: 310px; padding: 40px; width: calc(100% - 310px); }
        .table-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #34495e; color: white; }
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #ffeaa7; color: #d35400; }
        .badge-success { background: #d4edda; color: #155724; }
        .btn-confirm { background: #2ecc71; color: white; text-decoration: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .btn-pay { background: #3498db; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-stock { background: #fff3cd; color: #856404; padding: 15px; border-left: 5px solid #ffeeba; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>🛎️ Recepción</h2>
        <p>Sesión activa: <br><b><?php echo htmlspecialchars($_SESSION['email'] ?? 'Usuario Recepción'); ?></b></p>
        <!--<p>Sesión activa: <br><b><?php echo htmlspecialchars($_SESSION['email']); ?></b></p>-->
        <hr style="border: 0; border-top: 1px solid #4f5d73; margin: 20px 0;">
        <nav>
            <p><a href="recepcion_dashboard.php" style="color: white; text-decoration: none;">🗓️ Calendario Maestro</a></p>
            <p><a href="admin_dashboard.php" style="color: white; text-decoration: none;">📝 Mi Perfil</a></p>
            <p><a href="logout.php" style="color: #ff7675; text-decoration: none; font-weight: bold;">🚪 Cerrar Sesión</a></p>
        </nav>
    </div>

    <div class="main">
        <h1>Control y Calendario Maestro de Servicios</h1>
        <p>Validación de solicitudes entrantes, asignación de agendas y pasarela de caja.</p>

        <?php echo $mensaje; ?>

        <?php if (count($alertas_stock) > 0): ?>
            <div class="alert-stock">
                <h3 style="margin-top:0;">⚠️ Alertas de Inventario Crítico</h3>
                <ul style="margin-bottom: 0;">
                    <?php foreach ($alertas_stock as $item): ?>
                        <li style="margin-bottom: 5px;">
                            <b><?php echo htmlspecialchars($item['nombre']); ?></b> - Quedan: <?php echo $item['cantidad']; ?> unidades.
                        <!-- Aquí se pone el nro del proveedor para pedir más de algo-->
                            <a href="https://wa.me/59100000000?text=<?php echo urlencode('Hola, solicito reabastecimiento de: ' . $item['nombre']); ?>" target="_blank" style="margin-left: 10px; color: #25D366; font-weight: bold; text-decoration: none;">📱 Pedir a Proveedor (WhatsApp)</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="table-card">
            <h3>Historial General de Reservas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Paciente/Cliente</th>
                        <th>Groomer</th>
                        <th>Estado Cita</th>
                        <th>Caja (Monto)</th>
                        <th>Acciones Administrativas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($citas_maestras) > 0): ?>
                        <?php foreach ($citas_maestras as $c): ?>
                        <tr>
                            <td><b><?php echo date('d/m/Y', strtotime($c['fecha_hora_inicio'])); ?></b><br><small><?php echo date('H:i A', strtotime($c['fecha_hora_inicio'])); ?></small></td>
                            <td><b><?php echo htmlspecialchars($c['mascota']); ?></b> (<?php echo $c['especie']; ?>)<br><small>Dueño: <?php echo htmlspecialchars($c['cliente']); ?></small></td>
                            <td><small>Asignado a:</small><br><b><?php echo htmlspecialchars($c['groomer']); ?></b></td>
                            <td>
                                <span class="badge <?php echo ($c['estado'] === 'Confirmada' || $c['estado'] === 'Completada') ? 'badge-success' : 'badge-pending'; ?>">
                                    <?php echo $c['estado']; ?>
                                </span>
                            </td>
                            <td>
                                <b><?php echo $c['monto']; ?> Bs.</b><br>
                                <?php if ($c['pago_estado'] === 'Pagado'): ?>
                                    <small style="color: #0d47a1; font-weight: bold;">✅ Pagado (<?php echo $c['metodo_pago']; ?>)</small>
                                <?php else: ?>
                                    <small style="color: #d63031; font-weight: bold;">❌ No Pagado</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['estado'] === 'Pendiente'): ?>
                                    <a href="recepcion_dashboard.php?action=confirmar&id_cita=<?php echo $c['id_cita']; ?>" class="btn-confirm">Confirmar Cita</a>
                                <?php endif; ?>

                                <?php if ($c['pago_estado'] === 'No Pagado' && ($c['estado'] === 'Confirmada' || $c['estado'] === 'Completada')): ?>
                                    <form method="POST" style="display:flex; gap:5px; margin-top: 5px;">
                                        <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                        <select name="metodo_pago" required style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;">
                                            <option value="">- Método -</option>
                                            <option value="QR">Código QR</option>
                                            <option value="Efectivo">Efectivo</option>
                                            <option value="Transferencia">Transferencia</option>
                                        </select>
                                        <button type="submit" name="pay" class="btn-pay">Cobrar</button>
                                        <?php if ($c['pago_estado'] === 'Pagado'): ?>
                                            <a href="recibo.php?id_cita=<?php echo $c['id_cita']; ?>" target="_blank" style="background: #34495e; color: white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; margin-top: 5px; display: inline-block;">📄 Ver Recibo</a>
                                        <?php endif; ?>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; color: #636e72;">No hay citas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>