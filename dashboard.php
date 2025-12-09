<?php
// dashboard.php - Panel de Control Principal
// Esta es la pantalla principal que ve el usuario al entrar. Muestra un resumen financiero.

require_once 'config.php'; // Conexión a Base de Datos
require_once 'auth.php';   // Herramientas de seguridad
verificar_autenticacion(); // ¡Alto! Solo usuarios registrados pueden ver esto.

require_once 'header.php'; // Cargamos la parte de arriba de la página (menú, logos, etc.)

// --- CÁLCULO DE FINANZAS (Resumen de dinero) ---
// Empezamos asumiendo que no hay movimientos (0 pesos).
$ingresos = 0;
$egresos = 0;

// Paso 1: Revisar si se usó el filtro de fechas
// Si el usuario eligió "Desde" y "Hasta", filtramos los cálculos.
$f_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$f_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

$where_fecha = ""; // Esta variable guardará la condición extra para la búsqueda (SQL).
if (!empty($f_desde) && !empty($f_hasta)) {
    // Si hay fechas, le decimos a la base de datos: "Solo busca entre esta fecha y esta otra".
    $where_fecha = " AND fecha BETWEEN '$f_desde' AND '$f_hasta'";
}

// Paso 2: Calcular INGRESOS (Dinero que entró)
// Sumamos todos los 'recibos' de 'clientes' que estén confirmados (estado = 1).
$sql_ingresos = "SELECT SUM(monto) as total FROM transacciones WHERE tipo = 'recibo' AND tipo_entidad = 'cliente' AND estado = 1" . $where_fecha;
$res_ingresos = $conexion->query($sql_ingresos);
if ($row = $res_ingresos->fetch_assoc()) {
    $ingresos = $row['total'] ?? 0; // Si el resultado es vacío, ponemos 0.
}

// Paso 3: Calcular EGRESOS (Dinero que salió)
// Aquí sumamos dos cosas:
// a) Pagos a proveedores (recibos de proveedores).
// b) Gastos generales del negocio (luz, alquiler, etc).
$sql_egresos = "SELECT SUM(monto) as total FROM transacciones WHERE ((tipo = 'recibo' AND tipo_entidad = 'proveedor') OR tipo = 'gasto') AND estado = 1" . $where_fecha;
$res_egresos = $conexion->query($sql_egresos);
if ($row = $res_egresos->fetch_assoc()) {
    $egresos = $row['total'] ?? 0;
}
?>

<div class="row mb-4">
    <div class="col-12 text-center">
        <h1 class="display-4" style="color: var(--neon-cyan); text-shadow: 0 0 10px rgba(0,243,255,0.5);">BIENVENIDO
        </h1>
        <p class="lead text-white-50">Sistema de Gestión Integral</p>
    </div>
</div>

<!-- Filtro de Fechas -->
<div class="row justify-content-center mb-4">
    <div class="col-md-8">
        <div class="card" style="background-color: var(--card-bg); border: 1px solid var(--neon-cyan);">
            <div class="card-body">
                <form method="GET" class="row align-items-end justify-content-center">
                    <div class="col-md-4">
                        <label class="text-white">Desde</label>
                        <input type="date" name="fecha_desde" class="form-control" value="<?php echo $f_desde; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="text-white">Hasta</label>
                        <input type="date" name="fecha_hasta" class="form-control" value="<?php echo $f_hasta; ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-info w-100"><i class="fas fa-filter"></i>
                            Filtrar</button>
                    </div>
                    <?php if (!empty($f_desde)): ?>
                        <div class="col-md-1">
                            <a href="dashboard.php" class="btn btn-secondary w-100" title="Limpiar"><i
                                    class="fas fa-times"></i></a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header text-center">
                RESUMEN FINANCIERO
            </div>
            <div class="card-body">
                <canvas id="financeChart" data-ingresos="<?php echo $ingresos; ?>"
                    data-egresos="<?php echo $egresos; ?>"></canvas>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>