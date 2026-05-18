<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal Staff - Pet Spa</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #2d3436; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; color: white; }
        .login-card { background: #636e72; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); width: 350px; text-align: center; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: none; border-radius: 5px; box-sizing: border-box; }
        .btn-staff { width: 100%; padding: 12px; background: #0984e3; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 10px; }
        .back-link { display: block; margin-top: 20px; color: #dfe6e9; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>

<div class="login-card">
    <img src="https://cdn-icons-png.flaticon.com/512/607/607414.png" width="80" alt="Staff Icon">
    <h2>Acceso Personal</h2>
    <p>Administración y Grooming</p>
    
    <form action="login_accion.php" method="POST">
        <input type="email" name="email" placeholder="Correo Institucional" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit" class="btn-staff">Ingresar al Sistema</button>
    </form>

    <a href="index.php" class="back-link">← Volver a la página principal</a>
</div>

</body>
</html>