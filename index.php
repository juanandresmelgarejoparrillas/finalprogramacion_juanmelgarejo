<?php
// index.php - Página de Inicio de Sesión (Login)
// Esta es la primera pantalla que ven los usuarios. Aquí ponen su usuario y contraseña para entrar.

require_once 'config.php'; // Traemos las credenciales para conectar a la base de datos.
session_start();           // Iniciamos la memoria temporal (sesión).

// Paso 1: Revisar si ya había entrado antes
// Si el sistema recuerda al usuario (existe 'usuario_id'), no lo hacemos loguear de nuevo.
if (isset($_SESSION['usuario_id'])) {
    // Lo enviamos directo al panel principal.
    header("Location: dashboard.php");
    exit; // Detenemos este archivo aquí.
}

$error = ""; // Preparamos una variable vacía para mostrar errores (como contraseña mal escrita).

// Paso 2: Detectar si el usuario presionó el botón "Ingresar"
// Cuando se llena el formulario, los datos viajan ocultos (método POST).
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibimos lo que escribió en las cajas de texto.
    $usuario = $_POST['usuario'];
    $clave = $_POST['clave'];

    // Paso 3: Buscar al usuario en la base de datos
    // Preparamos una pregunta segura (SQL) para la base de datos.
    // Buscamos alguien con ese nombre de usuario y que esté activo (estado = 1).
    $sql = "SELECT id, usuario, clave, rol FROM usuarios WHERE usuario = ? AND estado = 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario); // Rellenamos el '?' con el dato real de forma segura.
    $stmt->execute();                 // Hacemos la búsqueda.
    $resultado = $stmt->get_result(); // Tomamos la respuesta.

    // Paso 4: Verificar si encontramos a alguien
    if ($resultado->num_rows === 1) {
        // Si encontramos 1 usuario, leemos sus datos.
        $fila = $resultado->fetch_assoc();

        // Paso 5: Verificar la contraseña
        // Comparamos la contraseña escrita con la clave secreta guardada (está encriptada).
        if (password_verify($clave, $fila['clave'])) {
            // ¡Contraseña correcta!

            // Guardamos los datos en la sesión para recordarlo en otras páginas.
            $_SESSION['usuario_id'] = $fila['id'];
            $_SESSION['usuario'] = $fila['usuario'];
            $_SESSION['rol'] = $fila['rol']; // Guardamos si es admin o usuario normal.

            // Lo llevamos al Dashboard.
            header("Location: dashboard.php");
            exit;
        } else {
            // Contraseña incorrecta.
            $error = "Contraseña incorrecta.";
        }
    } else {
        // No existe el usuario o está desactivado.
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