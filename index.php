<?php
// C:\xampp\htdocs\petspa\index.php
require 'db.php';

// Consulta para obtener los 3 mejores groomers basados en su rating promedio
// Esto cumple con el requerimiento de mostrar reseñas/destacados
$stmt = $pdo->query("SELECT u.nombre_completo, g.especialidad, g.rating_promedio 
                     FROM groomers g 
                     JOIN usuarios u ON g.usuario_id = u.id_usuario 
                     WHERE g.disponible = 1 
                     ORDER BY g.rating_promedio DESC LIMIT 3");
$destacados = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pet Spa - El Despertar del Brillo</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; color: #2d3436; background: #f9f9f9; }
        .hero { background: linear-gradient(135deg, #6c5ce7, #a29bfe); color: white; padding: 80px 20px; text-align: center; }
        .nav-buttons { margin-top: 30px; }
        .btn { padding: 15px 30px; border-radius: 30px; text-decoration: none; font-weight: bold; margin: 10px; display: inline-block; transition: 0.3s; }
        .btn-client { background: #00b894; color: white; }
        .btn-staff { background: white; color: #6c5ce7; border: 2px solid white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .section { padding: 50px 20px; max-width: 1000px; margin: auto; text-align: center; }
        .grid { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 30px; }
        .card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); width: 280px; }
        .rating { color: #f1c40f; font-weight: bold; }
    </style>
</head>
<body>

    <header class="hero">
        <h1>🐾 Bienvenido a Pet Spa</h1>
        <p>Cuidado integral y profesional para perros, gatos y animales domésticos.</p>
        <div class="nav-buttons">
            <a href="portal_clientes.php" class="btn btn-client">Portal de Clientes</a>
            <a href="portal_staff.php" class="btn btn-staff">Acceso Personal (Staff/Admin)</a>
            <a href="catalogo.php" class="btn btn-client">🛍️ Ver Tienda Online</a>
        </div>
    </header>

    <section class="section">
        <h2>Nuestros Servicios</h2>
        <div class="grid">
            <div class="card">
                <h3>Baño & Higiene</h3>
                <p>Hidratación profunda, secado manual y limpieza de oídos.</p>
            </div>
            <div class="card">
                <h3>Cortes con Estilo</h3>
                <p>Cortes de raza y estilismo especializado para mascotas.</p>
            </div>
            <div class="card">
                <h3>Spa Terapéutico</h3>
                <p>Masajes relajantes y aromaterapia para reducir el estrés.</p>
            </div>
        </div>
    </section>

    <section class="section" style="background: #ffffff;">
        <h2>Groomers Destacados</h2>
        <p>Conoce a los profesionales mejor calificados por nuestra comunidad.</p>
        <div class="grid">
            <?php foreach($destacados as $g): ?>
                <div class="card">
                    <img src="https://cdn-icons-png.flaticon.com/512/3460/3460473.png" width="60" alt="Groomer">
                    <h3><?php echo htmlspecialchars($g['nombre_completo']); ?></h3>
                    <p class="rating">⭐ <?php echo $g['rating_promedio']; ?> / 5.0</p>
                    <p><b>Especialidad:</b><br><?php echo htmlspecialchars($g['especialidad']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

</body>
</html>