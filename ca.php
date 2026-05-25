<?php
// C:\xampp\htdocs\petspa\crear_admin.php
require 'db.php';
//Aquí lo cambias a tu correo para que tu seas el admin, recuerda que al momento de crear groomers, o clientes tiene que ser un correo real
//Yo uso mi correo institucional, y me llegan notificaciones de que no se encontraron los correos que intentan utilizar
//También se cambia en el mailer.php, editar_perfil_groomer.php
$nombre = "Administrador Central";
$email  = "caguilarm@fcpn.edu.bo";
$pass   = "Admin123*"; // Esta será tu contraseña segura
$hash   = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    // 1. Limpiar intentos previos
    $pdo->prepare("DELETE FROM usuarios WHERE email = ?")->execute([$email]);

    $sql = "INSERT INTO usuarios (rol_id, nombre_completo, email, password_hash, estado, esta_verificado, cambio_password_pendiente) 
            VALUES (1, ?, ?, ?, 'Activo', 1, 1)";
    
    $pdo->prepare($sql)->execute([$nombre, $email, $hash]);

    echo "<h3>✅ Administrador creado con éxito.</h3>";
    echo "<b>Email:</b> $email<br>";
    echo "<b>Password:</b> $pass<br>";
    echo "<br><a href='portal_staff.php'>Ir al Login</a>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>