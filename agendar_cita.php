<?php
// C:\xampp\htdocs\petspa\agendar_cita.php
require 'auth_check.php';
require 'db.php';

if ($_SESSION['rol'] != 4) { header("Location: index.php"); exit(); }

$mensaje = "";
$limite_diario_groomer = 5; 

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

        $stmtLimite = $pdo->prepare("SELECT COUNT(*) FROM citas WHERE groomer_id = ? AND DATE(fecha_hora_inicio) = ? AND estado != 'Cancelada'");
        $stmtLimite->execute([$groomer_id, $fecha]);
        if ($stmtLimite->fetchColumn() >= $limite_diario_groomer) {
            throw new Exception("El estilista ya alcanzó su límite máximo de $limite_diario_groomer mascotas para este día.");
        }

        $stmtPet = $pdo->prepare("SELECT nombre, especie, peso_actual, temperamento FROM mascotas WHERE id_mascota = ?");
        $stmtPet->execute([$mascota_id]);
        $pet = $stmtPet->fetch();

        // 1. CÁLCULOS BASE Y MODIFICADORES
        $monto_final = $catalogo_servicios[$servicio_nombre]['precio'];
        $duracion_final = $catalogo_servicios[$servicio_nombre]['tiempo_base'];
        $tiempo_limpieza = 10; 

        if (strtolower($pet['especie']) === 'gato') {
            $duracion_final += 20; 
            $tiempo_limpieza += 10; 
        } elseif (strtolower($pet['especie']) === 'ave' || strtolower($pet['especie']) === 'roedor') {
            $duracion_final -= 15; 
            $monto_final -= 20;
        }

        if ($pet['peso_actual'] > 15) { 
            $monto_final += 30; $duracion_final += 20; 
        } 
        if ($pet['temperamento'] === 'Agresivo' || $pet['temperamento'] === 'Nervioso') { 
            $monto_final += 20; $duracion_final += 20; 
        }

        // 2. PROCESAR LOS SERVICIOS EXTRAS SELECCIONADOS
        $extras_seleccionados = $_POST['extras'] ?? [];
        $nombres_extras = [];
        
        foreach ($extras_seleccionados as $ext) {
            list($nombre_ex, $precio_ex, $tiempo_ex) = explode('|', $ext);
            $monto_final += (float)$precio_ex;
            $duracion_final += (int)$tiempo_ex;
            $nombres_extras[] = $nombre_ex;
        }

        if (count($nombres_extras) > 0) {
            $servicio_nombre .= " (Extras: " . implode(", ", $nombres_extras) . ")";
        }

        // 3. CAPTURAR EL COMENTARIO
        $comentario = htmlspecialchars($_POST['comentario_cliente'] ?? '');
        $duracion_final += $tiempo_limpieza;

        // 4. VALIDACIÓN DE CHOQUES DE HORARIO
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

        // 5. INSERTAR EN LA BASE DE DATOS
        $sqlCita = "INSERT INTO citas (mascota_id, groomer_id, fecha_hora_inicio, estado, monto, duracion_minutos, servicio, comentario_cliente, pago_estado, metodo_pago) 
                    VALUES (?, ?, ?, 'Pendiente', ?, ?, ?, ?, 'No Pagado', 'Pendiente')";
        $pdo->prepare($sqlCita)->execute([$mascota_id, $groomer_id, $fecha_hora_inicio, $monto_final, $duracion_final, $servicio_nombre, $comentario]);

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
        input, select, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 14px; background: #00b894; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 15px; font-size: 15px; }
        .resumen-caja { background: #e1f5fe; padding: 15px; border-radius: 8px; border: 1px solid #b3e5fc; margin-top: 25px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="color: #00b894; text-align: center; margin-top: 0;">📅 Agendar Nuevo Servicio</h2>
        <?php echo $mensaje; ?>
        
        <form method="POST" id="formCita">
            <label>1. Selecciona tu Mascota:</label>
            <select name="mascota_id" id="mascota_id" required>
                <option value="">-- Mis Mascotas --</option>
                <?php foreach ($mis_mascotas as $m): ?>
                    <!-- Ocultamos los datos de la mascota para que JS los lea -->
                    <option value="<?php echo $m['id_mascota']; ?>" 
                            data-especie="<?php echo strtolower($m['especie']); ?>" 
                            data-peso="<?php echo $m['peso_actual']; ?>" 
                            data-temperamento="<?php echo $m['temperamento']; ?>"
                            <?php if(isset($_POST['mascota_id']) && $_POST['mascota_id'] == $m['id_mascota']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($m['nombre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>2. ¿Qué servicio principal necesita?</label>
            <select name="servicio" id="servicio" required>
                <option value="">-- Selecciona un Servicio --</option>
                <?php foreach ($catalogo_servicios as $nombre => $datos): ?>
                    <option value="<?php echo $nombre; ?>" <?php if(isset($_POST['servicio']) && $_POST['servicio'] == $nombre) echo 'selected'; ?>><?php echo $nombre; ?></option>
                <?php endforeach; ?>
            </select>

            <label>3. Servicios Extra (Opcional):</label>
            <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-top: 5px;">
                <label style="font-weight: normal; margin-top: 0; display: block;">
                    <input type="checkbox" name="extras[]" class="extra-cb" value="Corte de Uñas|15|10"> ✂️ Corte de Uñas (+15 Bs)
                </label>
                <label style="font-weight: normal; margin-top: 8px; display: block;">
                    <input type="checkbox" name="extras[]" class="extra-cb" value="Limpieza de Oídos|10|10"> 👂 Limpieza de Oídos (+10 Bs)
                </label>
                <label style="font-weight: normal; margin-top: 8px; display: block;">
                    <input type="checkbox" name="extras[]" class="extra-cb" value="Corte Higiénico|20|15"> 🪒 Corte Higiénico (+20 Bs)
                </label>
            </div>

            <label>4. Instrucciones especiales para el estilista (Opcional):</label>
            <textarea name="comentario_cliente" rows="2" placeholder="Ej: Tiene una herida en la pata derecha, por favor tratar con cuidado..."></textarea>

            <label>5. Estilista (Groomer):</label>
            <select name="groomer_id" required>
                <option value="">-- Profesionales Disponibles --</option>
                <?php foreach ($groomers_disponibles as $g): ?>
                    <option value="<?php echo $g['id_groomer']; ?>"><?php echo htmlspecialchars($g['nombre_completo']); ?></option>
                <?php endforeach; ?>
            </select>

            <label>6. Fecha del Servicio:</label>
            <input type="date" name="fecha" id="fecha" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>

            <label>7. Hora de Inicio:</label>
            <select name="hora" id="hora" required>
                <option value="">-- Selecciona bloque horario --</option>
                <?php 
                $horas = ['08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00'];
                foreach ($horas as $h) {
                    echo "<option value='$h'>$h</option>";
                }
                ?>
            </select>

            <!-- CAJA DE RESUMEN DE PRECIO -->
            <div class="resumen-caja" id="caja_resumen" style="display: none;">
                <h3 style="margin: 0 0 5px 0; color: #0277bd;">💰 Total Estimado: <span id="monto_preview">0</span> Bs.</h3>
                <p style="margin: 0; color: #0288d1; font-size: 13px;">Tiempo estimado (incluye limpieza): <span id="tiempo_preview">0</span> min</p>
                <p style="margin: 5px 0 0 0; color: #7f8c8d; font-size: 11px;">*El precio incluye recargos automáticos por tamaño, especie o temperamento si corresponde.</p>
            </div>

            <button type="submit" class="btn">Confirmar Reserva</button>
        </form>
        <br><a href="dashboard.php" style="display:block; text-align:center; color:#636e72; text-decoration:none;">← Volver al Panel</a>
    </div>

    <!-- CALCULADORA EN TIEMPO REAL -->
    <script>
        const catalogo = {
            'Solo Baño y Secado': { precio: 60, tiempo: 40 },
            'Grooming Completo (Corte y Baño)': { precio: 120, tiempo: 60 },
            'Spa Premium (Deslanado y Masaje)': { precio: 180, tiempo: 90 }
        };

        function calcularPrecio() {
            const petSelect = document.getElementById('mascota_id');
            const servSelect = document.getElementById('servicio');
            const cajaResumen = document.getElementById('caja_resumen');

            // Si no ha elegido mascota o servicio, escondemos la caja
            if (petSelect.value === "" || servSelect.value === "") {
                cajaResumen.style.display = 'none';
                return;
            }

            // Datos de la mascota seleccionada
            const selectedOption = petSelect.options[petSelect.selectedIndex];
            const especie = selectedOption.getAttribute('data-especie');
            const peso = parseFloat(selectedOption.getAttribute('data-peso'));
            const temperamento = selectedOption.getAttribute('data-temperamento');

            // Precios base
            let monto = catalogo[servSelect.value].precio;
            let duracion = catalogo[servSelect.value].tiempo;
            let limpieza = 10;

            // Modificadores por Especie
            if (especie === 'gato') {
                duracion += 20; limpieza += 10;
            } else if (especie === 'ave' || especie === 'roedor') {
                duracion -= 15; monto -= 20;
            }

            // Modificadores por Peso
            if (peso > 15) { monto += 30; duracion += 20; }

            // Modificadores por Temperamento
            if (temperamento === 'Agresivo' || temperamento === 'Nervioso') { monto += 20; duracion += 20; }

            // Modificadores por Extras
            document.querySelectorAll('.extra-cb:checked').forEach(cb => {
                let partes = cb.value.split('|');
                monto += parseFloat(partes[1]);
                duracion += parseInt(partes[2]);
            });

            duracion += limpieza;

            // Actualizar la caja y mostrarla
            document.getElementById('monto_preview').innerText = monto;
            document.getElementById('tiempo_preview').innerText = duracion;
            cajaResumen.style.display = 'block';
        }

        // Event Listeners: Escuchar cambios en los campos
        document.getElementById('mascota_id').addEventListener('change', calcularPrecio);
        document.getElementById('servicio').addEventListener('change', calcularPrecio);
        document.querySelectorAll('.extra-cb').forEach(cb => cb.addEventListener('change', calcularPrecio));
    </script>
</body>
</html>