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
            <a href="catalogo.php">🛍️ Comprar Productos</a>
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

        <hr style="margin: 40px 0; border: 0; border-top: 1px solid #ddd;">
        
        <section>
            <h3>🛍️ Mis Pedidos de la Tienda</h3>
            <?php
            // LÓGICA PARA CANCELAR PEDIDO Y ENVIAR TELEGRAM
            if (isset($_GET['cancelar_pedido'])) {
                $id_venta_cancelar = $_GET['cancelar_pedido'];
                
                // 1. Obtener detalles del pedido para el mensaje
                $stmtDetalle = $pdo->prepare("SELECT v.codigo_recojo, u.nombre_completo 
                                              FROM ventas v 
                                              JOIN usuarios u ON v.cliente_id = u.id_usuario 
                                              WHERE v.id_venta = ?");
                $stmtDetalle->execute([$id_venta_cancelar]);
                $info_pedido = $stmtDetalle->fetch();

                if ($info_pedido) {
                    // 2. Devolver el stock de los productos cancelados a la tienda
                    $stmtItems = $pdo->prepare("SELECT producto_id, cantidad FROM detalle_ventas_productos WHERE venta_id = ?");
                    $stmtItems->execute([$id_venta_cancelar]);
                    $items = $stmtItems->fetchAll();
                    
                    foreach ($items as $item) {
                        $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?")
                            ->execute([$item['cantidad'], $item['producto_id']]);
                    }

                    // 3. Actualizar estado a Cancelado
                    $pdo->prepare("UPDATE ventas SET estado_pedido = 'Cancelado' WHERE id_venta = ?")->execute([$id_venta_cancelar]);
                    
                    // 4. Integración Telegram (REEMPLAZA CON TUS DATOS)
                    $env = parse_ini_file(__DIR__ . '/.env');
                    $telegram_token = $env['TELEGRAM_TOKEN']; 
                    $chat_id = $env['TELEGRAM_CHAT_ID'];//Busca @userinfobot en telegram, dale /start, y reemplaza este id con el tuyo para que las confirmaciones te lleguen a ti
                    
                    $mensaje_telegram = "🚫 *PEDIDO CANCELADO*\n\n";
                    $mensaje_telegram .= "👤 Cliente: " . $info_pedido['nombre_completo'] . "\n";
                    $mensaje_telegram .= "📦 Código Recojo: *" . $info_pedido['codigo_recojo'] . "*\n";
                    $mensaje_telegram .= "🔄 _El stock de los productos ha sido devuelto a la tienda automáticamente._";

                    $url_telegram = "https://api.telegram.org/bot{$telegram_token}/sendMessage";
                    $data = ['chat_id' => $chat_id, 'text' => $mensaje_telegram, 'parse_mode' => 'Markdown'];
                    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data)]];
                    @file_get_contents($url_telegram, false, stream_context_create($options));
                }

                echo "<script>window.location.href='dashboard.php';</script>";
                exit();
            }

            // Consultar pedidos del cliente logueado
            $mis_pedidos = $pdo->prepare("SELECT * FROM ventas WHERE cliente_id = ? AND codigo_recojo IS NOT NULL ORDER BY fecha_venta DESC");
            $mis_pedidos->execute([$_SESSION['usuario_id']]);
            $pedidos = $mis_pedidos->fetchAll();
            ?>

            <?php if (count($pedidos) > 0): ?>
                <table style="width: 100%; background: white; border-collapse: collapse; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                    <tr style="background: #2d3436; color: white;">
                        <th style="padding: 12px; text-align: left;">Fecha</th>
                        <th style="padding: 12px; text-align: left;">Código Recojo</th>
                        <th style="padding: 12px; text-align: left;">Total y Método</th>
                        <th style="padding: 12px; text-align: left;">Estado</th>
                        <th style="padding: 12px; text-align: center;">Acciones</th>
                    </tr>
                    <?php foreach ($pedidos as $ped): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 12px;"><?php echo date('d/m/Y H:i', strtotime($ped['fecha_venta'])); ?></td>
                        <td style="padding: 12px;"><b style="color: #0984e3; font-size: 16px;"><?php echo $ped['codigo_recojo']; ?></b></td>
                        
                        <td style="padding: 12px;">
                            <b>Bs. <?php echo $ped['total']; ?></b><br>
                            <small style="color: #7f8c8d;"><?php echo $ped['metodo_pago']; ?></small><br>
                            
                            <!-- BOTÓN PARA VER EL QR SI AÚN NO HA PAGADO Y ELIGIÓ QR -->
                            <?php if ($ped['metodo_pago'] === 'QR' && $ped['estado_pedido'] === 'Pendiente'): ?>
                                <a href="uploads/qr_pago.png" target="_blank" style="background: #e8f8f5; color: #16a085; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-decoration: none; display: inline-block; margin-top: 5px; border: 1px solid #1abc9c;">
                                    📷 Ver QR de Pago
                                </a>
                            <?php endif; ?>
                        </td>

                        <td style="padding: 12px;">
                            <?php 
                                $color = ($ped['estado_pedido'] == 'Cancelado') ? '#e74c3c' : (($ped['estado_pedido'] == 'Entregado') ? '#27ae60' : '#f39c12');
                            ?>
                            <b style="color: <?php echo $color; ?>;"><?php echo $ped['estado_pedido']; ?></b>
                        </td>
                        <td style="padding: 12px; text-align: center;">
                            <?php if ($ped['estado_pedido'] === 'Pendiente'): ?>
                                <a href="dashboard.php?cancelar_pedido=<?php echo $ped['id_venta']; ?>" style="background: #e74c3c; color: white; padding: 6px 10px; border-radius: 4px; text-decoration: none; font-size: 12px;" onclick="return confirm('¿Seguro que deseas cancelar este pedido? Perderás tu reserva de los productos.');">Cancelar Pedido</a>
                            <?php else: ?>
                                <span style="color: #bdc3c7; font-size: 12px;">No disponible</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="color: #7f8c8d;">Aún no has realizado compras en la tienda.</p>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>