<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Clientes - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; background: #f9f9f9; }
        .header { background: #00b894; color: white; padding: 40px; text-align: center; }
        .container { max-width: 900px; margin: -30px auto 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 0 20px; }
        .box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .services-list { list-style: none; padding: 0; }
        .services-list li { padding: 10px 0; border-bottom: 1px solid #eee; color: #636e72; }
        .services-list li b { color: #2d3436; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        .btn-client { width: 100%; padding: 12px; background: #00b894; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .register-box { text-align: center; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px; }
        .btn-reg { color: #00b894; font-weight: bold; text-decoration: none; }
    </style>
</head>
<body>

<div class="header">
    <h1>Bienvenido a Pet Spa</h1>
    <p>Cuidado de lujo para tus mascotas domésticas</p>
</div>

<div class="container">
    <div class="box">
        <h3>Servicios Disponibles</h3>
        <ul class="services-list">
            <li><b>Baño Completo:</b> Hidratación y secado manual.</li>
            <li><b>Corte de Raza:</b> Estilismo según el estándar.</li>
            <li><b>Spa Terapéutico:</b> Masajes y aceites esenciales.</li>
            <li><b>Corte de Uñas:</b> Cuidado higiénico seguro.</li>
            <li><b>Limpieza Dental:</b> Aliento fresco y salud bucal.</li>
        </ul>
        <p style="font-size: 14px; color: #b2bec3;">* Atendemos perros, gatos y otros animales domésticos.</p>
    </div>

    <div class="box">
        <h3>Iniciar Sesión</h3>
        <form action="login_accion.php" method="POST">
            <input type="email" name="email" placeholder="Tu correo electrónico" required>
            <input type="password" name="password" placeholder="Tu contraseña" required>
            <button type="submit" class="btn-client">Entrar a mi Panel</button>
        </form>

        <div class="register-box">
            <p>¿Aún no tienes cuenta?</p>
            <a href="index.html" class="btn-reg">Regístrate aquí ahora</a>
        </div>
    </div>
</div>

<div style="text-align: center;">
    <a href="index.php" style="color: #636e72; text-decoration: none;">← Volver al inicio</a>
</div>

</body>
</html>