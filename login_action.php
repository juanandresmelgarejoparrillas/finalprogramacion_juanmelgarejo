<?php
// login_action.php - Procesador de Login (Alternativo/Específico)
// Este archivo recibe los datos de un formulario de login y verifica si son correctos.
// NOTA: Usa 'email' y 'db.php', a diferencia de 'index.php' que usa 'usuario' y 'config.php'.

// 1. Iniciamos sesión para poder guardar los datos del usuario si se loguea bien.
session_start();

// 2. Incluimos la conexión a la base de datos (usando db.php).
require_once 'db.php';

// 3. Verificamos si alguien envió datos usando el método POST (el formulario).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 4. Guardamos lo que escribió el usuario en variables.
    $email = $_POST['email'];       // El correo electrónico
    $password = $_POST['password']; // La contraseña

    // 5. Preparamos la consulta para buscar al usuario en la base de datos.
    // Buscamos un usuario que tenga ese email Y que esté activo (status = 1).
    // El signo '?' es por seguridad (evita hackeos SQL injection).
    $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? AND status = 1");
    $stmt->bind_param("s", $email); // Rellenamos el '?' con el email.
    $stmt->execute();               // Ejecutamos la búsqueda.
    $result = $stmt->get_result();  // Obtenemos el resultado.

    // 6. Verificamos si encontramos exactamente un usuario.
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc(); // Convertimos el resultado en un arreglo de datos.

        // 7. Verificamos la contraseña.
        // password_verify compara la contraseña escrita con la encriptada en la base de datos.
        if (password_verify($password, $user['password'])) {
            // ¡Login Exitoso!

            // Guardamos los datos importantes en la sesión del servidor.
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];

            // Redirigimos al usuario al panel principal (Dashboard).
            header("Location: dashboard.php");
            exit; // Terminamos aquí.
        }
    }

    // 8. Si llegamos aquí, es porque no se encontró el usuario O la contraseña estaba mal.
    // Regresamos al login con un código de error (?error=1).
    header("Location: index.php?error=1");
    exit;
}
?>