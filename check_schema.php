<?php
require_once 'config.php';

echo "<h2>Estructura de TRANSACCIONES</h2>";
$res = $conexion->query("DESCRIBE transacciones");
echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Contenido Reciente</h2>";
$res2 = $conexion->query("SELECT * FROM transacciones ORDER BY id DESC LIMIT 5");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
    echo "<br>";
}
?>