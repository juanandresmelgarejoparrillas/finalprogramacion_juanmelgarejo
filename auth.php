<?php
// auth.php - Control de Acceso y Seguridad
// Este archivo contiene pequeñas herramientas (funciones) para saber si alguien tiene permiso para ver las páginas.
// Se usa en casi todas las páginas privadas del sistema.

// Paso 1: Iniciar el sistema de "Sesiones"
// Las sesiones son como una memoria temporal que recuerda quién eres mientras navegas.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función 1: Verificar que el usuario esté logueado
// Esta función actúa como un guardia de seguridad.
function verificar_autenticacion()
{
    // Preguntamos: "¿Existe un ID de usuario guardado en la memoria?"
    if (!isset($_SESSION['usuario_id'])) {
        // Si la respuesta es NO, significa que nadie ha iniciado sesión.
        // Entonces, lo expulsamos a la página de inicio (el login).
        header("Location: index.php");

        // 'exit' asegura que el código se detenga aquí y no cargue nada más de la página actual.
        exit();
    }
    // Si la respuesta es SÍ, la función termina y deja pasar al usuario.
}

// Función 2: Verificar si es Administrador
// Esta función sirve para preguntar si el usuario actual es el jefe (admin).
function es_admin()
{
    // Revisa dos cosas:
    // 1. Que exista el dato 'rol' en la sesión.
    // 2. Que ese 'rol' sea exactamente igual a 'admin'.
    // Devuelve VERDADERO (Sí) o FALSO (No).
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}
?>