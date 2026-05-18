<?php
// C:\xampp\htdocs\petspa\crear_admin.php
require 'db.php';

$nombre = "Administrador Central";
$email  = "caguilarm@fcpn.edu.bo";
$pass   = "Admin123*"; // Esta será tu contraseña segura
$hash   = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    // 1. Limpiar intentos previos
    $pdo->prepare("DELETE FROM usuarios WHERE email = ?")->execute([$email]);

    // 2. Insertar con todos los privilegios y requisitos de la rúbrica 
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