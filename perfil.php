<?php
// C:\xampp\htdocs\petspa\auth_check.php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit();
}

// Lógica de inactividad de 30 minutos[cite: 2]
if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.html?error=sesion_expirada");
    exit();
}
$_SESSION['ultima_actividad'] = time(); // Actualiza el tiempo en cada acción
?>