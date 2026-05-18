<?php
// C:\xampp\htdocs\petspa\auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html?error=no_autorizado");
    exit();
}

// 2. Control de Inactividad de 30 minutos (1800 segundos)
$tiempo_limite = 1800; 

if (isset($_SESSION['ultima_actividad'])) {
    $duracion_inactividad = time() - $_SESSION['ultima_actividad'];
    
    if ($duracion_inactividad > $tiempo_limite) {
        // La sesión expiró por tiempo
        session_unset();
        session_destroy();
        header("Location: index.html?error=sesion_expirada");
        exit();
    }
}

// 3. Actualizar el marcador de tiempo en cada acción del usuario
$_SESSION['ultima_actividad'] = time();
?>