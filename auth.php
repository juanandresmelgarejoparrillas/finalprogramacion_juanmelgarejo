<?php
// auth.php - Funciones para manejar la sesión (Login/Logout)

// Paso 1: Iniciar el manejo de sesiones si no está iniciado ya
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función 1: Verificar que el usuario esté logueado
function verificar_autenticacion()
{
    // Si no existe la variable 'usuario_id' en la sesión, no está logueado
    if (!isset($_SESSION['usuario_id'])) {
        // Lo mandamos al login (index.php)
        header("Location: index.php");
        exit(); // Detenemos la ejecución del script aquí
    }
}

// Función 2: Verificar si es Administrador
function es_admin()
{
    // Retorna VERDADERO (true) si el rol es 'admin', FALSO (false) si no
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
?>