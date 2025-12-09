<?php
// usuarios.php - Gestión de Usuarios del Sistema (Solo Jefes/Admins)
// Desde aquí se crean los usuarios que pueden entrar al sistema (vendedores, jefes, etc).
// IMPORTANTE: Solo los usuarios con rol de 'admin' pueden entrar a esta página.

require_once 'config.php'; // Conexión a la BD
require_once 'auth.php';   // Seguridad
verificar_autenticacion(); // Confirmar que esté logueado

// Paso de Seguridad Adicional: Checkeo de Admin
// Preguntamos: "¿Eres admin?". Si no lo es, lo sacamos de aquí.
if (!es_admin()) {
    header("Location: dashboard.php"); // Fuera, a la página de inicio.
    exit; // Detener ejecución.
}

require_once 'header.php'; // Cargar menú visual.

// Variables para mensajes
$mensaje = "";
$usuario_editar = null;

// --- LÓGICA DE CONTROL (Formularios) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['accion'])) {

        // CASO 1: CREAR NUEVO USUARIO
        if ($_POST['accion'] == 'crear') {
            $usuario = $_POST['usuario'];
            // IMPORTANTE: Seguridad de Claves
            // 'password_hash' convierte la contraseña normal (ej: "1234") en un código secreto ilegible.
            // Nunca guardamos las contraseñas reales en la base de datos por seguridad.
            $clave = password_hash($_POST['clave'], PASSWORD_DEFAULT);
            $rol = $_POST['rol']; // 'admin' o 'normal'

            $sql = "INSERT INTO usuarios (usuario, clave, rol, estado) VALUES (?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sss", $usuario, $clave, $rol);

            if ($stmt->execute()) {
                $mensaje = "Usuario creado con éxito.";
            } else {
                $mensaje = "Error al crear usuario.";
            }

            // CASO 2: EDITAR USUARIO EXISTENTE
        } elseif ($_POST['accion'] == 'editar') {
            $id = $_POST['id'];
            $usuario = $_POST['usuario'];
            $rol = $_POST['rol'];

            // ¿El usuario quiso cambiar la clave? (Si el campo de contraseña NO está vacío)
            if (!empty($_POST['clave'])) {
                // Si escribió una nueva clave, la encriptamos y actualizamos todo (nombre, calve y rol).
                $clave = password_hash($_POST['clave'], PASSWORD_DEFAULT);
                $sql = "UPDATE usuarios SET usuario=?, clave=?, rol=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sssi", $usuario, $clave, $rol, $id);
            } else {
                // Si NO escribió clave (la dejó vacía), solo actualizamos nombre y rol. 
                // Mantenemos su contraseña vieja tal cual estaba.
                $sql = "UPDATE usuarios SET usuario=?, rol=? WHERE id=?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssi", $usuario, $rol, $id);
            }

            if ($stmt->execute()) {
                $mensaje = "Usuario actualizado.";
            } else {
                $mensaje = "Error al actualizar.";
            }

            // CASO 3: BORRAR USUARIO
        } elseif ($_POST['accion'] == 'borrar') {
            $id = $_POST['id'];
            // Borrado Lógico (Papelera):
            // No lo eliminamos físicamente de la base de datos. Solo le ponemos estado = 0.
            // Así queda desactivado y no puede entrar más, pero conservamos sus datos históricos.
            $sql = "UPDATE usuarios SET estado=0 WHERE id=?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $mensaje = "Usuario eliminado (Desactivado).";
            }
        }
    }
}

// --- LISTAR USUARIOS ACTIVOS ---
// Traemos de la base de datos todos los usuarios con estado = 1.
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