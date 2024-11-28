<?php 
require_once 'includes/header.php';
require_once 'includes/middleware.php';

use App\Auth;
use function App\verificarPermiso;

verificarPermiso('dashboard_ver');
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4">Dashboard</h1>
    
    <!-- KPIs Principales -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Total Presupuestos</h5>
                    <h2 class="card-text" id="totalPresupuestos">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Monto Total (30 días)</h5>
                    <h2 class="card-text" id="montoTotalMes">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Tasa de Conversión</h5>
                    <h2 class="card-text" id="tasaConversion">-</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Exportaciones Flexxus (30 días)</h5>
                    <h2 class="card-text" id="exportacionesFlexxus">-</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Presupuestos por Estado</h5>
                    <canvas id="estadosChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Exportaciones Diarias</h5>
                    <canvas id="exportacionesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Materiales -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Top 5 Materiales Más Presupuestados</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Cantidad de Usos</th>
                                </tr>
                            </thead>
                            <tbody id="topMateriales">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let estadosChart = null;
    let exportacionesChart = null;

    function cargarEstadisticas() {
        fetch('api/estadisticas.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarKPIs(data.data);
                    actualizarGraficos(data.data);
                    actualizarTopMateriales(data.data.top_materiales);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarKPIs(data) {
        document.getElementById('totalPresupuestos').textContent = data.total_presupuestos;
        document.getElementById('montoTotalMes').textContent = '$ ' + new Intl.NumberFormat().format(data.monto_total_mes);
        document.getElementById('tasaConversion').textContent = data.tasa_conversion + '%';
        
        const totalExportaciones = data.exportaciones_flexxus.reduce((sum, exp) => sum + exp.cantidad, 0);
        document.getElementById('exportacionesFlexxus').textContent = totalExportaciones;
    }

    function actualizarGraficos(data) {
        // Gráfico de estados
        const estadosCtx = document.getElementById('estadosChart').getContext('2d');
        if (estadosChart) estadosChart.destroy();
        
        estadosChart = new Chart(estadosCtx, {
            type: 'pie',
            data: {
                labels: data.presupuestos_por_estado.map(e => e.nombre),
                datasets: [{
                    data: data.presupuestos_por_estado.map(e => e.cantidad),
                    backgroundColor: [
                        '#FF6384',
                        '#36A2EB',
                        '#FFCE56',
                        '#4BC0C0',
                        '#9966FF'
                    ]
                }]
            }
        });

        // Gráfico de exportaciones
        const exportacionesCtx = document.getElementById('exportacionesChart').getContext('2d');
        if (exportacionesChart) exportacionesChart.destroy();

        exportacionesChart = new Chart(exportacionesCtx, {
            type: 'line',
            data: {
                labels: data.exportaciones_flexxus.map(e => e.fecha),
                datasets: [{
                    label: 'Exportaciones',
                    data: data.exportaciones_flexxus.map(e => e.cantidad),
                    borderColor: '#36A2EB',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function actualizarTopMateriales(materiales) {
        const tbody = document.getElementById('topMateriales');
        tbody.innerHTML = '';
        
        materiales.forEach(m => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${m.codigo}</td>
                <td>${m.descripcion}</td>
                <td>${m.cantidad}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    // Cargar datos iniciales
    cargarEstadisticas();

    // Actualizar cada 5 minutos
    setInterval(cargarEstadisticas, 300000);
});
</script>

<?php require_once 'includes/footer.php'; ?> 