<?php
// clientes.php - Gestión de Clientes (Personas que nos compran)
// En esta página podemos ver la lista de clientes.
// Además, los Administradores pueden crear nuevos, corregir datos o borrarlos.

require_once 'config.php'; // Conexión a la base de datos.
require_once 'auth.php';   // Seguridad de sesión.
verificar_autenticacion(); // Verificamos que esté logueado.
require_once 'header.php'; // Parte visual superior.

$mensaje = ""; // Variable para avisar si salió todo bien o mal.

// --- LÓGICA DE CONTROL (Alta, Baja, Modificación) ---
// Aquí entramos solo si el usuario apretó algún botón de guardar o borrar (envió el formulario).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Paso 1: Verificar permisos de Administrador
    // Solo el jefe (admin) puede hacer cambios. Un usuario normal solo puede mirar.
    if (es_admin()) {
        if (isset($_POST['accion'])) {

            // CASO 1: CREAR UN NUEVO CLIENTE
            if ($_POST['accion'] == 'crear') {
                $nombre = $_POST['nombre'];
                $email = $_POST['email'];
                $telefono = $_POST['telefono'];

                // Insertamos los datos en la tabla 'clientes'.
                // 'estado = 1' significa que está Activo.
                $sql = "INSERT INTO clientes (nombre, correo, telefono, estado) VALUES (?, ?, ?, 1)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sss", $nombre, $email, $telefono);
                $stmt->execute(); // ¡Guardado!

                // CASO 2: CORREGIR/EDITAR UN CLIENTE EXISTENTE
            } elseif ($_POST['accion'] == 'editar') {
                $id = $_POST['id']; // Necesitamos saber el ID para saber a CUÁL corregir.
                $nombre = $_POST['nombre'];
                $email = $_POST['email'];
                $telefono = $_POST['telefono'];

                // Actualizamos los datos donde coincida el ID.
                $sql = "UPDATE clientes SET nombre=?, correo=?, telefono=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssi", $nombre, $email, $telefono, $id);
                $stmt->execute(); // ¡Actualizado!

                // CASO 3: BORRAR UN CLIENTE
            } elseif ($_POST['accion'] == 'borrar') {
                $id = $_POST['id'];
                // TRUCO: No borramos de verdad la fila (DELETE).
                // Solo le cambiamos el estado a 0 (Inactivo) para "ocultarlo".
                // Esto permite no perder el historial de ventas de este cliente.
                $sql = "UPDATE clientes SET estado=0 WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
    } else {
        // Si no es admin y trata de guardar, le mostramos este error.
        $mensaje = "No tienes permisos para realizar esta acción.";
    }
}


// --- LECTURA DE DATOS ---
// Buscamos todos los clientes que estén activos (estado = 1) para mostrarlos en la lista.
$sql = "SELECT * FROM clientes WHERE estado = 1";
$resultado = $conexion->query($sql);
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Clientes</h2>
        <?php if ($mensaje): ?>
            <div class="alert alert-warning"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if (es_admin()): ?>
            <div class="card mb-4">
                <div class="card-header">Nuevo / Editar Cliente</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="crear" id="accion">
                        <input type="hidden" name="id" id="id_cliente">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Nombre"
                                    required>
                            </div>
                            <div class="col-md-3">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="telefono" id="telefono" class="form-control"
                                    placeholder="Teléfono">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">Guardar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Listado</h5>
                    <input type="text" id="globalSearch" class="form-control form-control-sm w-25"
                        placeholder="Buscar cliente...">
                </div>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <?php if (es_admin()): ?>
                                <th>Acciones</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $fila['id']; ?></td>
                                <td><?php echo $fila['nombre']; ?></td>
                                <td><?php echo $fila['correo']; ?></td>
                                <td><?php echo $fila['telefono']; ?></td>
                                <?php if (es_admin()): ?>
                                    <td>
                                        <button class="btn btn-sm btn-primary"
                                            onclick="editar(<?php echo $fila['id']; ?>, '<?php echo $fila['nombre']; ?>', '<?php echo $fila['correo']; ?>', '<?php echo $fila['telefono']; ?>')">Editar</button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="accion" value="borrar">
                                            <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                onclick="return confirm('¿Seguro?')">Borrar</button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function editar(id, nombre, email, telefono) {
        document.getElementById('accion').value = 'editar';
        document.getElementById('id_cliente').value = id;
        document.getElementById('nombre').value = nombre;
        document.getElementById('email').value = email;
        document.getElementById('telefono').value = telefono;
    }
</script>

<?php require_once 'footer.php'; ?>