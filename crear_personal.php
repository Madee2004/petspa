<?php
// C:\xampp\htdocs\petspa\crear_personal.php
require 'auth_check.php';
require 'db.php';

// Seguridad: Solo el Administrador (Rol 1) puede acceder 
if ($_SESSION['rol'] != 1) { 
    header("Location: index.php"); 
    exit(); 
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $email  = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $rol_id = $_POST['rol_id'];
    $turno  = $_POST['turno']; 
    
    // Contraseña temporal segura 
    $pass_temp = "PetSpa2026*"; 
    $hash = password_hash($pass_temp, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        $pdo->beginTransaction();

        // 1. Insertar en usuarios incluyendo el turno 
        $sqlUser = "INSERT INTO usuarios (rol_id, nombre_completo, email, password_hash, estado, esta_verificado, cambio_password_pendiente, turno) 
                    VALUES (?, ?, ?, ?, 'Activo', 1, 1, ?)";
        $stmt = $pdo->prepare($sqlUser);
        $stmt->execute([$rol_id, $nombre, $email, $hash, $turno]);

        // 2. Recuperar el ID generado (necesario para la relación 1:1 con groomers)
        $stmtId = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmtId->execute([$email]);
        $res = $stmtId->fetch();
        $nuevo_id = $res['id_usuario'];

        // 3. Lógica para Groomer (Rol 3)
        if ($rol_id == 3) {
            $esp = htmlspecialchars($_POST['especialidad']);
            $stmtG = $pdo->prepare("INSERT INTO groomers (usuario_id, especialidad, disponible) VALUES (?, ?, 1)");
            $stmtG->execute([$nuevo_id, $esp]);
        }

        // 4. Registro en Audit Logs para Trazabilidad 
        $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, 'Admin', ?, ?)");
        $log->execute(["Creado personal ($turno): $email", $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        $pdo->commit();
        
        // 5. Envío de correo real con el token/clave temporal
        require_once 'mailer.php';
        if (enviarToken($email, $pass_temp)) {
            $mensaje = "<div class='alert success'>✅ Empleado en turno $turno creado. Se envió la clave temporal al correo.</div>";
        } else {
            $mensaje = "<div class='alert warning'>⚠️ Empleado creado en DB, pero hubo un error al enviar el correo.</div>";
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $mensaje = "<div class='alert error'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Personal - Pet Spa Admin</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; padding-top: 50px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); width: 100%; max-width: 450px; }
        h2 { color: #2d3436; margin-bottom: 20px; text-align: center; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #636e72; }
        input, select { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #dfe6e9; border-radius: 8px; box-sizing: border-box; }
        .btn-submit { background-color: #00b894; color: white; border: none; padding: 14px; width: 100%; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background-color: #00a887; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .success { background-color: #dff9fb; color: #009432; border: 1px solid #badc58; }
        .warning { background-color: #fff9db; color: #f39c12; border: 1px solid #f1c40f; }
        .error { background-color: #fab1a0; color: #d63031; border: 1px solid #ff7675; }
        #seccion_groomer { display: none; background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #00b894; }
    </style>
</head>
<body>

<div class="card">
    <h2>➕ Registrar Personal</h2>
    
    <?php echo $mensaje; ?>

    <form method="POST" id="formPersonal">
        <label>Nombre Completo:</label>
        <input type="text" name="nombre" placeholder="Ej. Juan Perez" required>

        <label>Correo Institucional:</label>
        <input type="email" name="email" placeholder="correo@petspa.com" required>

        <label>Rol del Empleado:</label>
        <select name="rol_id" id="rol_id" onchange="toggleGroomer()" required>
            <option value="">-- Seleccione un Rol --</option>
            <option value="2">Recepción</option>
            <option value="3">Groomer (Estilista)</option>
        </select>

        <div id="seccion_groomer">
            <label>Especialidad técnica:</label>
            <input type="text" name="especialidad" placeholder="Ej. Corte de raza, Gatos, Spa">
        </div>

        <label>Asignar Turno:</label>
        <select name="turno" required>
            <option value="Mañana">Mañana (08:00 - 14:00)</option>
            <option value="Tarde">Tarde (14:00 - 20:00)</option>
            <option value="Noche">Noche (Emergencias)</option>
        </select>

        <button type="submit" class="btn-submit">Registrar y Enviar Clave</button>
    </form>
    
    <div style="text-align: center; margin-top: 15px;">
        <a href="admin_dashboard.php" style="color: #636e72; text-decoration: none; font-size: 14px;">← Volver al Panel</a>
    </div>
</div>

<script>
function toggleGroomer() {
    const rol = document.getElementById('rol_id').value;
    const seccion = document.getElementById('seccion_groomer');
    // Si el rol es 3 (Groomer), mostramos la especialidad
    if (rol === '3') {
        seccion.style.display = 'block';
    } else {
        seccion.style.display = 'none';
    }
}
</script>

</body>
</html>