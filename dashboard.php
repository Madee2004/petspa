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
    </div>
</body>
</html>