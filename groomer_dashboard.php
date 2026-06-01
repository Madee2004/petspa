<?php
// C:\xampp\htdocs\petspa\groomer_dashboard.php
require 'auth_check.php';
require 'db.php';

if ($_SESSION['rol'] != 3) { header("Location: index.php"); exit(); }

$mensaje = "";
$stmtG = $pdo->prepare("SELECT u.*, g.id_groomer, g.especialidad FROM usuarios u JOIN groomers g ON u.id_usuario = g.usuario_id WHERE u.id_usuario = ?");
$stmtG->execute([$_SESSION['usuario_id']]);
$groomer = $stmtG->fetch();

// EXTRAEMOS INSUMOS DIRECTO DEL CATÁLOGO NUEVO
$insumos = $pdo->query("
    SELECT p.id_producto, p.nombre, di.ml_totales 
    FROM productos p 
    JOIN detalles_insumo di ON p.id_producto = di.id_producto 
    WHERE p.es_insumo_grooming IN (1, 2) AND di.ml_totales > 0
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalizar_cita'])) {
    $id_cita = $_POST['id_cita'];
    $insumo_id = $_POST['insumo_id'];
    
    try {
        $pdo->beginTransaction();

        if (!isset($_POST['chk_nudos']) || !isset($_POST['chk_bano']) || !isset($_POST['chk_secado'])) {
            throw new Exception("Error: Es obligatorio marcar todos los ítems del checklist.");
        }

        // LÓGICA DE MILILITROS: Extraemos el peso del paciente
        $stmtPeso = $pdo->prepare("SELECT peso_actual FROM mascotas m JOIN citas c ON m.id_mascota = c.mascota_id WHERE c.id_cita = ?");
        $stmtPeso->execute([$id_cita]);
        $peso_mascota = $stmtPeso->fetchColumn();

        // Calculamos los mililitros a descontar según el peso
        $ml_a_descontar = 15; // Mediano por defecto (5-15kg)
        if ($peso_mascota < 5) {
            $ml_a_descontar = 5; // Pequeño
        } elseif ($peso_mascota > 15) {
            $ml_a_descontar = 30; // Grande
        }

        // 1. Descontamos la cantidad exacta de mililitros
        $stmtUpdateML = $pdo->prepare("UPDATE detalles_insumo SET ml_totales = ml_totales - ? WHERE id_producto = ?");
        $stmtUpdateML->execute([$ml_a_descontar, $insumo_id]);

        // 2. Sincronizamos el stock físico de botellas (Redondea hacia arriba. Ej: 1.2 botellas = 2 en stock físico)
        $stmtSync = $pdo->prepare("
            UPDATE productos p 
            JOIN detalles_insumo di ON p.id_producto = di.id_producto 
            SET p.stock_actual = CEIL(di.ml_totales / di.contenido_por_envase) 
            WHERE p.id_producto = ?
        ");
        $stmtSync->execute([$insumo_id]);
        
        // 3. Finalizamos la cita
        $pdo->prepare("UPDATE citas SET estado = 'Finalizada' WHERE id_cita = ?")->execute([$id_cita]);
        
        // 4. Auditoría con detalle de ml consumidos
        $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Groomer', ?)")
            ->execute(["Servicio Cita $id_cita completado. Uso: $ml_a_descontar ml de insumo ID: $insumo_id", $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        $mensaje = "<div class='alert success' style='background:#d4edda; color:#155724; padding:10px; margin-bottom: 15px; border-radius: 5px;'>✅ Ficha cerrada. Se consumieron $ml_a_descontar ml de producto. Stock ajustado.</div>";
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $mensaje = "<div class='alert error' style='background:#fee2e2; color:#dc2626; padding:10px; margin-bottom: 15px; border-radius: 5px;'>❌ " . $e->getMessage() . "</div>";
    }
}

$sqlAgenda = "SELECT c.*, m.nombre as mascota, m.especie, m.peso_actual, u_cli.telefono as tel_cliente, u_cli.nombre_completo as nombre_cli
              FROM citas c 
              JOIN mascotas m ON c.mascota_id = m.id_mascota 
              JOIN usuarios u_cli ON m.propietario_id = u_cli.id_usuario
              WHERE c.groomer_id = ? AND c.estado IN ('Confirmada', 'Finalizada')
              ORDER BY c.fecha_hora_inicio ASC";
$stmtAgenda = $pdo->prepare($sqlAgenda);
$stmtAgenda->execute([$groomer['id_groomer']]);
$agenda = $stmtAgenda->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agenda Groomer - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; margin: 0; display: flex; }
        .sidebar { width: 250px; background: #2d3436; color: white; height: 100vh; padding: 30px 20px; position: fixed; }
        .main { margin-left: 290px; padding: 40px; width: 100%; }
        .card { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); border-left: 5px solid #0984e3; }
        .btn { background: #00b894; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; margin-top: 10px; }
        .btn-wa { background: #25D366; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; margin-top:10px;}
        .nav-link { color: #bdc3c7; text-decoration: none; display: block; margin-bottom: 15px; font-size: 15px; }
        .nav-link:hover { color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3 style="margin-top: 0;"><?php echo htmlspecialchars($groomer['nombre_completo']); ?></h3>
        <p style="color: #74b9ff; font-size: 14px; margin-top: -10px;">Especialidad: <?php echo htmlspecialchars($groomer['especialidad']); ?></p>
        <hr style="border: 0; border-top: 1px solid #636e72; margin: 20px 0;">
        
        <a href="groomer_dashboard.php" class="nav-link">📋 Mi Agenda</a>
        <a href="cambiar_password.php" class="nav-link">🔐 Cambiar Contraseña</a>
        <a href="logout.php" style="color: #ff7675; text-decoration: none; font-weight: bold; display: block; margin-top: 30px;">🚪 Cerrar Sesión</a>
    </div>

    <div class="main">
        <h2>✂️ Panel Técnico de Grooming</h2>
        <p style="color: #636e72;">Revisa tus citas confirmadas y llena la ficha técnica al momento de la atención.</p>
        
        <?php echo $mensaje; ?>

        <?php if (count($agenda) > 0): ?>
            <?php foreach ($agenda as $a): ?>
                <div class="card" <?php if($a['estado']=='Finalizada') echo 'style="border-color:#2ecc71; opacity:0.9;"'; ?>>
                    <h3 style="margin-top: 0; color: #2d3436;">🐾 <?php echo htmlspecialchars($a['mascota']); ?> - <?php echo date('H:i A', strtotime($a['fecha_hora_inicio'])); ?></h3>
                    <p style="margin-bottom: 5px;">Servicio: <b><?php echo htmlspecialchars($a['servicio']); ?></b></p>
                    <p style="margin-bottom: 15px;">Estado: <b style="color: <?php echo $a['estado'] == 'Finalizada' ? '#27ae60' : '#0984e3'; ?>;"><?php echo $a['estado']; ?></b> | Dueño: <?php echo htmlspecialchars($a['nombre_cli']); ?></p>
                    
                    <?php 
                        date_default_timezone_set('America/La_Paz');
                        $hora_cita = strtotime($a['fecha_hora_inicio']);
                        $hora_actual = time();
                    ?>

                    <?php if ($hora_cita <= $hora_actual && $a['estado'] == 'Confirmada'): ?>
                        <form method="POST" style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:5px;">
                            <input type="hidden" name="id_cita" value="<?php echo $a['id_cita']; ?>">
                            
                            <?php if (!empty($a['comentario_cliente'])): ?>
                                <div style="background: #fff3cd; color: #856404; padding: 10px; border-left: 4px solid #ffeeba; margin-bottom: 15px; font-size: 13px;">
                                    <b>Nota del cliente:</b> <?php echo htmlspecialchars($a['comentario_cliente']); ?>
                                </div>
                            <?php endif; ?>

                            <h4 style="margin-top: 0; color: #d35400;">📋 Checklist Obligatorio</h4>
                            
                            <?php if (strpos($a['servicio'], 'Solo Baño') !== false): ?>
                                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="chk_nudos" required> Revisión general de piel</label>
                                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="chk_bano" required> Baño y limpieza de glándulas</label>
                                <label style="display: block; margin-bottom: 15px;"><input type="checkbox" name="chk_secado" required> Secado rápido a turbina</label>
                            <?php elseif (strpos($a['servicio'], 'Spa Premium') !== false): ?>
                                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="chk_nudos" required> Deslanado profundo</label>
                                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="chk_bano" required> Masaje relajante e hidratación capilar</label>
                                <label style="display: block; margin-bottom: 15px;"><input type="checkbox" name="chk_secado" required> Secado, corte a tijera y perfume premium</label>
                            <?php else: ?>
                                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="chk_nudos" required> Revisión de piel y nudos</label>
                                <label style="display: block; margin-bottom: 8px;"><input type="checkbox" name="chk_bano" required> Baño profundo realizado</label>
                                <label style="display: block; margin-bottom: 15px;"><input type="checkbox" name="chk_secado" required> Secado y corte finalizado</label>
                            <?php endif; ?>
                            
                            <label style="display:block; margin-top:10px; font-weight:bold; font-size: 14px;">Insumo utilizado (El sistema descontará ml según peso):</label>
                            <select name="insumo_id" required style="width:100%; padding:10px; margin-bottom:15px; border-radius: 4px; border: 1px solid #ccc;">
                                <option value="">- Selecciona el producto base -</option>
                                <?php foreach($insumos as $i): ?>
                                    <option value="<?php echo $i['id_producto']; ?>"><?php echo htmlspecialchars($i['nombre']); ?> (Disponible: <?php echo $i['ml_totales']; ?> ml)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="finalizar_cita" class="btn">✅ Cerrar Ficha (Ajustar Stock)</button>
                        </form>

                    <?php elseif ($hora_cita > $hora_actual && $a['estado'] == 'Confirmada'): ?>
                        <div style="background: #e1f5fe; padding: 15px; border-radius: 5px; color: #0277bd; border-left: 4px solid #0288d1; font-size: 14px;">
                            ⏳ <b>Ficha Bloqueada:</b> El checklist y el uso de inventario se habilitarán automáticamente a la hora programada del servicio (<b><?php echo date('H:i A', $hora_cita); ?></b>). Prepara tu estación.
                        </div>
                    <?php endif; ?>

                    <?php if ($a['estado'] == 'Finalizada'): ?>
                        <?php 
                            $numero_limpio = preg_replace('/[^0-9]/', '', $a['tel_cliente']);
                            $mensaje_wa = urlencode("🐾 ¡Hola " . $a['nombre_cli'] . "! Te escribimos de Pet Spa. Tu mascota " . $a['mascota'] . " ya está hermosa y lista para que pases a recogerla. ¡Te esperamos!");
                            $link_wa = "https://wa.me/591" . $numero_limpio . "?text=" . $mensaje_wa;
                        ?>
                        <div style="margin-top: 15px;">
                            <a href="<?php echo $link_wa; ?>" target="_blank" class="btn-wa">📱 Enviar Aviso de Recojo (WhatsApp)</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #7f8c8d; font-style: italic;">No tienes citas programadas para hoy.</p>
        <?php endif; ?>
    </div>
    
    <script>
        document.querySelectorAll('form').forEach(form => {
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            const btnSubmit = form.querySelector('button[name="finalizar_cita"]');
            
            if(checkboxes.length > 0 && btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.5';
                btnSubmit.style.cursor = 'not-allowed';
                
                form.addEventListener('change', () => {
                    const todosMarcados = Array.from(checkboxes).every(c => c.checked);
                    btnSubmit.disabled = !todosMarcados;
                    btnSubmit.style.opacity = todosMarcados ? '1' : '0.5';
                    btnSubmit.style.cursor = todosMarcados ? 'pointer' : 'not-allowed';
                });
            }
        });
    </script>
</body>
</html>