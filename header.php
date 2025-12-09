<?php
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

  <?php if (isset($_SESSION['usuario_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
      <div class="container">
        <a class="navbar-brand" href="dashboard.php">
          <i class="fas fa-rocket"></i> INNOVAPLAY
          <div style="font-size: 1.1rem; color: var(--neon-cyan); text-shadow: none;">
            <i class="fas fa-user-astronaut"></i> <?php echo ucfirst($_SESSION['usuario']); ?>
            (<?php echo ucfirst($_SESSION['rol']); ?>)
          </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <!-- Menú de Navegación -->
            <li class="nav-item">
              <a class="nav-link" href="dashboard.php">Inicio</a>
            </li>

            <!-- Opción visible SOLO para ADMINISTRADORES -->
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin'): ?>
              <li class="nav-item">
                <a class="nav-link" href="usuarios.php">Usuarios</a>
              </li>
            <?php endif; ?>

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
            <li class="nav-item">
              <a class="nav-link text-danger" href="logout.php">Salir <i class="fas fa-sign-out-alt"></i></a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  <?php endif; ?>

  <div class="container">