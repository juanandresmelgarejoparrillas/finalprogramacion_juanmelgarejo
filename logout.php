<?php
// logout.php - Cerrar Sesión
// Este archivo se encarga de sacar al usuario del sistema de forma segura.

// 1. Recuperamos la sesión actual para saber quién estaba conectado.
session_start();

// 2. Destruimos toda la información de la sesión.
// Esto borra la memoria temporal: el sistema "olvida" al usuario.
session_destroy();

// 3. Redirigimos al usuario a la página de inicio (Login).
header("Location: index.php");

// 4. Terminamos el script para asegurar que no se ejecute nada más.
exit;
?>