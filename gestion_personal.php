<?php
// C:\xampp\htdocs\petspa\gestion_personal.php
require 'auth_check.php';
require 'db.php';

// Seguridad: Solo el Administrador (Rol 1) 
if ($_SESSION['rol'] != 1) { header("Location: index.php"); exit(); }

// Consulta: Unimos usuarios con groomers para ver especialidades
$sql = "SELECT u.id_usuario, u.nombre_completo, u.email, u.turno, r.nombre_rol, g.especialidad 
        FROM usuarios u 
        INNER JOIN roles r ON u.rol_id = r.id_rol 
        LEFT JOIN groomers g ON u.id_usuario = g.usuario_id
        WHERE u.rol_id IN (2, 3) 
        ORDER BY u.rol_id ASC";
$staff = $pdo->query($sql)->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Personal - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
        th { background-color: #00b894; color: white; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-turno { background: #e1f5fe; color: #01579b; }
        .btn-add { background: #0984e3; color: white; padding: 10px 15px; text-decoration: none; border-radius: 6px; float: right; }
    </style>
</head>
<body>
    <div class="container">
        <a href="crear_personal.php" class="btn-add">+ Registrar Nuevo Empleado</a>
        <h2>👥 Control de Personal Técnico</h2>
        <p>Listado oficial de empleados y sus horarios asignados.</p>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Rol</th>
                    <th>Turno</th>
                    <th>Especialidad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $s): ?>
                <tr>
                    <td><b><?php echo $s['nombre_completo']; ?></b><br><small><?php echo $s['email']; ?></small></td>
                    <td><?php echo $s['nombre_rol']; ?></td>
                    <td><span class="badge badge-turno"><?php echo $s['turno']; ?></span></td>
                    <td><?php echo $s['especialidad'] ?? 'N/A'; ?></td>
                    <td>
                        <a href="editar_staff.php?id=<?php echo $s['id_usuario']; ?>" style="color: #0984e3;">Editar</a> | 
                        <a href="borrar_staff.php?id=<?php echo $s['id_usuario']; ?>" style="color: #d63031;">Eliminar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br><a href="admin_dashboard.php">← Volver al Dashboard</a>
    </div>
</body>
</html>