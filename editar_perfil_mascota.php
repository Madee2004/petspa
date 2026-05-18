<?php
// C:\xampp\htdocs\petspa\editar_perfil_mascota.php
require 'auth_check.php';
require 'db.php';

// 1. Validar parámetros de entrada
$id_mascota = $_GET['id'] ?? null;
$usuario_actual = $_SESSION['usuario_id'] ?? null;

if (!$id_mascota) {
    header("Location: dashboard.php?error=id_faltante");
    exit();
}

try {
    // 2. Consulta ÚNICA y estricta para verificar propiedad (RBAC)
    $stmt = $pdo->prepare("SELECT * FROM mascotas WHERE id_mascota = ? AND propietario_id = ?");
    $stmt->execute([$id_mascota, $usuario_actual]);
    $m = $stmt->fetch();

    // 3. Si no hay resultado, registramos el intento en logs y bloqueamos
    if (!$m) {
        $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address) VALUES (?, 'Seguridad', ?)");
        $log->execute(["Intento de acceso no autorizado a Mascota ID: $id_mascota", $_SERVER['REMOTE_ADDR']]);
        
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h2>🚫 Acceso Denegado</h2>
                <p>No tienes permiso para editar esta mascota o el registro no existe.</p>
                <a href='dashboard.php'>Volver al Dashboard</a>
             </div>");
    }
} catch (Exception $e) {
    die("Error de base de datos: " . $e->getMessage());
}

$mensaje = "";

// 4. Procesar la actualización (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $especie = $_POST['especie']; // Nueva opción de especie
    $peso = $_POST['peso_actual'];
    $temp = $_POST['temperamento'];
    $alergias = htmlspecialchars($_POST['alergias']);
    $vacunas = isset($_POST['vacunas_al_dia']) ? 1 : 0;
    $foto_url = $m['foto_url']; // Mantener la actual por defecto

    // Lógica para actualizar foto
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_url = "pet_" . time() . "." . $ext;
        
        // Asegúrate de que la carpeta 'uploads' exista
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }
        move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_url);
    }

    try {
        $update = $pdo->prepare("UPDATE mascotas SET especie = ?, peso_actual = ?, temperamento = ?, alergias = ?, vacunas_al_dia = ?, foto_url = ? WHERE id_mascota = ?");
        $update->execute([$especie, $peso, $temp, $alergias, $vacunas, $foto_url, $id_mascota]);
        
        // Registrar en Auditoría (Trazabilidad)
        $log = $pdo->prepare("INSERT INTO audit_logs (accion, rol_ejecutor, ip_address, user_agent) VALUES (?, 'Cliente', ?, ?)");
        $log->execute(["Actualizó perfil de mascota: ".$m['nombre'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);

        header("Location: dashboard.php?msg=mascota_actualizada");
        exit();
    } catch (Exception $e) {
        $mensaje = "<div style='color:red; padding:10px; border:1px solid red; border-radius:5px;'>❌ Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Perfil de <?php echo $m['nombre']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 15px; width: 450px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        h2 { color: #00b894; margin-top: 0; }
        label { display: block; margin-top: 15px; font-weight: bold; color: #444; }
        input, select, textarea { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .btn-save { width: 100%; padding: 14px; background: #00b894; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 20px; }
        .btn-save:hover { background: #00a082; }
        .preview-img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; display: block; margin: 10px auto; border: 2px solid #00b894; }
    </style>
</head>
<body>
    <div class="card">
        <h2>⚙️ Editar a <?php echo htmlspecialchars($m['nombre']); ?></h2>
        
        <?php if ($m['foto_url']): ?>
            <img src="uploads/<?php echo $m['foto_url']; ?>" class="preview-img" alt="Foto actual">
        <?php endif; ?>

        <?php echo $mensaje; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <label>Especie:</label>
            <select name="especie">
                <option value="Perro" <?php if($m['especie']=='Perro') echo 'selected'; ?>>Perro</option>
                <option value="Gato" <?php if($m['especie']=='Gato') echo 'selected'; ?>>Gato</option>
                <option value="Conejo" <?php if($m['especie']=='Conejo') echo 'selected'; ?>>Conejo</option>
                <option value="Ave" <?php if($m['especie']=='Ave') echo 'selected'; ?>>Ave / Pájaro</option>
                <option value="Roedor" <?php if($m['especie']=='Roedor') echo 'selected'; ?>>Roedor (Hámster, Cuy, etc.)</option>
                <option value="Otro" <?php if($m['especie']=='Otro') echo 'selected'; ?>>Otro</option>
            </select>

            <label>Peso Actual (kg)*:</label>
            <input type="number" step="0.1" name="peso_actual" value="<?php echo $m['peso_actual']; ?>" required>

            <label>Temperamento*:</label>
            <select name="temperamento">
                <option value="Tranquilo" <?php if($m['temperamento']=='Tranquilo') echo 'selected'; ?>>Tranquilo</option>
                <option value="Nervioso" <?php if($m['temperamento']=='Nervioso') echo 'selected'; ?>>Nervioso</option>
                <option value="Miedoso" <?php if($m['temperamento']=='Miedoso') echo 'selected'; ?>>Miedoso</option>
                <option value="Agresivo" <?php if($m['temperamento']=='Agresivo') echo 'selected'; ?>>Agresivo</option>
            </select>

            <label>Alergias / Notas Médicas*:</label>
            <textarea name="alergias" rows="3" required><?php echo htmlspecialchars($m['alergias']); ?></textarea>

            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                <input type="checkbox" name="vacunas_al_dia" value="1" <?php if($m['vacunas_al_dia']) echo 'checked'; ?> style="width: auto;"> ¿Vacunas al día?
            </label>

            <label>Cambiar foto (Opcional):</label>
            <input type="file" name="foto" accept="image/*">

            <button type="submit" class="btn-save">Guardar Cambios</button>
        </form>
        <br>
        <a href="dashboard.php" style="color: #636e72; text-decoration: none; display:block; text-align:center; font-size: 14px;">← Cancelar y volver</a>
    </div>
</body>
</html>