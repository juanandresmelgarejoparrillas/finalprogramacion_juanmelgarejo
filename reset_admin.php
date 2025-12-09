<?php
// reset_admin.php
require_once 'db.php';

$pass = 'admin123';
$hash = password_hash($pass, PASSWORD_DEFAULT);
$email = 'admin@sistema.com';

// Actualizar o Insertar si no existe
$sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // Actualizar
    $sql_update = "UPDATE usuarios SET password = ?, status = 1, rol = 'admin' WHERE email = ?";
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("ss", $hash, $email);
    if ($stmt_up->execute()) {
        echo "<h1>Contraseña y Rol actualizados correctamente.</h1>";
        echo "<p>Usuario: $email</p>";
        echo "<p>Password: $pass</p>";
        echo "<p>Rol: admin</p>";

        // Forzar logout para que tome los cambios
        session_start();
        session_destroy();

        echo "<a href='index.php'>Ir al Login (Sesión cerrada)</a>";
    } else {
        echo "Error al actualizar: " . $conn->error;
    }
} else {
    // Insertar
    $nombre = 'Administrador';
    $rol = 'admin';
    $status = 1;
    $sql_ins = "INSERT INTO usuarios (nombre, email, password, rol, status) VALUES (?, ?, ?, ?, ?)";
    $stmt_ins = $conn->prepare($sql_ins);
    $stmt_ins->bind_param("ssssi", $nombre, $email, $hash, $rol, $status);
    if ($stmt_ins->execute()) {
        echo "<h1>Usuario Admin creado correctamente.</h1>";
        echo "<p>Usuario: $email</p>";
        echo "<p>Password: $pass</p>";

        session_start();
        session_destroy();

        echo "<a href='index.php'>Ir al Login</a>";
    } else {
        echo "Error al crear: " . $conn->error;
    }
}
?>