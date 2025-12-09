<?php
// clientes.php - Gestión de Clientes
// Todos pueden ver, pero SOLO ADMIN puede crear, editar o borrar.

require_once 'config.php';
require_once 'auth.php';
verificar_autenticacion();
require_once 'header.php';

$mensaje = "";

// --- LÓGICA ABML (Alta, Baja, Modificación) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Paso 1: Verificar permisos
    // Si NO es admin, le mostramos error y no hacemos nada.
    if (es_admin()) {
        if (isset($_POST['accion'])) {

            // ACCIÓN 1: CREAR CLIENTE
            if ($_POST['accion'] == 'crear') {
                $nombre = $_POST['nombre'];
                $email = $_POST['email'];
                $telefono = $_POST['telefono'];

                $sql = "INSERT INTO clientes (nombre, correo, telefono, estado) VALUES (?, ?, ?, 1)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sss", $nombre, $email, $telefono);
                $stmt->execute();

                // ACCIÓN 2: EDITAR CLIENTE
            } elseif ($_POST['accion'] == 'editar') {
                $id = $_POST['id'];
                $nombre = $_POST['nombre'];
                $email = $_POST['email'];
                $telefono = $_POST['telefono'];

                $sql = "UPDATE clientes SET nombre=?, correo=?, telefono=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssi", $nombre, $email, $telefono, $id);
                $stmt->execute();

                // ACCIÓN 3: BORRAR CLIENTE
            } elseif ($_POST['accion'] == 'borrar') {
                $id = $_POST['id'];
                // Soft Delete (Estado = 0)
                $sql = "UPDATE clientes SET estado=0 WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
    } else {
        $mensaje = "No tienes permisos para realizar esta acción.";
    }
}


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