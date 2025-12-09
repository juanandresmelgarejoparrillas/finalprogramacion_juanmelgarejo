<?php
// update_db.php - Actualizador de Base de Datos
// Este script sirve para agregar mejoras a la base de datos sin borrar lo que ya existe.
// Si agregamos funciones nuevas (como número de comprobante), corremos esto para que la BD se entere.

require_once 'config.php';

echo "<h3>Actualizando Base de Datos...</h3>";

// Lista de comandos 'ALTER TABLE' para agregar las columnas nuevas que necesitemos.
// 'ADD COLUMN IF NOT EXISTS' intenta evitar errores si ya existen, aunque en MySQL viejos puede fallar el 'IF NOT EXISTS'.
$updates = [
    // Agregamos campo para el número impreso de la factura (ej: 0001-000020)
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS numero_comprobante VARCHAR(50) DEFAULT NULL",
    // Agregamos la letra de la factura (A, B, C)
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS letra CHAR(1) DEFAULT NULL",
    // Agregamos la condición de pago (Contado o Cta Cte)
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS condicion_pago ENUM('contado', 'cuenta_corriente') DEFAULT NULL",
    // Agregamos el saldo pendiente (cuánto falta pagar de esa factura)
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS saldo_pendiente DECIMAL(10, 2) DEFAULT 0"
];

foreach ($updates as $sql) {
    // Intentamos ejecutar cada actualización.
    if ($conexion->query($sql) === TRUE) {
        echo "Ejecutado con éxito: $sql <br>";
    } else {
        // Si falla, mostramos el mensaje (puede ser que ya exista la columna, lo cual no es grave).
        echo "Información del sistema: " . $conexion->error . "<br>";
    }
}

echo "<h3>Actualización Completada. <a href='transacciones.php'>Volver a Transacciones</a></h3>";
?>