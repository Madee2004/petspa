<?php
// C:\xampp\htdocs\petspa\gestion_clientes.php
require 'auth_check.php';
require 'db.php';

if ($_SESSION['rol'] != 1) { header("Location: index.php"); exit(); }

// Consulta: Contamos cuántas mascotas tiene cada cliente
$sql = "SELECT u.id_usuario, u.nombre_completo, u.email, u.telefono, COUNT(m.id_mascota) as total_mascotas 
        FROM usuarios u 
        LEFT JOIN mascotas m ON u.id_usuario = m.propietario_id 
        WHERE u.rol_id = 4 
        GROUP BY u.id_usuario";
$clientes = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Clientes - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
        .pet-count { background: #fab1a0; color: #d63031; padding: 2px 10px; border-radius: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h2>📱 Directorio de Clientes</h2>
        <table>
            <tr style="background:#2d3436; color:white;">
                <th style="padding:10px;">Cliente</th>
                <th>Contacto</th>
                <th>Mascotas Reg.</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($clientes as $c): ?>
            <tr>
                <td style="padding:15px; border-bottom:1px solid #eee;">
                    <b><?php echo $c['nombre_completo']; ?></b>
                </td>
                <td><?php echo $c['email']; ?><br><small><?php echo $c['telefono']; ?></small></td>
                <td><span class="pet-count"><?php echo $c['total_mascotas']; ?></span></td>
                <td>
                    <a href="ver_mascotas_cliente.php?id=<?php echo $c['id_usuario']; ?>">Ver Mascotas</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br><a href="admin_dashboard.php">← Volver al Dashboard</a>
    </div>
</body>
</html>