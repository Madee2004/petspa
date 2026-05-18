<?php
// C:\xampp\htdocs\petspa\verificar_token.php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $ua = $_SERVER['HTTP_USER_AGENT'];

    try {
        // 1. Llamar al procedimiento de tu SQL
        $stmt = $pdo->prepare("CALL validar_y_activar_usuario(?, ?, ?)");
        $stmt->execute([$token, $ip, $ua]);
        $resultado = $stmt->fetch();

        // --- SOLUCIÓN AL ERROR: Cerramos el cursor para liberar la conexión ---
        $stmt->closeCursor(); 

        if ($resultado && $resultado['resultado'] === 'SUCCESS') {
            // 2. Ahora que la conexión está libre, buscamos al usuario para la sesión
            // Filtramos por el email que acaba de ser activado
            $stmtUser = $pdo->prepare("SELECT id_usuario, rol_id FROM usuarios WHERE estado = 'Activo' AND esta_verificado = 1 ORDER BY fecha_registro DESC LIMIT 1");
            $stmtUser->execute();
            $user = $stmtUser->fetch();

            if ($user) {
                $_SESSION['usuario_id'] = $user['id_usuario'];
                $_SESSION['rol'] = $user['rol_id'];
                $_SESSION['ultima_actividad'] = time(); // Requisito: Iniciar reloj de 30 min
                
                header("Location: dashboard.php");
                exit();
            }
        } else {
            echo "<h2>Error</h2>";
            echo "<p>" . ($resultado['mensaje'] ?? 'Token inválido o expirado (15 min)') . "</p>";
            echo "<a href='verificar.html'>Volver a intentar</a>";
        }
    } catch (Exception $e) {
        echo "Error del sistema: " . $e->getMessage();
    }
}
?>