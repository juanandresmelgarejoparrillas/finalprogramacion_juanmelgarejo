<?php
// setup.php - Script para inicializar la base de datos
$servidor = "localhost";
$usuario_db = "root";
$clave_db = "";

// 1. Conectar a MySQL sin seleccionar base de datos
$conexion = new mysqli($servidor, $usuario_db, $clave_db);

if ($conexion->connect_error) {
    die("Error de conexión a MySQL: " . $conexion->connect_error);
}

// 2. Crear Base de Datos
$sql = "CREATE DATABASE IF NOT EXISTS abml_futurista CHARACTER SET utf8 COLLATE utf8_general_ci";
if ($conexion->query($sql) === TRUE) {
    echo "Base de datos 'abml_futurista' creada o ya existente.<br>";
} else {
    die("Error al crear base de datos: " . $conexion->error);
}

// 3. Seleccionar la Base de Datos
$conexion->select_db("abml_futurista");

// 4. Crear Tablas
$queries = [
    "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(50) NOT NULL UNIQUE,
        clave VARCHAR(255) NOT NULL,
        rol ENUM('admin', 'normal') DEFAULT 'normal',
        estado TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS clientes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        correo VARCHAR(100),
        telefono VARCHAR(20),
        estado TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS proveedores (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        correo VARCHAR(100),
        telefono VARCHAR(20),
        estado TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS transacciones (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tipo ENUM('factura', 'recibo', 'nota_credito', 'nota_debito') NOT NULL,
        tipo_entidad ENUM('cliente', 'proveedor') NOT NULL,
        entidad_id INT NOT NULL,
        monto DECIMAL(10, 2) NOT NULL,
        fecha DATE NOT NULL,
        transaccion_relacionada_id INT DEFAULT NULL,
        estado TINYINT(1) DEFAULT 1,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (transaccion_relacionada_id) REFERENCES transacciones(id)
    )"
];

foreach ($queries as $query) {
    if ($conexion->query($query) === TRUE) {
        echo "Tabla verificada/creada.<br>";
    } else {
        echo "Error creando tabla: " . $conexion->error . "<br>";
    }
}

// 5. Insertar Usuarios por defecto si no existen
$check_admin = $conexion->query("SELECT * FROM usuarios WHERE usuario = 'admin'");
if ($check_admin->num_rows == 0) {
    // Clave: admin
    $pass_admin = password_hash('admin', PASSWORD_DEFAULT);
    $conexion->query("INSERT INTO usuarios (usuario, clave, rol, estado) VALUES ('admin', '$pass_admin', 'admin', 1)");
    echo "Usuario 'admin' creado (clave: admin).<br>";
}

$check_user = $conexion->query("SELECT * FROM usuarios WHERE usuario = 'user'");
if ($check_user->num_rows == 0) {
    // Clave: user
    $pass_user = password_hash('user', PASSWORD_DEFAULT);
    $conexion->query("INSERT INTO usuarios (usuario, clave, rol, estado) VALUES ('user', '$pass_user', 'normal', 1)");
    echo "Usuario 'user' creado (clave: user).<br>";
}

echo "<hr><h3>Instalación Completada. <a href='index.php'>Ir al Login</a></h3>";
?>