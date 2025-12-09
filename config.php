<?php
// config.php - Configuración principal de la base de datos
// Este archivo es el puente entre nuestra aplicación y la base de datos (donde se guarda la información).
// Aquí definimos las credenciales para poder entrar y guardar datos.

$servidor = "localhost";    // El nombre del servidor. En tu computadora personal suele ser "localhost".
$usuario_db = "root";       // El nombre de usuario dueño de la base de datos.
$clave_db = "";             // La contraseña del usuario. En programas como XAMPP suele venir vacía por defecto.
$nombre_db = "abml_futurista"; // El nombre exacto de la base de datos que vamos a usar.

// Paso 1: Intentar crear la conexión
// Aquí la aplicación "llama" a la base de datos con los datos de arriba.
$conexion = new mysqli($servidor, $usuario_db, $clave_db, $nombre_db);

// Paso 2: Verificar si hubo un error al conectar
// Si la conexión falla (por ejemplo, contraseña mal escrita), la variable 'connect_error' tendrá información.
if ($conexion->connect_error) {
    // Si hubo error, detenemos la ejecución del programa (die) y mostramos qué pasó.
    die("Error de conexión: " . $conexion->connect_error);
}

// Paso 3: Configurar el idioma de los datos
// Esto es importante para que se vean bien las tildes, la letra eñe y otros caracteres especiales del español.
$conexion->set_charset("utf8");
?>