<?php
// index.php - Pantalla de Login (Acceso)
// Este archivo procesa el ingreso de los usuarios al sistema.

require_once 'config.php'; // Incluimos la conexión a la base de datos
session_start();           // Iniciamos la sesión para poder guardar datos del usuario

// 1. Si el usuario ya está logueado, lo mandamos directo al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = ""; // Variable para guardar mensajes de error si falla el login

// 2. ¿Se envió el formulario? (Se usó POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['usuario']; // Capturamos lo que escribió en "usuario"
    $clave = $_POST['clave'];     // Capturamos lo que escribió en "contraseña"

    // 3. Preparamos la consulta SQL para buscar al usuario
    // Usamos '?' para evitar que nos hackeen (SQL Injection)
    $sql = "SELECT id, usuario, clave, rol FROM usuarios WHERE usuario = ? AND estado = 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    // 4. Verificamos si encontramos al usuario
    if ($resultado->num_rows === 1) {
        $fila = $resultado->fetch_assoc();

        // 5. Verificamos si la contraseña coincide (está encriptada/hash)
        if (password_verify($clave, $fila['clave'])) {
            // ¡Login Exitoso! Guardamos datos en la sesión
            $_SESSION['usuario_id'] = $fila['id'];
            $_SESSION['usuario'] = $fila['usuario'];
            $_SESSION['rol'] = $fila['rol']; // 'admin' o 'usuario'

            // Lo mandamos al panel principal
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado o inactivo.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login - Sistema Futuro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-page">
    <div class="card login-card">
        <h2 class="text-center mb-4">ACCESO</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="clave" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">INGRESAR</button>
        </form>
    </div>
</body>

</html>