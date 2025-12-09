<?php
// cuenta_corriente.php - Visor Detallado de Cuenta Corriente
// Esta página muestra el detalle fino de lo que nos deben (Clientes) o lo que debemos (Proveedores).
// Diferencia entre "DEBE" (lo que suma deuda) y "HABER" (lo que resta deuda).

require_once 'db.php';     // Conexión
require_once 'header.php'; // Diseño

// 1. Detectar qué estamos buscando (¿Cliente o Proveedor?)
$tipo_entidad = $_GET['tipo'] ?? 'cliente'; // Por defecto Cliente
$entidad_id = $_GET['id'] ?? 0;             // ID específico (si vale 0, no seleccionó nadie aún)

// 2. Traer listas para el filtro (Buscador superior)
$clientes = $conexion->query("SELECT id, nombre FROM clientes WHERE estado = 1 ORDER BY nombre ASC");
$proveedores = $conexion->query("SELECT id, nombre FROM proveedores WHERE estado = 1 ORDER BY nombre ASC");

// Variables donde guardaremos los resultados
$transacciones = [];
$saldo = 0;
$entidad_nombre = "";

// 3. Si ya eligieron a alguien ($entidad_id > 0), buscamos su información.
if ($entidad_id > 0) {
    // a) Buscamos su nombre para el título
    $tabla = ($tipo_entidad == 'cliente') ? 'clientes' : 'proveedores';
    $stmt_nom = $conexion->prepare("SELECT nombre FROM $tabla WHERE id = ?");
    $stmt_nom->bind_param("i", $entidad_id);
    $stmt_nom->execute();
    $res_nom = $stmt_nom->get_result();
    if ($res_nom->num_rows > 0) {
        $entidad_nombre = $res_nom->fetch_assoc()['nombre'];
    }

    // b) Buscamos sus movimientos (Facturas, Recibos, etc)
    // Ordenamos por fecha ASC para reconstruir la historia desde el principio.
    $sql = "SELECT * FROM transacciones WHERE tipo_entidad = ? AND entidad_id = ? AND estado IN (0, 1) ORDER BY fecha ASC, id ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $tipo_entidad, $entidad_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $saldo_calculo = 0; // Saldo temporal para el cálculo línea por línea

    // Recorremos cada movimiento...
    while ($row = $result->fetch_assoc()) {
        $debe = 0;
        $haber = 0;
        $monto = floatval($row['monto']);
        $tipo = $row['tipo'];
        $eliminada = $row['estado'] == 0; // ¿La transacción fue borrada?

        // CÁLCULO DE SALDOS (Solo sumamos si NO está eliminada)
        if (!$eliminada) {
            
            // CASO CLIENTE (Ellos nos deben)
            if ($tipo_entidad == 'cliente') {
                if ($tipo == 'factura' || $tipo == 'nota_debito') {
                    // Factura AUMENTA deuda (Columna DEBE)
                    $debe = $monto;
                    $saldo_calculo += $debe; 
                } elseif ($tipo == 'recibo' || $tipo == 'nota_credito') {
                    // Pagos DISMINUYEN deuda (Columna HABER)
                    $haber = $monto;
                    $saldo_calculo -= $haber;
                }
            } else {
                // CASO PROVEEDOR (Nosotros debemos)
                // Se invierte la lógica:
                if ($tipo == 'factura' || $tipo == 'nota_debito') {
                    // Compra (Factura) AUMENTA nuestra deuda (Columna HABER, pasivo)
                    $haber = $monto;
                    $saldo_calculo += $haber;
                } elseif ($tipo == 'recibo' || $tipo == 'nota_credito') {
                    // Pago nuestro (Recibo) DISMINUYE deuda (Columna DEBE)
                    $debe = $monto;
                    $saldo_calculo -= $debe;
                }
            }
        }

        // Guardamos los valores calculados en la fila para usarlos luego en la tabla HTML
        $row['debe_calc'] = $debe;
        $row['haber_calc'] = $haber;
        $row['saldo_calc'] = $saldo_calculo;

        $transacciones[] = $row;
    }

    // Invertimos el arreglo para mostrar lo más reciente arriba (DESC),
    // pero habiendo calculado los saldos correctamente (ASC).
    $transacciones = array_reverse($transacciones);
}
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-4" style="color: var(--neon-cyan);">CUENTA CORRIENTE <small class="text-white-50 fs-6">(Partida
                Doble)</small></h2>

        <!-- Filtro de Selección -->
        <div class="card mb-4" style="border: 1px solid var(--neon-cyan); background-color: var(--card-bg);">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3">
                        <label class="text-white">Tipo</label>
                        <select name="tipo" id="tipo_entidad" class="form-control" onchange="this.form.submit()">
                            <option value="cliente" <?php echo $tipo_entidad == 'cliente' ? 'selected' : ''; ?>>Cliente
                            </option>
                            <option value="proveedor" <?php echo $tipo_entidad == 'proveedor' ? 'selected' : ''; ?>>
                                Proveedor</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="text-white">Seleccionar Entidad</label>
                        <select name="id" class="form-control">
                            <option value="0">-- Seleccionar --</option>
                            <?php
                            $lista = ($tipo_entidad == 'cliente') ? $clientes : $proveedores;
                            if ($lista) {
                                $lista->data_seek(0);
                                while ($item = $lista->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $item['id']; ?>" <?php echo $entidad_id == $item['id'] ? 'selected' : ''; ?>>
                                        <?php echo $item['nombre']; ?>
                                    </option>
                                <?php endwhile;
                            } ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Ver Cuenta</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($entidad_id > 0): ?>
            <h4 class="text-center mb-3">Historial de: <span
                    style="color: var(--neon-pink); text-transform: uppercase;"><?php echo $entidad_nombre; ?></span></h4>

            <div class="card" style="box-shadow: 0 0 15px rgba(0,243,255,0.1);">
                <div class="card-body table-responsive p-0">
                    <table class="table table-dark table-hover mb-0">
                        <thead style="background-color: var(--bg-dark); border-bottom: 2px solid var(--neon-cyan);">
                            <tr class="text-center">
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Comprobante</th>
                                <th class="text-info">DEBE</th>
                                <th class="text-warning">HABER</th>
                                <th>SALDO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Si no hay transacciones
                            if (empty($transacciones)) {
                                echo '<tr><td colspan="6" class="text-center py-4">No hay movimientos registrados.</td></tr>';
                            }

                            // Mostramos el último saldo calculado (que es el primero chronológicamente si no hay movs, 
                            // pero aquí queremos el SALDO FINAL arriba? No, en tabla histórica el saldo es fila a fila).
                            // El "Saldo Final" global (acumulado total) es el de la PRIMERA fila del array invertido.
                            // (Porque la última operación define el saldo actual).
                        
                            $saldo_final_total = 0;
                            if (!empty($transacciones)) {
                                $saldo_final_total = $transacciones[0]['saldo_calc'];
                            }

                            foreach ($transacciones as $t):
                                $debe = $t['debe_calc'];
                                $haber = $t['haber_calc'];
                                $saldo_fila = $t['saldo_calc'];
                                $tipo = $t['tipo'];
                                $eliminada = $t['estado'] == 0;
                                $estilo_fila = $eliminada ? 'class="text-decoration-line-through text-muted"' : 'class="align-middle"';

                                // Formateo visual
                                $tipo_str = ucfirst(str_replace('_', ' ', $tipo));
                                if ($t['letra'])
                                    $tipo_str .= " (" . $t['letra'] . ")";
                                if ($eliminada)
                                    $tipo_str .= " (ANULADO)";
                                ?>
                                <tr <?php echo $estilo_fila; ?>>
                                    <td class="text-center"><?php echo date('d/m/Y', strtotime($t['fecha'])); ?></td>
                                    <td><?php echo $tipo_str; ?></td>
                                    <td class="text-center"><?php echo $t['numero_comprobante'] ?: '-'; ?></td>

                                    <!-- DEBE -->
                                    <td class="text-end text-info" style="font-family: monospace; font-size: 1.1em;">
                                        <?php echo ($debe > 0 && !$eliminada) ? '$ ' . number_format($debe, 2) : '-'; ?>
                                    </td>

                                    <!-- HABER -->
                                    <td class="text-end text-warning" style="font-family: monospace; font-size: 1.1em;">
                                        <?php echo ($haber > 0 && !$eliminada) ? '$ ' . number_format($haber, 2) : '-'; ?>
                                    </td>

                                    <!-- SALDO -->
                                    <td class="text-end fw-bold"
                                        style="font-family: monospace; font-size: 1.2em; color: <?php echo $saldo_fila >= 0 ? '#00f3ff' : '#ff0055'; ?>;">
                                        $ <?php echo number_format($saldo_fila, 2); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot style="border-top: 2px solid var(--neon-cyan);">
                            <tr style="background-color: rgba(0, 243, 255, 0.05);">
                                <td colspan="5" class="text-end text-uppercase" style="letter-spacing: 2px;">SALDO FINAL
                                </td>
                                <td class="text-end fw-bold"
                                    style="font-family: 'Orbitron', monospace; font-size: 1.4em; color: <?php echo $saldo_final_total >= 0 ? '#00f3ff' : '#ff0055'; ?>; text-shadow: 0 0 10px rgba(0,243,255,0.3);">
                                    $ <?php echo number_format($saldo_final_total, 2); ?>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>