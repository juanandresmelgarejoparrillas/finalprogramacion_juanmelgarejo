<?php
// check_schema.php - Herramienta de Diagnóstico
// Este archivo sirve para que el programador revise cómo está creada la tabla 'transacciones' en la base de datos.
// No es para uso del usuario final.

require_once 'config.php'; // Conexión a la base de datos.

echo "<h2>Estructura de TRANSACCIONES</h2>";
echo "<p>Mostrando las columnas (campos) que tiene la tabla en la base de datos.</p>";

// Comando SQL 'DESCRIBE' nos dice qué columnas tiene una tabla.
$res = $conexion->query("DESCRIBE transacciones");

// Dibujamos una tabla simple HTML con los resultados.
echo "<table border='1'><tr><th>Campo (Field)</th><th>Tipo de Dato (Type)</th><th>Permite Nulo (Null)</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Contenido Reciente</h2>";
echo "<p>Mostrando las últimas 5 operaciones guardadas para verificar que se graban bien.</p>";

// Traemos los últimos 5 registros para ver qué datos tienen.
$res2 = $conexion->query("SELECT * FROM transacciones ORDER BY id DESC LIMIT 5");
while ($row = $res2->fetch_assoc()) {
    // 'print_r' muestra toda la información bruta de la variable.
    print_r($row);
    echo "<br>";
}
?>