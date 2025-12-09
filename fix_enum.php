<?php
require_once 'config.php';

// Agregar 'gasto' al ENUM
$sql = "ALTER TABLE transacciones MODIFY COLUMN tipo ENUM('factura','recibo','nota_credito','nota_debito','gasto') NOT NULL";

if ($conexion->query($sql) === TRUE) {
    echo "Columna 'tipo' actualizada. Ahora permite 'gasto'.";
} else {
    echo "Error al modificar columna: " . $conexion->error;
}
?>