<?php
// usuarios.php - Gestión de Usuarios (Solo Administradores)
// Permite crear, editar y eliminar (lógicamente) usuarios del sistema.

require_once 'config.php';
require_once 'auth.php';
verificar_autenticacion(); // Paso 1: Verificar login

// Paso 2: Seguridad - Solo el admin puede entrar aquí
if (!es_admin()) {
    header("Location: dashboard.php"); // Si no es admin, lo echamos al inicio
    exit;
}

require_once 'header.php';

// Inicializamos variables
$mensaje = "";
$usuario_editar = null; // (Opcional, si quisiéramos precargar datos en PHP, pero usamos JS)

// --- LÓGICA DE FORMULARIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {

        // ACCIÓN 1: CREAR USUARIO
        if ($_POST['accion'] == 'crear') {
            $usuario = $_POST['usuario'];
            // Hash de contraseña: Convertimos la clave en un código seguro irreversible
            $clave = password_hash($_POST['clave'], PASSWORD_DEFAULT);
            $rol = $_POST['rol'];

            $sql = "INSERT INTO usuarios (usuario, clave, rol, estado) VALUES (?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sss", $usuario, $clave, $rol);

            if ($stmt->execute()) {
                $mensaje = "Usuario creado con éxito.";
            } else {
                $mensaje = "Error al crear usuario.";
            }

            // ACCIÓN 2: EDITAR USUARIO
        } elseif ($_POST['accion'] == 'editar') {
            $id = $_POST['id'];
            $usuario = $_POST['usuario'];
            $rol = $_POST['rol'];

            // ¿El usuario quiso cambiar la clave? (Si el campo no está vacío)
            if (!empty($_POST['clave'])) {
                // Si escribió algo, encriptamos la nueva clave y actualizamos todo
                $clave = password_hash($_POST['clave'], PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET usuario=?, clave=?, rol=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssi", $usuario, $clave, $rol, $id);
            } else {
                // Si lo dejó vacío, actualizamos solo nombre y rol (mantenemos clave vieja)
                $sql = "UPDATE usuarios SET usuario=?, rol=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssi", $usuario, $rol, $id);
            }

            if ($stmt->execute()) {
                $mensaje = "Usuario actualizado.";
            } else {
                $mensaje = "Error al actualizar.";
            }

            // ACCIÓN 3: BORRAR USUARIO (Soft Delete)
        } elseif ($_POST['accion'] == 'borrar') {
            $id = $_POST['id'];
            // No borramos el registro (DELETE), solo lo marcamos como inactivo (estado=0)
            // Esto es 'Soft Delete' para mantener historial.
            $sql = "UPDATE usuarios SET estado=0 WHERE id=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $mensaje = "Usuario eliminado (Soft Delete).";
            }
        }
    }
}

// --- OBTENER LISTA DE USUARIOS ACTIVOS ---
$sql = "SELECT * FROM usuarios WHERE estado = 1";
$resultado = $conexion->query($sql);
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">Gestión de Usuarios</h2>
        <?php if ($mensaje): ?>
            <div class="alert alert-info"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <!-- Formulario Crear/Editar -->
        <div class="card mb-4">
            <div class="card-header">Nuevo / Editar Usuario</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion" value="crear" id="accion">
                    <input type="hidden" name="id" id="id_usuario">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" name="usuario" id="nombre_usuario" class="form-control"
                                placeholder="Nombre de usuario" required>
                        </div>
                        <div class="col-md-4">
                            <input type="password" name="clave" id="clave_usuario" class="form-control"
                                placeholder="Contraseña (dejar vacía si no cambia)">
                        </div>
                        <div class="col-md-2">
                            <select name="rol" id="rol_usuario" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Listado de Usuarios</span>
                <input type="text" id="globalSearch" class="form-control form-control-sm w-25" placeholder="Buscar...">
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($fila = $resultado->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $fila['id']; ?></td>
                                <td><?php echo $fila['usuario']; ?></td>
                                <td><?php echo $fila['rol']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                        onclick="editar(<?php echo $fila['id']; ?>, '<?php echo $fila['usuario']; ?>', '<?php echo $fila['rol']; ?>')">Editar</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="accion" value="borrar">
                                        <input type="hidden" name="id" value="<?php echo $fila['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('¿Seguro?')">Borrar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function editar(id, usuario, rol) {
        document.getElementById('accion').value = 'editar';
        document.getElementById('id_usuario').value = id;
        document.getElementById('nombre_usuario').value = usuario;
        document.getElementById('rol_usuario').value = rol;
        document.getElementById('clave_usuario').placeholder = "Nueva contraseña (opcional)";
    }
</script>

<?php require_once 'footer.php'; ?>