<?php
// Este archivo sirve para revisar cómo está construida la columna 'tipo' en la tabla de transacciones de la base de datos.
// Es útil para asegurarnos de que la base de datos tiene la estructura correcta.

require_once 'config.php'; // Traemos la configuración para conectarnos a la base de datos.

echo "<h2>Columna TIPO en TRANSACCIONES</h2>"; // Mostramos un título en la pantalla.

// Le pedimos a la base de datos que nos muestre los detalles de la columna llamada 'tipo' dentro de la tabla 'transacciones'.
$res = $conexion->query("SHOW COLUMNS FROM transacciones LIKE 'tipo'");

// Mientras haya información que mostrar, vamos fila por fila.
while ($row = $res->fetch_assoc()) {
    // Imprimimos en pantalla toda la información técnica de esa columna (nombre, tipo de dato, etc.) de forma legible.
    print_r($row);
}
?>