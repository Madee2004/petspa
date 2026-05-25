<?php
// C:\xampp\htdocs\petspa\agendar_cita.php
require 'auth_check.php';
require 'db.php';

if ($_SESSION['rol'] != 4) { header("Location: index.php"); exit(); }

$mensaje = "";
$limite_diario_groomer = 5; // Regla de negocio: Max 5 mascotas por día

$catalogo_servicios = [
    'Solo Baño y Secado' => ['precio' => 60, 'tiempo_base' => 40],
    'Grooming Completo (Corte y Baño)' => ['precio' => 120, 'tiempo_base' => 60],
    'Spa Premium (Deslanado y Masaje)' => ['precio' => 180, 'tiempo_base' => 90]
];

$stmtMascotas = $pdo->prepare("SELECT * FROM mascotas WHERE propietario_id = ?");
$stmtMascotas->execute([$_SESSION['usuario_id']]);
$mis_mascotas = $stmtMascotas->fetchAll();

$stmtGroomers = $pdo->query("SELECT g.id_groomer, u.nombre_completo, g.especialidad 
                             FROM groomers g JOIN usuarios u ON g.usuario_id = u.id_usuario 
                             WHERE u.estado = 'Activo'");
$groomers_disponibles = $stmtGroomers->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mascota_id = $_POST['mascota_id'];
    $groomer_id = $_POST['groomer_id'];
    $servicio_nombre = $_POST['servicio'] ?? 'Grooming Completo (Corte y Baño)';
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    
    $fecha_hora_inicio = $fecha . ' ' . $hora . ':00';

    try {
        $pdo->beginTransaction();

        date_default_timezone_set('America/La_Paz');
        if (strtotime($fecha_hora_inicio) < strtotime('now')) {
            throw new Exception("No puedes agendar citas en el pasado.");
        }

        // REGLA: Límite de citas por día para el Groomer
        $stmtLimite = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE groomer_id = ? AND DATE(fecha_hora_inicio) = ? AND estado != 'Cancelada'");
        $stmtLimite->execute([$groomer_id, $fecha]);
        if ($stmtLimite->fetchColumn() >= $limite_diario_groomer) {
            throw new Exception("El estilista ya alcanzó su límite máximo de $limite_diario_groomer mascotas para este día.");
        }

        $stmtPet = $pdo->prepare("SELECT nombre, especie, peso_actual, temperamento FROM mascotas WHERE id_mascota = ?");
        $stmtPet->execute([$mascota_id]);
        $pet = $stmtPet->fetch();

        // CÁLCULOS DINÁMICOS POR ESPECIE Y TEMPERAMENTO
        $monto_final = $catalogo_servicios[$servicio_nombre]['precio'];
        $duracion_final = $catalogo_servicios[$servicio_nombre]['tiempo_base'];
        $tiempo_limpieza = 10; // Base limpieza

        // Modificadores de especie
        if (strtolower($pet['especie']) === 'gato') {
            $duracion_final += 20; // Los gatos toman más tiempo
            $tiempo_limpieza += 10; // Desinfección extra
        } elseif (strtolower($pet['especie']) === 'ave' || strtolower($pet['especie']) === 'roedor') {
            $duracion_final -= 15; // Animales pequeños toman menos tiempo
            $monto_final -= 20;
        }

        // Modificadores físicos
        if ($pet['peso_actual'] > 15) { 
            $monto_final += 30; $duracion_final += 20; 
        } 
        if ($pet['temperamento'] === 'Agresivo' || $pet['temperamento'] === 'Nervioso') { 
            $monto_final += 20; $duracion_final += 20; 
        }
        
        $duracion_final += $tiempo_limpieza;

        // Validación de Choques
        $inicio_solicitado = strtotime($fecha_hora_inicio);
        $fin_solicitado = $inicio_solicitado + ($duracion_final * 60);

        $stmtCheck = $pdo->prepare("SELECT fecha_hora_inicio, duracion_minutos FROM citas WHERE groomer_id = ? AND DATE(fecha_hora_inicio) = ? AND estado != 'Cancelada'");
        $stmtCheck->execute([$groomer_id, $fecha]);
        
        foreach ($stmtCheck->fetchAll() as $cita_existente) {
            $inicio_existente = strtotime($cita_existente['fecha_hora_inicio']);
            $fin_existente = $inicio_existente + ($cita_existente['duracion_minutos'] * 60);
            if ($inicio_solicitado < $fin_existente && $fin_solicitado > $inicio_existente) {
                throw new Exception("El horario choca con otra atención. Tu servicio requiere $duracion_final minutos en total. Por favor, elige otra hora.");
            }
        }

        $sqlCita = "INSERT INTO citas (mascota_id, groomer_id, fecha_hora_inicio, estado, monto, duracion_minutos, servicio, pago_estado, metodo_pago) VALUES (?, ?, ?, 'Pendiente', ?, ?, ?, 'No Pagado', 'Pendiente')";
        $pdo->prepare($sqlCita)->execute([$mascota_id, $groomer_id, $fecha_hora_inicio, $monto_final, $duracion_final, $servicio_nombre]);

        $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Cliente', ?)")->execute(["Agendó $servicio_nombre para " . $pet['nombre'], $_SERVER['REMOTE_ADDR']]);

        $pdo->commit();
        header("Location: dashboard.php?msg=cita_solicitada");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $mensaje = "<div class='alert error' style='background:#fee2e2; color:#dc2626; padding:15px; border-radius:5px; margin-bottom:15px;'>⚠️ " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita - Pet Spa</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 450px; }
        label { display: block; margin-top: 15px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 14px; background: #00b894; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="color: #00b894; text-align: center; margin-top: 0;">📅 Agendar Nuevo Servicio</h2>
        <?php echo $mensaje; ?>
        
        <form method="POST">
            <label>1. Selecciona tu Mascota:</label>
            <select name="mascota_id" required>
                <option value="">-- Mis Mascotas --</option>
                <?php foreach ($mis_mascotas as $m): ?>
                    <option value="<?php echo $m['id_mascota']; ?>" <?php if(isset($_POST['mascota_id']) && $_POST['mascota_id'] == $m['id_mascota']) echo 'selected'; ?>><?php echo htmlspecialchars($m['nombre']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>2. ¿Qué servicio necesita?</label>
            <select name="servicio" required>
                <?php foreach ($catalogo_servicios as $nombre => $datos): ?>
                    <option value="<?php echo $nombre; ?>" <?php if(isset($_POST['servicio']) && $_POST['servicio'] == $nombre) echo 'selected'; ?>><?php echo $nombre; ?></option>
                <?php endforeach; ?>
            </select>

            <label>3. Estilista (Groomer):</label>
            <select name="groomer_id" required>
                <option value="">-- Profesionales Disponibles --</option>
                <?php foreach ($groomers_disponibles as $g): ?>
                    <option value="<?php echo $g['id_groomer']; ?>" <?php if(isset($_POST['groomer_id']) && $_POST['groomer_id'] == $g['id_groomer']) echo 'selected'; ?>><?php echo htmlspecialchars($g['nombre_completo']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>4. Fecha del Servicio:</label>
            <input type="date" name="fecha" id="fecha" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" value="<?php echo htmlspecialchars($_POST['fecha'] ?? ''); ?>" required>

            <label>5. Hora de Inicio:</label>
            <select name="hora" id="hora" required>
                <option value="">-- Selecciona bloque horario --</option>
                <?php 
                $horas = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '18:15'];
                foreach ($horas as $h) {
                    $selected = (isset($_POST['hora']) && $_POST['hora'] == $h) ? 'selected' : '';
                    echo "<option value='$h' $selected>$h</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn">Calcular y Confirmar Reserva</button>
        </form>
        <br><a href="dashboard.php" style="display:block; text-align:center; color:#636e72; text-decoration:none;">← Volver al Panel</a>
    </div>

    <script>
        document.getElementById('fecha').addEventListener('change', function() {
            const hoy = new Date();
            const fechaElegida = new Date(this.value + "T00:00:00");
            const horasOptions = document.getElementById('hora').options;

            if(fechaElegida.toDateString() === hoy.toDateString()) {
                const horaActual = hoy.getHours() + (hoy.getMinutes() / 60);
                for(let i = 1; i < horasOptions.length; i++) {
                    const horaOption = parseFloat(horasOptions[i].value.replace(':', '.'));
                    if(horaOption < horaActual) {
                        horasOptions[i].disabled = true;
                        horasOptions[i].text = horasOptions[i].value + " (Pasada)";
                    } else {
                        horasOptions[i].disabled = false;
                        horasOptions[i].text = horasOptions[i].value;
                    }
                }
            } else {
                for(let i = 1; i < horasOptions.length; i++) {
                    horasOptions[i].disabled = false;
                    horasOptions[i].text = horasOptions[i].value;
                }
            }
        });
    </script>
</body>
</html>