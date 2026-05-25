<?php
// C:\xampp\htdocs\petspa\recibo.php
require 'auth_check.php';
require 'db.php';

if (!isset($_GET['id_cita'])) { die("Error: Cita no especificada."); }
$id_cita = $_GET['id_cita'];

$sql = "SELECT c.*, m.nombre as mascota, u_cli.nombre_completo as cliente, u_cli.ci, u_gro.nombre_completo as groomer
        FROM citas c 
        JOIN mascotas m ON c.mascota_id = m.id_mascota 
        JOIN usuarios u_cli ON m.propietario_id = u_cli.id_usuario
        JOIN groomers g ON c.groomer_id = g.id_groomer 
        JOIN usuarios u_gro ON g.usuario_id = u_gro.id_usuario 
        WHERE c.id_cita = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id_cita]);
$cita = $stmt->fetch();

if (!$cita) { die("Cita no encontrada."); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo - Pet Spa</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background: #e0e0e0; display: flex; justify-content: center; padding: 20px; }
        .ticket { background: white; width: 300px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin: 0; font-size: 20px; }
        .divider { border-top: 1px dashed #000; margin: 15px 0; }
        .btn-print { display: block; width: 100%; padding: 10px; background: #2c3e50; color: white; text-align: center; text-decoration: none; font-family: sans-serif; margin-top: 20px; cursor: pointer; }
        @media print { .btn-print { display: none; } body { background: white; } .ticket { box-shadow: none; } }
    </style>
</head>
<body>
    <div class="ticket">
        <h2>🐾 PET SPA "EL DESPERTAR"</h2>
        <p style="text-align: center; font-size: 12px;">Comprobante de Servicio</p>
        <div class="divider"></div>
        <p><b>Fecha/Hora:</b> <?php echo date('d/m/Y H:i', strtotime($cita['fecha_hora_inicio'])); ?></p>
        <p><b>Ticket N°:</b> #000<?php echo $cita['id_cita']; ?></p>
        <p><b>Cliente:</b> <?php echo htmlspecialchars($cita['cliente']); ?></p>
        <p><b>CI:</b> <?php echo htmlspecialchars($cita['ci']); ?></p>
        <div class="divider"></div>
        <p><b>Paciente:</b> <?php echo htmlspecialchars($cita['mascota']); ?></p>
        <p><b>Servicio:</b> <?php echo htmlspecialchars($cita['servicio']); ?></p>
        <p><b>Atendido por:</b> <?php echo htmlspecialchars($cita['groomer']); ?></p>
        <div class="divider"></div>
        <h3 style="text-align: right; margin: 0;">TOTAL: <?php echo $cita['monto']; ?> Bs.</h3>
        <p style="text-align: right; margin: 5px 0 0 0; font-size: 12px;">Estado: <?php echo $cita['pago_estado']; ?> (<?php echo $cita['metodo_pago']; ?>)</p>
        <div class="divider"></div>
        <p style="text-align: center; font-size: 12px;">¡Gracias por confiar en nosotros!</p>
        
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    </div>
</body>
</html>