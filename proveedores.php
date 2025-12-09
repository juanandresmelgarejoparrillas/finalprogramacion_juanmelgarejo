<?php
// proveedores.php - Gestión de Proveedores (A quienes les compramos)
// Funciona exactamente igual que la página de Clientes.
// Permite ver la lista, y solo el Admin puede guardar o borrar proveedores.

require_once 'config.php'; // Conexión a la BD.
require_once 'auth.php';   // Seguridad.
verificar_autenticacion(); // Verificar login.
require_once 'header.php'; // Diseño visual (menú).

$mensaje = ""; // Para mensajes de éxito o error.

// --- LÓGICA DE CONTROL ---
// Si se recibió datos por formulario (Botón Guardar/Borrar)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Paso 1: Solo el Admin tiene permiso
    if (es_admin()) {
        if (isset($_POST['accion'])) {

            // CASO 1: CREAR NUEVO PROVEEDOR
            if ($_POST['accion'] == 'crear') {
                $nombre = $_POST['nombre'];
                $email = $_POST['email'];
                $telefono = $_POST['telefono'];

                // Insertamos en la tabla 'proveedores'. Estado 1 = Activo.
                $sql = "INSERT INTO proveedores (nombre, correo, telefono, estado) VALUES (?, ?, ?, 1)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sss", $nombre, $email, $telefono);
                $stmt->execute(); // Ejecutar guardado.

                // CASO 2: EDITAR PROVEEDOR EXISTENTE
            } elseif ($_POST['accion'] == 'editar') {
                $id = $_POST['id'];
                $nombre = $_POST['nombre'];
                $email = $_POST['email'];
                $telefono = $_POST['telefono'];

                // Actualizamos los datos del ID seleccionado.
                $sql = "UPDATE proveedores SET nombre=?, correo=?, telefono=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssi", $nombre, $email, $telefono, $id);
                $stmt->execute(); // Ejecutar actualización.

                // CASO 3: BORRAR (OCULTAR) PROVEEDOR
            } elseif ($_POST['accion'] == 'borrar') {
                $id = $_POST['id'];
                // Borrado Lógico: Cambiamos estado a 0 par que no aparezca en las listas,
                // pero no lo borramos definitivamente para no perder registro de compras pasadas.
                $sql = "UPDATE proveedores SET estado=0 WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
            }
        }
    } else {
        $mensaje = "No tienes permisos.";
    }
}


// --- LISTAR PROVEEDORES ---
// Buscamos solo los que tienen estado = 1 (Activos).
$sql = "SELECT * FROM proveedores WHERE estado = 1";
$resultado = $conexion->query($sql);
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Proveedores</h2>
        <?php if ($mensaje): ?>
            <div class="alert alert-warning"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if (es_admin()): ?>
            <div class="card mb-4">
                <div class="card-header">Nuevo / Editar Proveedor</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="accion" value="crear" id="accion">
                        <input type="hidden" name="id" id="id_proveedor">
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
                        placeholder="Buscar proveedor...">
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
        document.getElementById('id_proveedor').value = id;
        document.getElementById('nombre').value = nombre;
        document.getElementById('email').value = email;
        document.getElementById('telefono').value = telefono;
    }
</script>

<?php require_once 'footer.php'; ?>