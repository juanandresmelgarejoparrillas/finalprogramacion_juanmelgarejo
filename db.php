<?php
// db.php - Conexión a la base de datos
$host = 'localhost';
$user = 'root';
$pass = ''; // Por defecto en XAMPP
$db = 'sistema_gestion_futuro';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Configurar charset a UTF-8 para español
$conn->set_charset("utf8");
?>