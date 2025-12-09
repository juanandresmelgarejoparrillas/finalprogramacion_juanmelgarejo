<?php
// header.php - Encabezado y Menú Superior
// Este archivo se incluye en todas las páginas para mostrar la barra de navegación y cargar los estilos (CSS).

// Aseguramos que la sesión esté iniciada para poder saber quién es el usuario.
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema ABML Futurista</title>

  <!-- Cargamos Bootstrap (librería de diseño) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Cargamos los íconos (FontAwesome) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <!-- Cargamos nuestros estilos personalizados -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <script>
    // Script Anti-Flash: Aplica el tema guardado ANTES de que se muestre el contenido
    (function () {
      const savedTheme = localStorage.getItem('theme') || 'light';
      document.body.setAttribute('data-theme', savedTheme);
    })();
  </script>

  <?php
  // Solo mostramos el menú si hay un usuario conectado (logueado).
  // Si no está conectado, verá la página vacía o el login, pero sin menú.
  if (isset($_SESSION['usuario_id'])):
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
      <div class="container">
        <!-- Logo y Nombre del Usuario -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
          <i class="fas fa-rocket"></i>
          <span class="fw-bold">INNOVAPLAY</span>
          <div class="vr mx-2 bg-white opacity-50"></div>
          <div style="font-size: 0.9rem; font-weight: normal;">
            <i class="fas fa-user-circle"></i> <?php echo ucfirst($_SESSION['usuario']); ?>
            <span class="badge bg-light text-dark ms-1"><?php echo ucfirst($_SESSION['rol']); ?></span>
          </div>
        </a>

        <!-- Botón para ver menú en celulares (Hamburguesa) -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto align-items-center">
            <!-- BOTÓN TEMA (OSCURO/CLARO) -->
            <li class="nav-item me-2">
              <button class="btn btn-sm btn-outline-light rounded-circle" onclick="toggleTheme()" title="Cambiar Tema">
                <i id="theme-icon" class="fas fa-moon"></i>
              </button>
            </li>

            <!-- BOTONES DEL MENÚ -->

            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">Inicio</a>
            </li>

            <!-- Botón 'Usuarios': Solo visible para ADMINISTRADORES -->
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link" href="usuarios.php">Usuarios</a>
              </li>
            <?php endif; ?>

            <!-- Botones visibles para todos -->
            <li class="nav-item">
              <a class="nav-link" href="clientes.php">Clientes</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="proveedores.php">Proveedores</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="egresos.php">Egresos</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="transacciones.php">Transacciones</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="balance.php">Balances</a>
            </li>

            <!-- Botón SALIR (rojo) -->
            <li class="nav-item">
              <a class="nav-link text-danger" href="logout.php">Salir <i class="fas fa-sign-out-alt"></i></a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  <?php endif; ?>

  <!-- Aquí empieza el contenido principal de la página (el 'cuerpo' visible) -->
  <div class="container">