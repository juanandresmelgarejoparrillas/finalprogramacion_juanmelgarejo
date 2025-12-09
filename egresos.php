<?php
// egresos.php - Gestión de Egresos (Gastos)
// Aquí registramos todo lo que sea SALIDA de dinero: Facturas de proveedores o gastos varios.

require_once 'config.php';
require_once 'auth.php';
verificar_autenticacion();
require_once 'header.php';

// --- LÓGICA PARA GUARDAR UN NUEVO EGRESO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'nuevo_egreso') {
    $tipo_egreso = $_POST['tipo_egreso']; // Puede ser 'factura_proveedor' o 'gasto_general'
    $fecha = $_POST['fecha'];
    $monto = $_POST['monto'];
    $descripcion = $_POST['descripcion'] ?? '';

    // OPCIÓN A: Es una FACTURA de un PROVEEDOR (Genera Deuda/Saldo Pendiente)
    if ($tipo_egreso == 'factura_proveedor') {
        $entidad_id = $_POST['proveedor_id']; // ¿A quién le debemos?
        $numero = $_POST['numero_factura'];

        $tipo = 'factura';          // En BD es una 'factura'
        $tipo_entidad = 'proveedor';// De un 'proveedor'

        // Al cargar una factura de compra, debemos esa plata -> Aumenta el saldo pendiente
        $saldo_pendiente = $monto;

        $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, numero_comprobante, descripcion, estado, saldo_pendiente) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssidsssd", $tipo, $tipo_entidad, $entidad_id, $monto, $fecha, $numero, $descripcion, $saldo_pendiente);

    } else {
        // OPCIÓN B: Es un GASTO GENERAL (Caja Chica, Taxi, Librería, etc)
        // No necesariamente asociado a un proveedor del sistema.

        $tipo = 'gasto';
        $tipo_entidad = 'proveedor'; // Lo marcamos genéricamente
        $entidad_id = 0;             // ID 0 porque no es un proveedor registrado específico
        $numero = $_POST['referencia']; // Ticket o referencia

        // UN GASTO GENERAL SE PAGA EN EL MOMENTO.
        // Por lo tanto, no genera deuda a futuro. Saldo pendiente = 0.
        $saldo_pendiente = 0;

        $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, numero_comprobante, descripcion, estado, saldo_pendiente) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssidsssd", $tipo, $tipo_entidad, $entidad_id, $monto, $fecha, $numero, $descripcion, $saldo_pendiente);
    }

    if ($stmt->execute()) {
        $mensaje = "Egreso registrado correctamente.";
    } else {
        $error = "Error al registrar egreso: " . $conexion->error;
    }
}

// Obtener Proveedores para el select
$proveedores = $conexion->query("SELECT * FROM proveedores WHERE estado = 1 ORDER BY nombre ASC");

// Obtener Listado de Egresos (Facturas Proveedor + Gastos)
$sql_list = "SELECT t.*, p.nombre as nombre_proveedor 
             FROM transacciones t 
             LEFT JOIN proveedores p ON t.entidad_id = p.id 
             WHERE (t.tipo_entidad = 'proveedor' AND t.tipo IN ('factura', 'gasto')) 
             AND t.estado = 1 
             ORDER BY t.fecha DESC, t.id DESC";
$res_list = $conexion->query($sql_list);
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 style="color: var(--neon-purple); text-shadow: 0 0 5px rgba(188, 19, 254, 0.5);">GESTIÓN DE EGRESOS</h2>
        <p class="text-white-50">Registre Facturas de Proveedores y Gastos Generales.</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-lg w-100"
            style="background-color: var(--neon-purple); border: none; color: white; box-shadow: 0 0 10px var(--neon-purple);"
            data-bs-toggle="modal" data-bs-target="#modalNuevoEgreso">
            <i class="fas fa-plus-circle"></i> Nuevo Egreso
        </button>
    </div>
</div>

<?php if (isset($mensaje)): ?>
    <div class="alert alert-success"><?php echo $mensaje; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card" style="border: 1px solid var(--neon-purple); background-color: var(--card-bg);">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <select id="documentFilter" class="form-control">
                    <option value="">Todos</option>
                    <option value="factura">Factura Proveedor</option>
                    <option value="gasto">Gasto General</option>
                </select>
            </div>
            <div class="col-md-9">
                <input type="text" id="globalSearch" class="form-control" placeholder="Buscar egreso...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover table-dark align-middle">
                <thead>
                    <tr style="border-bottom: 2px solid var(--neon-purple);">
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Proveedor / Detalle</th>
                        <th>Referencia / N°</th>
                        <th>Descripción</th>
                        <th class="text-end">Monto</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $res_list->fetch_assoc()):
                        $es_gasto = $row['tipo'] == 'gasto';
                        $detalle = $es_gasto ? 'Gasto General' : $row['nombre_proveedor'];
                        ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['fecha'])); ?></td>
                            <td>
                                <span class="badge <?php echo $es_gasto ? 'bg-secondary' : 'bg-primary'; ?>">
                                    <?php echo strtoupper($row['tipo']); ?>
                                </span>
                            </td>
                            <td><?php echo $detalle; ?></td>
                            <td><?php echo $row['numero_comprobante']; ?></td>
                            <td><?php echo $row['descripcion']; ?></td>
                            <td class="text-end text-warning" style="font-family: monospace; font-size: 1.1rem;">
                                $<?php echo number_format($row['monto'], 2); ?>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="transacciones.php" style="display:inline;"
                                    onsubmit="return confirm('¿Eliminar este egreso?');">
                                    <input type="hidden" name="accion" value="borrar_transaccion">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                            class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Nuevo Egreso -->
    <div class="modal fade" id="modalNuevoEgreso" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid var(--neon-purple);">
                <div class="modal-header">
                    <h5 class="modal-title text-white">Registrar Nuevo Egreso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="nuevo_egreso">

                        <div class="mb-3">
                            <label class="text-white">Tipo de Egreso</label>
                            <select name="tipo_egreso" id="tipo_egreso_select" class="form-control" required
                                onchange="toggleEgresoFields()">
                                <option value="factura_proveedor">Factura de Proveedor</option>
                                <option value="gasto_general">Gasto General / Caja Chica</option>
                            </select>
                        </div>

                        <!-- Campos Factura Proveedor -->
                        <div id="campos_proveedor">
                            <div class="mb-3">
                                <label class="text-white">Proveedor</label>
                                <select name="proveedor_id" class="form-control">
                                    <?php
                                    $proveedores->data_seek(0);
                                    while ($p = $proveedores->fetch_assoc()): ?>
                                        <option value="<?php echo $p['id']; ?>"><?php echo $p['nombre']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="text-white">Número de Factura</label>
                                <input type="text" name="numero_factura" class="form-control">
                            </div>
                        </div>

                        <!-- Campos Gasto General -->
                        <div id="campos_gasto" style="display:none;">
                            <div class="mb-3">
                                <label class="text-white">Referencia (Ticket / Comprobante)</label>
                                <input type="text" name="referencia" class="form-control"
                                    placeholder="Ej: Ticket Peaje">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-white">Descripción / Detalle</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-white">Fecha</label>
                                <input type="date" name="fecha" class="form-control"
                                    value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="text-white">Monto Total</label>
                                <input type="number" step="0.01" name="monto" class="form-control" required>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn"
                            style="background-color: var(--neon-purple); color: white;">Guardar Egreso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleEgresoFields() {
            const tipo = document.getElementById('tipo_egreso_select').value;
            const camposProv = document.getElementById('campos_proveedor');
            const camposGasto = document.getElementById('campos_gasto');

            if (tipo === 'factura_proveedor') {
                camposProv.style.display = 'block';
                camposGasto.style.display = 'none';
                camposProv.querySelector('select').setAttribute('required', 'required');
                camposProv.querySelector('input').setAttribute('required', 'required');
                camposGasto.querySelector('input').removeAttribute('required');
            } else {
                camposProv.style.display = 'none';
                camposGasto.style.display = 'block';
                camposProv.querySelector('select').removeAttribute('required');
                camposProv.querySelector('input').removeAttribute('required');
                camposGasto.querySelector('input').setAttribute('required', 'required');
            }
        }
    </script>

    <?php require_once 'footer.php'; ?>