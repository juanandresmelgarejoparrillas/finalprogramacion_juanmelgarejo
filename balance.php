<?php
require_once 'config.php';
require_once 'auth.php';
verificar_autenticacion();
require_once 'header.php';

$tipo_entidad = isset($_GET['tipo']) ? $_GET['tipo'] : 'cliente';
$entidad_id = isset($_GET['id']) ? $_GET['id'] : '';

// Obtener lista para el buscador
$clientes = $conexion->query("SELECT id, nombre FROM clientes WHERE estado = 1");
$proveedores = $conexion->query("SELECT id, nombre FROM proveedores WHERE estado = 1");

$historial = [];
$saldo = 0;

if ($entidad_id) {
    // Calcular saldo e historial
    // Saldo Cliente: Facturas (Debe) - Recibos (Haber) - Notas Credito (Haber) + Notas Debito (Debe)
    // Saldo Proveedor: Facturas (Haber - lo que debemos) - Recibos (Debe - lo que pagamos) ...

    $sql = "SELECT * FROM transacciones WHERE tipo_entidad = ? AND entidad_id = ? AND estado = 1 ORDER BY fecha ASC, id ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $tipo_entidad, $entidad_id);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $monto = $row['monto'];
        $tipo = $row['tipo'];

        // Lógica Contable:
        if ($tipo_entidad == 'cliente') {
            // CLIENTES:
            // - Factura/ND (Nos deben plata) -> SUMA al saldo.
            // - Recibo/NC (Nos pagaron o descontamos) -> RESTA al saldo.
            if ($tipo == 'factura' || $tipo == 'nota_debito') {
                $saldo += $monto;
            } else {
                $saldo -= $monto;
            }
        } else {
            // PROVEEDORES:
            // - Factura (Les debemos plata) -> SUMA al saldo (deuda).
            // - Recibo (Les pagamos) -> RESTA a la deuda.
            if ($tipo == 'factura' || $tipo == 'nota_debito') {
                $saldo += $monto;
            } else {
                $saldo -= $monto;
            }
        }

        $row['saldo_acumulado'] = $saldo;
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