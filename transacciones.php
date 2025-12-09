<?php
// transacciones.php - Núcleo de Gestión de Movimientos
// Esta es la página más importante. Aquí se crean Facturas, Recibos, Notas de Crédito, etc.
// Maneja toda la lógica de cuentas corrientes y pagos.

require_once 'config.php';
require_once 'auth.php';
verificar_autenticacion(); // Paso 1: Seguridad.

// --- AJAX: OBTENER RELACIONADOS ---
// Esto sirve para cuando apretamos el botón "Ver relaciones" (el ojito).
// Devuelve una lista de documentos vinculados (ej: Recibos vinculados a una factura) sin recargar la página.
if (isset($_GET['ajax_relacionados'])) {
    $id = intval($_GET['ajax_relacionados']);
    $sql = "SELECT * FROM transacciones WHERE transaccion_relacionada_id = $id AND estado = 1 ORDER BY fecha DESC";
    $res = $conexion->query($sql);
    $docs = [];
    while ($row = $res->fetch_assoc()) {
        $docs[] = $row;
    }
    // Devolvemos la respuesta en formato JSON (datos puros para Javascript).
    header('Content-Type: application/json');
    echo json_encode($docs);
    exit;
}

require_once 'header.php'; // Parte visual.

$mensaje = ""; // Mensaje verde (éxito)
$error = "";   // Mensaje rojo (fallo)

// --- LÓGICA POST (PROCESAR SI EL USUARIO GUARDÓ ALGO) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. NUEVA FACTURA
    // Se ejecuta cuando llenamos el formulario "Nueva Factura".
    if (isset($_POST['accion']) && $_POST['accion'] == 'nueva_factura') {
        $tipo_entidad = $_POST['tipo_entidad']; // ¿Cliente o Proveedor?
        $entidad_id = $_POST['entidad_id'];     // ¿Quién exactamente?
        $fecha = $_POST['fecha'];
        $monto = $_POST['monto'];
        $numero = $_POST['numero'];
        $letra = $_POST['letra'];      // A, B, C...
        $condicion = $_POST['condicion']; // 'contado' o 'cuenta_corriente'
        $descripcion = $_POST['descripcion'] ?? '';

        // Cálculo inicial de Saldo Pendiente (Deuda).
        // Si es 'cuenta_corriente', la deuda es total.
        // Si es 'contado', conceptualmente la deuda nace y muere, pero por orden técnico la creamos primero.
        $saldo_pendiente = ($condicion == 'cuenta_corriente') ? $monto : 0;

        // Insertamos la FACTURA en la base de datos.
        $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, numero_comprobante, letra, condicion_pago, saldo_pendiente, descripcion, estado) VALUES ('factura', ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sidssssds", $tipo_entidad, $entidad_id, $monto, $fecha, $numero, $letra, $condicion, $saldo_pendiente, $descripcion);

        if ($stmt->execute()) {
            $factura_id = $stmt->insert_id; // Obtenemos el ID de la factura recién creada.

            // CASO ESPECIAL: VENTA DE CONTADO
            // Si fue contado, generamos automáticamente el RECIBO de pago para que la deuda quede saldada.
            if ($condicion == 'contado') {
                $sql_recibo = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, transaccion_relacionada_id, estado) VALUES ('recibo', ?, ?, ?, ?, ?, 1)";
                $stmt_recibo = $conexion->prepare($sql_recibo);
                $stmt_recibo->bind_param("sidsi", $tipo_entidad, $entidad_id, $monto, $fecha, $factura_id);
                $stmt_recibo->execute();

                $mensaje = "Factura de Contado registrada. Recibo generado automáticamente.";
            } else {
                $mensaje = "Factura registrada correctamente. Se generó deuda en Cta Cte.";
            }
        } else {
            $error = "Error al registrar factura: " . $conexion->error;
        }
    }

    // 1.5 NUEVA NOTA DE DÉBITO (SOLO ADMIN)
    // La Nota de Débito AUMENTA la deuda del cliente (ej: cheque rechazado, interés por mora).
    // Funciona casi igual que una factura a nivel contable.
    if (isset($_POST['accion']) && $_POST['accion'] == 'nueva_nota_debito') {
        if (es_admin()) {
            $tipo_entidad = $_POST['tipo_entidad'];
            $entidad_id = $_POST['entidad_id'];
            $fecha = $_POST['fecha'];
            $monto = $_POST['monto'];
            $numero = $_POST['numero'];
            $letra = $_POST['letra'];

            // La ND siempre genera saldo pendiente (deuda), no puede ser de contado directo aquí.
            $saldo_pendiente = $monto;
            $condicion = 'cuenta_corriente';

            $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, numero_comprobante, letra, condicion_pago, saldo_pendiente, estado) VALUES ('nota_debito', ?, ?, ?, ?, ?, ?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sidssssd", $tipo_entidad, $entidad_id, $monto, $fecha, $numero, $letra, $condicion, $saldo_pendiente);

            if ($stmt->execute()) {
                $mensaje = "Nota de Débito registrada correctamente.";
            } else {
                $error = "Error al registrar ND: " . $conexion->error;
            }
        } else {
            $error = "Acceso denegado."; // Seguridad extra.
        }
    }

    // 2. NUEVO RECIBO (IMPUTADO A UNA FACTURA ESPECÍFICA)
    // Cuando entramos a pagar una factura puntual apretando el botón verde "Recibo".
    if (isset($_POST['accion']) && $_POST['accion'] == 'nuevo_recibo') {
        $factura_id = $_POST['factura_id'];
        $monto_recibo = $_POST['monto'];
        $fecha = $_POST['fecha'];

        // Paso A: Buscamos la factura original para ver cuánto debe.
        $qry = $conexion->query("SELECT * FROM transacciones WHERE id = $factura_id");
        $factura = $qry->fetch_assoc();

        // Validación: No podemos pagar más de lo que se debe.
        if ($monto_recibo > $factura['saldo_pendiente']) {
            $error = "Error: El monto ($$monto_recibo) supera el saldo pendiente ($" . $factura['saldo_pendiente'] . ").";
        } else {
            $tipo_entidad = $factura['tipo_entidad'];
            $entidad_id = $factura['entidad_id'];

            // Paso B: Creamos el RECIBO vinculado a esa factura.
            $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, transaccion_relacionada_id, estado) VALUES ('recibo', ?, ?, ?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sidsi", $tipo_entidad, $entidad_id, $monto_recibo, $fecha, $factura_id);

            if ($stmt->execute()) {
                // Paso C: ACTUALIZAMOS EL SALDO de la factura original.
                // Restamos lo que se pagó.
                $nuevo_saldo = $factura['saldo_pendiente'] - $monto_recibo;
                $upd = $conexion->prepare("UPDATE transacciones SET saldo_pendiente = ? WHERE id = ?");
                $upd->bind_param("di", $nuevo_saldo, $factura_id);
                $upd->execute();
                $mensaje = "Recibo registrado. Saldo de factura actualizado.";
            } else {
                $error = "Error al registrar recibo.";
            }
        }
    }

    // 2.5 NUEVO RECIBO GENERAL (DESDE BOTÓN SUPERIOR)
    // Recibos globales que pueden o no estar vinculados a una factura específica.
    if (isset($_POST['accion']) && $_POST['accion'] == 'nuevo_recibo_general') {
        $factura_id = !empty($_POST['factura_id']) ? $_POST['factura_id'] : null;
        $monto_recibo = $_POST['monto'];
        $fecha = $_POST['fecha'];
        $tipo_entidad = $_POST['tipo_entidad'];
        $entidad_id = $_POST['entidad_id'];

        if ($factura_id) {
            // CASO 1: Eligió imputar a una factura. Lógica idéntica al punto anterior.
            $qry = $conexion->query("SELECT * FROM transacciones WHERE id = $factura_id");
            $factura = $qry->fetch_assoc();

            if ($monto_recibo > $factura['saldo_pendiente']) {
                $error = "Error: El monto ($$monto_recibo) supera el saldo pendiente ($" . $factura['saldo_pendiente'] . ").";
            } else {
                $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, transaccion_relacionada_id, estado) VALUES ('recibo', ?, ?, ?, ?, ?, 1)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("sidsi", $tipo_entidad, $entidad_id, $monto_recibo, $fecha, $factura_id);

                if ($stmt->execute()) {
                    $nuevo_saldo = $factura['saldo_pendiente'] - $monto_recibo;
                    $upd = $conexion->prepare("UPDATE transacciones SET saldo_pendiente = ? WHERE id = ?");
                    $upd->bind_param("di", $nuevo_saldo, $factura_id);
                    $upd->execute();
                    $mensaje = "Recibo registrado e imputado correctamente.";
                } else {
                    $error = "Error al registrar recibo.";
                }
            }
        } else {
            // CASO 2: PAGO A CUENTA (Sin factura relacionada)
            // Es plata que nos dan "a cuenta" de futuras compras.
            // Se registra el recibo 'suelto'. Baja el saldo global pero no cancela ninguna factura específica.
            $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, estado) VALUES ('recibo', ?, ?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sids", $tipo_entidad, $entidad_id, $monto_recibo, $fecha);
            if ($stmt->execute()) {
                $mensaje = "Recibo General (a cuenta) registrado correctamente.";
            } else {
                $error = "Error al registrar recibo general: " . $conexion->error;
            }
        }
    }

    // 3. NOTA DE CRÉDITO
    // Se usa para anular facturas o hacer descuentos. RESTA deuda.
    if (isset($_POST['accion']) && $_POST['accion'] == 'nota_credito') {
        $factura_id = $_POST['factura_id'];
        $monto = $_POST['monto'];
        $fecha = $_POST['fecha'];

        // Revisamos la factura original.
        $qry = $conexion->query("SELECT * FROM transacciones WHERE id = $factura_id");
        $factura = $qry->fetch_assoc();

        // No podemos descontar más de lo que debe.
        if ($monto > $factura['saldo_pendiente']) {
            $error = "Error: La Nota de Crédito ($$monto) no puede superar el saldo pendiente ($" . $factura['saldo_pendiente'] . ").";
        } else {
            $tipo_entidad = $factura['tipo_entidad'];
            $entidad_id = $factura['entidad_id'];

            // Registramos la NC vinculada.
            $sql = "INSERT INTO transacciones (tipo, tipo_entidad, entidad_id, monto, fecha, transaccion_relacionada_id, estado) VALUES ('nota_credito', ?, ?, ?, ?, ?, 1)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sidsi", $tipo_entidad, $entidad_id, $monto, $fecha, $factura_id);

            if ($stmt->execute()) {
                // REDUCIMOS el saldo pendiente de la factura (Igual que un pago).
                $nuevo_saldo = $factura['saldo_pendiente'] - $monto;
                $upd = $conexion->prepare("UPDATE transacciones SET saldo_pendiente = ? WHERE id = ?");
                $upd->bind_param("di", $nuevo_saldo, $factura_id);
                $upd->execute();
                $mensaje = "Nota de Crédito registrada. Saldo ajustado.";
            } else {
                $error = "Error al registrar NC.";
            }
        }
    }

    // 4. BORRAR TRANSACCIÓN (SOLO ADMIN)
    // Permite eliminar errores.
    if (isset($_POST['accion']) && $_POST['accion'] == 'borrar_transaccion') {
        if (es_admin()) {
            $id = $_POST['id'];

            // Buscamos qué estamos borrando.
            $qry = $conexion->query("SELECT * FROM transacciones WHERE id = $id");
            if ($qry->num_rows > 0) {
                $transaccion = $qry->fetch_assoc();

                // Borramos (Soft Delete) la transacción.
                $conexion->query("UPDATE transacciones SET estado = 0 WHERE id = $id");

                // LÓGICA DE RESTAURACIÓN DE DEUDA:
                // Si borramos un PAGO (Recibo) o un DESCUENTO (NC) que afectó a una factura...
                // ... esa factura vuelve a deber plata. Hay que devolverle el saldo.
                if (($transaccion['tipo'] == 'recibo' || $transaccion['tipo'] == 'nota_credito') && $transaccion['transaccion_relacionada_id']) {
                    $factura_id = $transaccion['transaccion_relacionada_id'];
                    $monto_restaurar = $transaccion['monto'];

                    // Devolvemos el monto al saldo pendiente de la factura original.
                    $conexion->query("UPDATE transacciones SET saldo_pendiente = saldo_pendiente + $monto_restaurar WHERE id = $factura_id");
                    $mensaje = "Documento eliminado. Saldo de factura restaurado.";
                } else {
                    $mensaje = "Documento eliminado correctamente.";
                }
            }
        } else {
            $error = "Acceso denegado.";
        }
    }
}

// --- LÓGICA DE VISTAS (QUÉ PANTALLA MOSTRAMOS) ---
// View 1: 'seleccion' -> Muestra lista de gente para elegir.
// View 2: 'detalle'   -> Muestra las facturas y botones de acción de una persona.

$vista = isset($_GET['vista']) ? $_GET['vista'] : 'seleccion';
$tipo_entidad = isset($_GET['tipo']) ? $_GET['tipo'] : 'cliente';
$entidad_id = isset($_GET['id']) ? $_GET['id'] : null;

$entidad_data = null;
if ($entidad_id) {
    // Si ya elegimos a alguien, traemos sus nombre y datos básicos.
    $tabla = ($tipo_entidad == 'cliente') ? 'clientes' : 'proveedores';
    $res = $conexion->query("SELECT * FROM $tabla WHERE id = $entidad_id");
    $entidad_data = $res->fetch_assoc();
}
?>

<div class="row">
    <div class="col-12">
        <?php if ($mensaje): ?>
            <div class="alert alert-success"><?php echo $mensaje; ?></div><?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    </div>
</div>

<?php if ($vista == 'seleccion'): ?>
    <!-- VISTA 1: SELECCIÓN DE CLIENTE/PROVEEDOR -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center">
                    <h3>Seleccionar Entidad</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="btn-group" role="group">
                            <a href="?vista=seleccion&tipo=cliente"
                                class="btn <?php echo $tipo_entidad == 'cliente' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Clientes</a>
                            <a href="?vista=seleccion&tipo=proveedor"
                                class="btn <?php echo $tipo_entidad == 'proveedor' ? 'btn-primary' : 'btn-outline-secondary'; ?>">Proveedores</a>
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="text" id="buscador" class="form-control" placeholder="Buscar por nombre..."
                            onkeyup="filtrarLista()">
                    </div>
                    <div class="list-group" id="lista-entidades">
                        <?php
                        $tabla = ($tipo_entidad == 'cliente') ? 'clientes' : 'proveedores';
                        // Traemos solo los activos (estado=1)
                        $sql = "SELECT * FROM $tabla WHERE estado = 1 ORDER BY nombre ASC";
                        $res = $conexion->query($sql);
                        while ($fila = $res->fetch_assoc()):
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center item-entidad">
                                <span><?php echo $fila['nombre']; ?></span>
                                <a href="?vista=detalle&tipo=<?php echo $tipo_entidad; ?>&id=<?php echo $fila['id']; ?>"
                                    class="btn btn-sm btn-success">Ingresar Movimiento</a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php elseif ($vista == 'detalle' && $entidad_data): ?>
    <!-- VISTA 2: DETALLE Y MOVIMIENTOS -->
    <div class="row">
        <div class="col-12 mb-3">
            <a href="transacciones.php" class="btn btn-outline-light"><i class="fas fa-arrow-left"></i> Volver</a>
        </div>
        <div class="col-12 mb-4">
            <div class="card border-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h2 class="m-0 text-primary"><?php echo $entidad_data['nombre']; ?> <small
                            class="text-muted">(<?php echo ucfirst($tipo_entidad); ?>)</small></h2>
                    <div>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#modalFactura"><i
                                class="fas fa-plus"></i> Nueva Factura</button>
                        <button class="btn btn-success btn-lg ms-2" data-bs-toggle="modal"
                            data-bs-target="#modalReciboGeneral"><i class="fas fa-hand-holding-usd"></i> Nuevo
                            Recibo</button>
                        <?php if (es_admin()): ?>
                            <button class="btn btn-warning btn-lg ms-2" data-bs-toggle="modal" data-bs-target="#modalND"><i
                                    class="fas fa-plus"></i> Nota Débito</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-4" style="border: 1px solid var(--neon-cyan); background-color: var(--card-bg);">
                <div class="card-header">Listado de Transacciones</div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <select id="documentFilter" class="form-control">
                                <option value="">Todos los Documentos</option>
                                <option value="factura">Factura</option>
                                <option value="recibo">Recibo</option>
                                <option value="nota crédito">Nota Crédito</option>
                                <option value="nota débito">Nota Débito</option>
                            </select>
                        </div>
                        <div class="col-md-9">
                            <input type="text" id="globalSearch" class="form-control"
                                placeholder="Buscar por cliente, número, monto...">
                        </div>
                    </div>
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>N° Comp.</th>
                                <th>Letra</th>
                                <th>Monto</th>
                                <th>Condición</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // CONSULTA CLAVE PARA LA TABLA:
                            // Queremos ver:
                            // 1. Facturas y Notas de Débito (Documentos que generan deuda).
                            // 2. Recibos "sueltos" o generales (Pagos a cuenta que no están imputados a nada específico).
                            // NO mostramos aquí los Recibos/NC que ya están vinculados a una factura, para no ensuciar la lista principal.
                            $sql = "SELECT * FROM transacciones WHERE (tipo IN ('factura', 'nota_debito') OR (tipo = 'recibo' AND transaccion_relacionada_id IS NULL)) AND tipo_entidad = '$tipo_entidad' AND entidad_id = $entidad_id AND estado = 1 ORDER BY fecha DESC";
                            $facturas = $conexion->query($sql);

                            if ($facturas->num_rows > 0):
                                while ($f = $facturas->fetch_assoc()):
                                    $es_factura_nd = in_array($f['tipo'], ['factura', 'nota_debito']);
                                    $es_recibo = $f['tipo'] == 'recibo';

                                    // Preparamos los textos para que se vean lindos en pantalla
                                    $tipo_doc = ucfirst(str_replace('_', ' ', $f['tipo']));
                                    $condicion_texto = '-';
                                    $saldo_texto = '-';
                                    $clase_estado = 'text-white';
                                    $texto_estado = '-';

                                    // LÓGICA DE ESTADOS VISUALES (Semáforo de colores)
                                    if ($es_factura_nd) {
                                        $es_contado = $f['condicion_pago'] == 'contado';
                                        $pagada = $f['saldo_pendiente'] <= 0; // Si el saldo es 0, ya está pagada.
                        
                                        // Verificamos si fue ANULADA por una Nota de Crédito completa.
                                        $anulada_por_nc = false;
                                        if ($pagada && !$es_contado) {
                                            // Buscamos si tiene NC asociadas que sumen el total.
                                            $chk_nc = $conexion->query("SELECT SUM(monto) as total_nc FROM transacciones WHERE transaccion_relacionada_id = {$f['id']} AND tipo = 'nota_credito' AND estado = 1");
                                            $row_nc = $chk_nc->fetch_assoc();

                                            if ($row_nc['total_nc'] >= $f['monto'])
                                                $anulada_por_nc = true;
                                        }

                                        // Definimos el color y el texto según el estado.
                                        $clase_estado = $pagada ? 'text-success' : 'text-danger';
                                        $texto_estado = $pagada ? 'PAGADA' : 'PENDIENTE';

                                        if ($es_contado)
                                            $texto_estado = 'PAGADA (Contado)';

                                        if ($anulada_por_nc) {
                                            $texto_estado = 'ANULADA POR NC';
                                            $clase_estado = 'text-warning'; // Amarillo
                                        }

                                        $condicion_texto = ucfirst(str_replace('_', ' ', $f['condicion_pago']));
                                        $saldo_texto = '$' . number_format($f['saldo_pendiente'], 2);
                                    } elseif ($es_recibo) {
                                        // Es un recibo suelto (pago a cuenta)
                                        $texto_estado = 'RECIBO';
                                        $clase_estado = 'text-success';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($f['fecha'])); ?></td>
                                        <td><?php echo $tipo_doc; ?></td>
                                        <td><?php echo $f['numero_comprobante'] ?: '-'; ?></td>
                                        <td><span class="badge bg-secondary"><?php echo $f['letra'] ?: '-'; ?></span></td>
                                        <td>$<?php echo number_format($f['monto'], 2); ?></td>
                                        <td><?php echo $condicion_texto; ?></td>
                                        <td class="fw-bold"><?php echo $saldo_texto; ?></td>
                                        <td class="<?php echo $clase_estado; ?> fw-bold"><?php echo $texto_estado; ?></td>
                                        <td>
                                            <!-- Botones de Acción -->

                                            <!-- 1. Pagar (Solo si debe plata y no es contado) -->
                                            <?php if ($es_factura_nd && !$es_contado && !$pagada): ?>
                                                <button class="btn btn-sm btn-success"
                                                    onclick="abrirRecibo(<?php echo $f['id']; ?>, <?php echo $f['saldo_pendiente']; ?>)">Recibo</button>
                                            <?php endif; ?>

                                            <!-- 2. Nota de Crédito (Para anular o descontar) -->
                                            <?php if ($es_factura_nd && !$pagada): ?>
                                                <button class="btn btn-sm btn-warning"
                                                    onclick="abrirNC(<?php echo $f['id']; ?>, <?php echo $f['saldo_pendiente']; ?>)">Nota
                                                    Crédito</button>
                                            <?php endif; ?>

                                            <!-- 3. Ver relacionados (pagos ya hechos, NCs vinculadas) -->
                                            <?php if ($es_factura_nd): ?>
                                                <button class="btn btn-sm btn-info" onclick="verRelacionados(<?php echo $f['id']; ?>)"
                                                    title="Ver Documentos Relacionados"><i class="fas fa-eye"></i></button>
                                            <?php endif; ?>

                                            <!-- 4. BORRAR (Solo el Admin puede borrar cosas) -->
                                            <?php if (es_admin()): ?>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('¿Borrar este documento? <?php echo $es_recibo ? "Se restaurará el saldo de la factura si aplica." : "Se perderá el historial visible."; ?>')">
                                                    <input type="hidden" name="accion" value="borrar_transaccion">
                                                    <input type="hidden" name="id" value="<?php echo $f['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Borrar (Admin)"><i
                                                            class="fas fa-trash"></i></button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">No hay documentos registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALES -->
    <div class="modal fade" id="modalFactura" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid var(--neon-blue);">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Factura</h5><button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="nueva_factura">
                        <input type="hidden" name="tipo_entidad" value="<?php echo $tipo_entidad; ?>">
                        <input type="hidden" name="entidad_id" value="<?php echo $entidad_id; ?>">
                        <div class="mb-3"><label>Fecha</label><input type="date" name="fecha" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="row">
                            <div class="col-md-4 mb-3"><label>Letra</label><select name="letra" class="form-control"
                                    required>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                </select></div>
                            <div class="col-md-8 mb-3"><label>Número</label><input type="text" name="numero"
                                    class="form-control" placeholder="0001-00001234" required></div>
                        </div>
                        <div class="mb-3"><label>Monto</label><input type="number" step="0.01" name="monto"
                                class="form-control" required></div>
                        <div class="mb-3"><label>Condición</label><select name="condicion" class="form-control" required>
                                <option value="contado">Contado</option>
                                <option value="cuenta_corriente">Cuenta Corriente</option>
                            </select></div>
                        <div class="mb-3">
                            <label>Descripción / Notas</label>
                            <textarea name="descripcion" class="form-control" rows="2"
                                placeholder="Detalle opcional..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancelar</button><button type="submit"
                            class="btn btn-primary">Guardar</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL NUEVO RECIBO GENERAL -->
    <div class="modal fade" id="modalReciboGeneral" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid var(--neon-purple);">
                <!-- Purple for receipts -->
                <div class="modal-header">
                    <h5 class="modal-title">Generar Recibo</h5><button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="nuevo_recibo_general">
                        <input type="hidden" name="tipo_entidad" value="<?php echo $tipo_entidad; ?>">
                        <input type="hidden" name="entidad_id" value="<?php echo $entidad_id; ?>">

                        <div class="mb-3">
                            <label>Imputar a Factura (Opcional)</label>
                            <select name="factura_id" class="form-control" id="select_factura_general">
                                <option value="">-- Pago a Cuenta (Sin imputar) --</option>
                                <?php
                                $sql_pend = "SELECT * FROM transacciones WHERE tipo = 'factura' AND tipo_entidad = '$tipo_entidad' AND entidad_id = $entidad_id AND saldo_pendiente > 0 AND estado = 1 ORDER BY fecha ASC";
                                $res_pend = $conexion->query($sql_pend);
                                while ($p = $res_pend->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo date('d/m/Y', strtotime($p['fecha'])) . " - " . $p['numero_comprobante'] . " ($" . $p['saldo_pendiente'] . ")"; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3"><label>Fecha</label><input type="date" name="fecha" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="mb-3"><label>Monto</label><input type="number" step="0.01" name="monto"
                                class="form-control" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Generar
                            Recibo</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL NOTA DEBITO (SOLO ADMIN) -->
    <?php if (es_admin()): ?>
        <div class="modal fade" id="modalND" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid #ffc107;">
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Nota de Débito</h5><button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="nueva_nota_debito">
                            <input type="hidden" name="tipo_entidad" value="<?php echo $tipo_entidad; ?>">
                            <input type="hidden" name="entidad_id" value="<?php echo $entidad_id; ?>">
                            <div class="alert alert-warning">La Nota de Débito genera deuda en Cuenta Corriente.</div>
                            <div class="mb-3"><label>Fecha</label><input type="date" name="fecha" class="form-control"
                                    value="<?php echo date('Y-m-d'); ?>" required></div>
                            <div class="row">
                                <div class="col-md-4 mb-3"><label>Letra</label><select name="letra" class="form-control"
                                        required>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                    </select></div>
                                <div class="col-md-8 mb-3"><label>Número</label><input type="text" name="numero"
                                        class="form-control" placeholder="0001-00001234" required></div>
                            </div>
                            <div class="mb-3"><label>Monto</label><input type="number" step="0.01" name="monto"
                                    class="form-control" required></div>
                        </div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Guardar
                                ND</button></div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="modalRecibo" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid var(--neon-purple);">
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Recibo</h5><button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="nuevo_recibo">
                        <input type="hidden" name="factura_id" id="recibo_factura_id">
                        <div class="alert alert-info">Saldo Pendiente: $<span id="recibo_saldo_display"></span></div>
                        <div class="mb-3"><label>Fecha</label><input type="date" name="fecha" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="mb-3"><label>Monto</label><input type="number" step="0.01" name="monto"
                                id="recibo_monto" class="form-control" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Generar
                            Recibo</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNC" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid #ffc107;">
                <div class="modal-header">
                    <h5 class="modal-title">Nueva Nota de Crédito</h5><button type="button"
                        class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="accion" value="nota_credito">
                        <input type="hidden" name="factura_id" id="nc_factura_id">
                        <div class="alert alert-warning">Saldo Pendiente: $<span id="nc_saldo_display"></span></div>
                        <div class="mb-3"><label>Fecha</label><input type="date" name="fecha" class="form-control"
                                value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="mb-3"><label>Monto</label><input type="number" step="0.01" name="monto" id="nc_monto"
                                class="form-control" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-warning">Generar
                            NC</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalRelacionados" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background-color: var(--panel-bg); border: 1px solid var(--neon-blue);">
                <div class="modal-header">
                    <h5 class="modal-title">Documentos Relacionados</h5><button type="button"
                        class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm text-white">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                                <th>ID</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tabla_relacionados"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Definir variable global para uso en main.js
        window.esAdmin = <?php echo es_admin() ? 'true' : 'false'; ?>;
    </script>

<?php endif; ?>

<?php require_once 'footer.php'; ?>