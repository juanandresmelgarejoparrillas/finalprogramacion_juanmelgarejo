<?php
// login_action.php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Buscar usuario por email y que esté activo (status = 1)
    $stmt = $conn->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? AND status = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verificar password
        if (password_verify($password, $user['password'])) {
            // Login exitoso
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];

            header("Location: dashboard.php");
            exit;
        }
    }

    // Login fallido
    header("Location: index.php?error=1");
    exit;
}
?>