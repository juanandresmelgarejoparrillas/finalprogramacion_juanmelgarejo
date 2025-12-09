<?php
require_once 'config.php';

echo "<h2>Columna TIPO en TRANSACCIONES</h2>";
$res = $conexion->query("SHOW COLUMNS FROM transacciones LIKE 'tipo'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
?>