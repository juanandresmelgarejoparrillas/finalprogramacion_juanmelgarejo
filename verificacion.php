<?php
// verificacion.php
require_once 'db.php';
require_once 'header.php';

// Contadores
$cnt_users = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE status=1")->fetch_assoc()['c'];
$cnt_cli = $conn->query("SELECT COUNT(*) as c FROM clientes WHERE status=1")->fetch_assoc()['c'];
$cnt_prov = $conn->query("SELECT COUNT(*) as c FROM proveedores WHERE status=1")->fetch_assoc()['c'];
$cnt_trans = $conn->query("SELECT COUNT(*) as c FROM transacciones WHERE status=1")->fetch_assoc()['c'];

// Verificar integridad básica (ej: usuarios sin email)
$integrity_check = $conn->query("SELECT COUNT(*) as c FROM usuarios WHERE email IS NULL")->fetch_assoc()['c'];
$status_system = ($integrity_check == 0) ? "OPERATIVO" : "ADVERTENCIA";
$color_status = ($integrity_check == 0) ? "var(--neon-cyan)" : "var(--neon-pink)";
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card" style="border: 1px solid <?php echo $color_status; ?>;">
                <div class="card-header text-center" style="background-color: rgba(0,0,0,0.5);">
                    <h3 style="color: <?php echo $color_status; ?>;"><i class="fas fa-microchip"></i> DIAGNÓSTICO DEL
                        SISTEMA</h3>
                </div>
                <div class="card-body" style="font-family: 'Courier New', monospace;">
                    <p><strong>ESTADO DEL SERVIDOR:</strong> <span style="color: #0f0;">ONLINE</span></p>
                    <p><strong>BASE DE DATOS:</strong> <span style="color: #0f0;">CONECTADA</span></p>
                    <p><strong>INTEGRIDAD DE DATOS:</strong> <span
                            style="color: <?php echo $color_status; ?>;"><?php echo $status_system; ?></span></p>
                    <hr style="border-color: #333;">
                    <h5 style="color: var(--text-main);">MÉTRICAS:</h5>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-users"></i> Usuarios Activos: <span
                                class="float-end"><?php echo $cnt_users; ?></span></li>
                        <li><i class="fas fa-briefcase"></i> Clientes: <span
                                class="float-end"><?php echo $cnt_cli; ?></span></li>
                        <li><i class="fas fa-truck"></i> Proveedores: <span
                                class="float-end"><?php echo $cnt_prov; ?></span></li>
                        <li><i class="fas fa-exchange-alt"></i> Transacciones: <span
                                class="float-end"><?php echo $cnt_trans; ?></span></li>
                    </ul>
                    <hr style="border-color: #333;">
                    <div class="text-center">
                        <p class="text-muted small">System Check Complete. <?php echo date('H:i:s'); ?></p>
                        <div class="progress" style="height: 5px; background-color: #333;">
                            <div class="progress-bar" role="progressbar"
                                style="width: 100%; background-color: <?php echo $color_status; ?>;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>