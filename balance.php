<?php
// balance.php - Visor Simple de Cuentas Corrientes
// Esta página permite ver la historia de movimientos y el saldo adeudado de un Cliente o Proveedor.

require_once 'config.php';
require_once 'auth.php';
verificar_autenticacion(); // Paso 1: Verificar que esté logueado.
require_once 'header.php';

// Paso 2: Obtener a quién estamos buscando (Cliente o Proveedor) desde la URL
// Si no se especifica, asumimos 'cliente'.
$tipo_entidad = isset($_GET['tipo']) ? $_GET['tipo'] : 'cliente';
$entidad_id = isset($_GET['id']) ? $_GET['id'] : '';

// Paso 3: Cargar las listas para el menú desplegable (Select)
// Buscamos todas las personas activas para que el usuario pueda elegir.
$clientes = $conexion->query("SELECT id, nombre FROM clientes WHERE estado = 1");
$proveedores = $conexion->query("SELECT id, nombre FROM proveedores WHERE estado = 1");

$historial = []; // Aquí guardaremos la lista de movimientos.
$saldo = 0;      // Aquí iremos sumando/restando el dinero.

// Paso 4: Si el usuario ya eligió a alguien ($entidad_id tiene valor)
if ($entidad_id) {
    // Buscamos todas las transacciones de esa persona, ordenadas por fecha (de la más vieja a la nueva).
    // Esto es necesario para ir calculando el "saldo acumulado" fila por fila.
    $sql = "SELECT * FROM transacciones WHERE tipo_entidad = ? AND entidad_id = ? AND estado = 1 ORDER BY fecha ASC, id ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $tipo_entidad, $entidad_id);
    $stmt->execute();
    $res = $stmt->get_result();

    // Recorremos cada movimiento para calcular los numeros...
    while ($row = $res->fetch_assoc()) {
        $monto = $row['monto'];
        $tipo = $row['tipo']; // ¿Fue factura? ¿Recibo?

        // --- LÓGICA DE SUMA Y RESTA (CONTABILIDAD BÁSICA) ---
        if ($tipo_entidad == 'cliente') {
            // SI ES CLIENTE (Ellos nos deben a nosotros):
            // - Factura o Nota de Débito: Aumenta la deuda (SUMA).
            // - Recibo o Nota de Crédito: Disminuye la deuda (RESTA).
            if ($tipo == 'factura' || $tipo == 'nota_debito') {
                $saldo += $monto;
            } else {
                $saldo -= $monto;
            }
        } else {
            // SI ES PROVEEDOR (Nosotros le debemos a ellos):
            // - Factura (Compra): Aumenta nuestra deuda con ellos (SUMA).
            // - Recibo (Pago) o NC: Disminuye nuestra deuda (RESTA).
            if ($tipo == 'factura' || $tipo == 'nota_debito') {
                $saldo += $monto;
            } else {
                $saldo -= $monto;
            }
        }

        // Guardamos el saldo parcial en este momento de la historia.
        $row['saldo_acumulado'] = $saldo;

        // Agregamos este movimiento a la lista final.
        $historial[] = $row;
    }
}
?>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">Consultar Cuenta Corriente</div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <select name="tipo" class="form-control" onchange="this.form.submit()">
                            <option value="cliente" <?php echo $tipo_entidad == 'cliente' ? 'selected' : ''; ?>>Cliente
                            </option>
                            <option value="proveedor" <?php echo $tipo_entidad == 'proveedor' ? 'selected' : ''; ?>>
                                Proveedor</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="id" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php
                            $lista = ($tipo_entidad == 'cliente') ? $clientes : $proveedores;
                            foreach ($lista as $item) {
                                $selected = ($entidad_id == $item['id']) ? 'selected' : '';
                                echo "<option value='{$item['id']}' $selected>{$item['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Ver</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php if ($entidad_id): ?>
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Historial de Transacciones</span>
                    <span class="badge bg-primary fs-5">Saldo Actual: $<?php echo number_format($saldo, 2); ?></span>
                </div>
                <div class="card-body">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>ID</th>
                                <th>Debe</th>
                                <th>Haber</th>
                                <th>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $historial = array_reverse($historial);
                            foreach ($historial as $t):
                                $debe = 0;
                                $haber = 0;
                                // Visualización contable simple
                                if ($tipo_entidad == 'cliente') {
                                    if ($t['tipo'] == 'factura' || $t['tipo'] == 'nota_debito') {
                                        $debe = $t['monto'];
                                    } else {
                                        $haber = $t['monto'];
                                    }
                                } else {
                                    // Proveedor
                                    if ($t['tipo'] == 'recibo' || $t['tipo'] == 'nota_credito') {
                                        $debe = $t['monto']; // Pagamos (sale dinero/baja deuda)
                                    } else {
                                        $haber = $t['monto']; // Nos facturan (sube deuda)
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?php echo $t['fecha']; ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $t['tipo'])); ?></td>
                                    <td>#<?php echo $t['id']; ?></td>
                                    <td class="text-danger"><?php echo $debe > 0 ? '$' . number_format($debe, 2) : '-'; ?></td>
                                    <td class="text-success"><?php echo $haber > 0 ? '$' . number_format($haber, 2) : '-'; ?>
                                    </td>
                                    <td><strong>$<?php echo number_format($t['saldo_acumulado'], 2); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>