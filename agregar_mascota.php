<?php
// C:\xampp\htdocs\petspa\agregar_mascota.php
require 'auth_check.php';
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = htmlspecialchars($_POST['nombre']);
    $especie = $_POST['especie'];
    $raza = htmlspecialchars($_POST['raza']);
    $fecha_nac = $_POST['fecha_nacimiento'];
    $peso = $_POST['peso_actual'];
    $alergias = htmlspecialchars($_POST['alergias']);
    $temperamento = $_POST['temperamento'];
    $vacunas = isset($_POST['vacunas_al_dia']) ? 1 : 0;
    
    // Gestión de la Foto
    $foto_nombre = "default_pet.png";
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
        $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $foto_nombre = "pet_" . time() . "." . $ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], "uploads/" . $foto_nombre);
    }

    try {
        $sql = "INSERT INTO mascotas (propietario_id, nombre, especie, raza, fecha_nacimiento, peso_actual, alergias, temperamento, vacunas_al_dia, foto_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['usuario_id'], $nombre, $especie, $raza, $fecha_nac, $peso, $alergias, $temperamento, $vacunas, $foto_nombre]);

        header("Location: dashboard.php?msg=mascota_creada");
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Mascota</title>
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        input, select, textarea { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ddd; box-sizing: border-box; }
        label { font-weight: bold; font-size: 14px; color: #555; }
    </style>
</head>
<body style="font-family: sans-serif; background: #f0f2f5; padding: 40px;">
    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 15px; shadow: 0 4px 10px rgba(0,0,0,0.1);">
        <h2>🐾 Registrar Nueva Mascota</h2>
        <form method="POST" enctype="multipart/form-data" class="form-grid">
            <div>
                <label>Nombre:</label>
                <input type="text" name="nombre" required>
            </div>
            <div>
                <label>Especie:</label>
                <select name="especie" required>
                    <option value="Perro">Perro</option>
                    <option value="Gato">Gato</option>
                    <option value="Conejo">Conejo</option>
                    <option value="Ave">Ave / Pájaro</option>
                    <option value="Roedor">Roedor (Hámster, Cuy, etc.)</option>
                    <option value="Reptil">Reptil</option>
                    <option value="Otro">Otro animal doméstico</option>
                </select>
            </div>
            <div>
                <label>Raza:</label>
                <input type="text" name="raza" required>
            </div>
            <div>
                <label>Peso Actual (kg)*:</label>
                <input type="number" step="0.1" name="peso_actual" required>
            </div>
            <div>
                <label>Temperamento*:</label>
                <select name="temperamento" required>
                    <option value="Tranquilo">Tranquilo</option>
                    <option value="Nervioso">Nervioso</option>
                    <option value="Miedoso">Miedoso</option>
                    <option value="Agresivo">Agresivo</option>
                </select>
            </div>
            <div>
                <label>Fecha Nacimiento:</label>
                <input type="date" name="fecha_nacimiento">
            </div>
            <div class="full-width">
                <label>Alergias / Notas Médicas*:</label>
                <textarea name="alergias" rows="2" placeholder="Si no tiene, poner 'Ninguna'" required></textarea>
            </div>
            <div class="full-width">
                <label>
                    <input type="checkbox" name="vacunas_al_dia" value="1"> ¿Tiene vacunas al día?*
                </label>
            </div>
            <div class="full-width">
                <label>Foto de la mascota:</label>
                <input type="file" name="foto" accept="image/*">
            </div>
            <button type="submit" class="full-width" style="padding:15px; background:#00b894; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">Guardar Mascota</button>
        </form>
    </div>
</body>
</html>