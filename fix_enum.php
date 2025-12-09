<?php
// fix_enum.php - Parche de Base de Datos
// Este script soluciona un problema común: cuando la base de datos no sabe qué es un "gasto".
// Agrega la palabra 'gasto' a la lista de tipos permitidos en la tabla de transacciones.

require_once 'config.php';

// SQL para MODIFICAR la columna 'tipo'.
// Le decimos: "A partir de ahora, acepta: factura, recibo, nota_credito, nota_debito Y gasto".
$sql = "ALTER TABLE transacciones MODIFY COLUMN tipo ENUM('factura','recibo','nota_credito','nota_debito','gasto') NOT NULL";

// Ejecutamos la orden.
if ($conexion->query($sql) === TRUE) {
    echo "<h1>¡Éxito!</h1>";
    echo "<p>Columna 'tipo' actualizada. Ahora permite guardar 'gasto'.</p>";
} else {
    echo "<h1>Error</h1>";
    echo "<p>No se pudo modificar la columna: " . $conexion->error . "</p>";
}
?>