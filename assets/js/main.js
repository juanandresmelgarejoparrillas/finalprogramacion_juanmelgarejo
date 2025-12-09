/* assets/js/main.js */
console.log('Sistema Futuro JS loaded');

// --- FUNCIÓN DE FILTRADO (BUSCADOR CLIENTES/PROVEEDORES) ---
// Se ejecuta cuando el usuario escribe en el input "buscador".
function filtrarLista() {
    var input, filter, lista, items, span, i, txtValue;
    input = document.getElementById('buscador');
    if (!input) return;

    filter = input.value.toUpperCase(); // Convertir a mayúsculas para comparar
    lista = document.getElementById("lista-entidades");
    items = lista.getElementsByClassName('item-entidad');

    // Recorrer todos los elementos de la lista y ocultar los que no coinciden
    for (i = 0; i < items.length; i++) {
        span = items[i].getElementsByTagName("span")[0];
        txtValue = span.textContent || span.innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            items[i].style.display = ""; // Mostrar
        } else {
            items[i].style.display = "none"; // Ocultar
        }
    }
}

// --- MODALES DE TRANSACCIONES ---

// Abrir Modal de Recibo (Pago)
// Pre-carga el ID de la factura y valida que el monto no supere el saldo.
function abrirRecibo(id, saldo) {
    document.getElementById('recibo_factura_id').value = id;
    document.getElementById('recibo_saldo_display').innerText = saldo;
    document.getElementById('recibo_monto').max = saldo; // Tope máximo
    document.getElementById('recibo_monto').value = saldo; // Valor por defecto: Total de la deuda
    new bootstrap.Modal(document.getElementById('modalRecibo')).show();
}

// Abrir Modal de Nota de Crédito (Descuento/Devolución)
// Similar al recibo, pero para descontar deuda sin pago de dinero (ej: error facturación).
function abrirNC(id, saldo) {
    document.getElementById('nc_factura_id').value = id;
    document.getElementById('nc_saldo_display').innerText = saldo;
    document.getElementById('nc_monto').max = saldo;
    document.getElementById('nc_monto').value = saldo;
    new bootstrap.Modal(document.getElementById('modalNC')).show();
}

// Ver Documentos Relacionados (AJAX)
// Muestra qué recibos o notas de crédito están vinculados a una factura específica.
function verRelacionados(id) {
    // window.esAdmin se define en el PHP (transacciones.php)
    fetch('transacciones.php?ajax_relacionados=' + id)
        .then(response => response.json())
        .then(data => {
            let tbody = document.getElementById('tabla_relacionados');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">No hay documentos relacionados.</td></tr>';
            } else {
                // Construir filas de la tabla dinámicamente
                data.forEach(doc => {
                    let tipo = doc.tipo.replace('_', ' ').toUpperCase();

                    // Si es Admin, mostrar botón de borrar
                    let btnBorrar = '';
                    if (window.esAdmin) {
                        btnBorrar = `<form method="POST" style="display:inline;" onsubmit="return confirm('¿Borrar este documento? Se restaurará el saldo.')">
                        <input type="hidden" name="accion" value="borrar_transaccion">
                        <input type="hidden" name="id" value="${doc.id}">
                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>`;
                    }

                    tbody.innerHTML += `<tr><td>${doc.fecha}</td><td>${tipo}</td><td>$${doc.monto}</td><td>#${doc.id}</td><td>${btnBorrar}</td></tr>`;
                });
            }
            new bootstrap.Modal(document.getElementById('modalRelacionados')).show();
        });
}

// Dashboard: Chart
document.addEventListener("DOMContentLoaded", function () {
    const chartCanvas = document.getElementById('financeChart');
    if (chartCanvas) {
        const ingresos = chartCanvas.dataset.ingresos;
        const egresos = chartCanvas.dataset.egresos;

        new Chart(chartCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Egresos'],
                datasets: [{
                    data: [ingresos, egresos],
                    backgroundColor: ['#00f3ff', '#bc13fe'],
                    borderColor: ['#00f3ff', '#bc13fe'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: { color: '#000000ff' }
                    }
                }
            }
        });
    }
});
