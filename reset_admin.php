<?php
// reset_admin.php - Recuperación de Acceso
// ¡URGENCIA! Este archivo sirve para RESTAURAR el usuario 'admin' si olvidaste la contraseña
// o si borraste al usuario por error.
// ALERTA: Cualquiera que abra este archivo puede resetear el admin. Debería borrarse después de usar.

require_once 'db.php'; // Usa 'db.php' (Ojo, verificar si el proyecto usa config.php o db.php)

// DATOS POR DEFECTO PARA EL RESCATE
$pass = 'admin123';                         // Nueva contraseña
$hash = password_hash($pass, PASSWORD_DEFAULT); // La encriptamos para guardarla segura
$email = 'admin@sistema.com';               // El email del "súper usuario"

// Paso 1: Verificamos si el usuario ya existe por su email.
$sql = "SELECT id FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // CASO A: El usuario EXISTE. Lo actualizamos (Update).
    // Le ponemos la nueva contraseña, lo activamos (status 1) y nos aseguramos que sea admin.
    $sql_update = "UPDATE usuarios SET password = ?, status = 1, rol = 'admin' WHERE email = ?";
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("ss", $hash, $email);

    if ($stmt_up->execute()) {
        echo "<h1>Contraseña y Rol actualizados correctamente.</h1>";
        echo "<p>Usuario: $email</p>";
        echo "<p>Password: $pass (¡Anótela!)</p>";
        echo "<p>Rol: admin</p>";

        // Cerramos cualquier sesión vieja para evitar conflictos.
        session_start();
        session_destroy();

        echo "<a href='index.php'>Ir al Login (Sesión cerrada)</a>";
    } else {
        echo "Error al actualizar: " . $conn->error;
    }
} else {
    // CASO B: El usuario NO EXISTE. Lo creamos de cero (Insert).
    $nombre = 'Administrador';
    $rol = 'admin';
    $status = 1; // Activo

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