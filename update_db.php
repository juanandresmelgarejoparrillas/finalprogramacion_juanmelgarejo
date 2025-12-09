<?php
// update_db.php - Script para actualizar la estructura de la base de datos
require_once 'config.php';

echo "<h3>Actualizando Base de Datos...</h3>";

// Array de sentencias ALTER para agregar columnas si no existen
$updates = [
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS numero_comprobante VARCHAR(50) DEFAULT NULL",
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS letra CHAR(1) DEFAULT NULL",
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS condicion_pago ENUM('contado', 'cuenta_corriente') DEFAULT NULL",
    "ALTER TABLE transacciones ADD COLUMN IF NOT EXISTS saldo_pendiente DECIMAL(10, 2) DEFAULT 0"
];

foreach ($updates as $sql) {
    if ($conexion->query($sql) === TRUE) {
        echo "Ejecutado: $sql <br>";
    } else {
        // Ignorar error si es porque ya existe (aunque IF NOT EXISTS debería manejarlo en versiones recientes de MariaDB/MySQL)
        echo "Info/Error: " . $conexion->error . "<br>";
    }
}

echo "<h3>Actualización Completada. <a href='transacciones.php'>Volver a Transacciones</a></h3>";
?>