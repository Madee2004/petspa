<?php
// C:\xampp\htdocs\petspa\db.php
$host = 'localhost';
$db   = 'spamascotas'; // El nombre exacto de tu SQL
$user = 'root';
$pass = ''; 
$port = '3307'; // El puerto que indica tu phpMyAdmin

try {
    // Usamos el charset utf8mb4 para evitar errores con tildes o caracteres especiales
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>