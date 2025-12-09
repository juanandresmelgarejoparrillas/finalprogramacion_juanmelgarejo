<?php
// db.php - Archivo alternativo de conexión
// Este archivo funciona igual que 'config.php', pero parece apuntar a una base de datos diferente o antigua.
// Es importante revisar cuál de los dos se está usando realmente en cada archivo.

$host = 'localhost'; // El servidor local.
$user = 'root';      // El usuario administrador.
$pass = '';          // La contraseña (vacía).
$db = 'sistema_gestion_futuro'; // OJO: Aquí apunta a 'sistema_gestion_futuro', diferente a 'config.php'.

// Creamos la conexión usando los datos de arriba.
$conn = new mysqli($host, $user, $pass, $db);

// Verificamos si la conexión falló.
if ($conn->connect_error) {
    // Si falla, mostramos el mensaje de error y paramos todo.
    die("Error de conexión: " . $conn->connect_error);
}

// Ajustamos la codificación de caracteres a UTF-8 para soportar español correctamente.
$conn->set_charset("utf8");
?>