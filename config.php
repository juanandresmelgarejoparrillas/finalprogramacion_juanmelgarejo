<?php
// config.php - Configuración principal de la base de datos
// Este archivo se encarga de conectar PHP con MySQL.

$servidor = "localhost";    // El servidor donde está la BD (usualmente localhost en desarrollo)
$usuario_db = "root";       // Usuario de MySQL
$clave_db = "";             // Contraseña de MySQL (vacía en XAMPP por defecto)
$nombre_db = "abml_futurista"; // El nombre de nuestra base de datos

// Paso 1: Intentar conectar
$conexion = new mysqli($servidor, $usuario_db, $clave_db, $nombre_db);

// Paso 2: Verificar si hubo error
if ($conexion->connect_error) {
    // Si falla, detenemos todo y mostramos el error
    die("Error de conexión: " . $conexion->connect_error);
}

// Paso 3: Configurar caracteres especiales (tildes, ñ)
$conexion->set_charset("utf8");
?>