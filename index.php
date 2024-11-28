<?php include 'includes/header.php'; ?>

<div class="row mb-3">
    <!-- Cotizaciones -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cotizaciones del Día</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-6">
                        <h4>Dólar Divisa</h4>
                        <h2 id="dolarDivisa" class="text-primary">$0.00</h2>
                        <small id="variacionDivisa" class="text-muted"></small>
                    </div>
                    <div class="col-md-6">
                        <h4>Dólar Billete</h4>
                        <h2 id="dolarBillete" class="text-success">$0.00</h2>
                        <small id="variacionBillete" class="text-muted"></small>
                    </div>
                </div>
                <p class="text-center mt-3">
                    <small class="text-muted">Última actualización: <span id="ultimaActualizacion"></span></small>
                </p>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Estadísticas</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title">Presupuestos del Mes</h6>
                                <h3 id="presupuestosMes" class="mb-0">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title">Presupuestos Aprobados</h6>
                                <h3 id="presupuestosAprobados" class="mb-0">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h6 class="card-title">Clientes Activos</h6>
                                <h3 id="clientesActivos" class="mb-0">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h6 class="card-title">Materiales</h6>
                                <h3 id="totalMateriales" class="mb-0">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Últimos Presupuestos -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Últimos Presupuestos</h5>
                <a href="presupuestos.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Nuevo
                </a>
            </div>
            <div class="card-body">
                <div id="gridUltimosPresupuestos" class="ag-theme-alpine" style="height: 300px;"></div>
            </div>
        </div>
    </div>

    <!-- Presupuestos por Vencer -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Presupuestos por Vencer</h5>
            </div>
            <div class="card-body">
                <div id="presupuestosVencer"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Configuración de la grilla de últimos presupuestos
    const columnDefs = [
        { 
            field: 'numero', 
            headerName: 'Número',
            width: 120
        },
        { field: 'cliente', headerName: 'Cliente' },
        { 
            field: 'fecha', 
            headerName: 'Fecha',
            valueFormatter: params => formatDate(params.value),
            width: 120
        },
        { 
            field: 'total_ars', 
            headerName: 'Total ARS',
            valueFormatter: params => formatCurrency(params.value)
        },
        { 
            field: 'estado',
            headerName: 'Estado',
            cellRenderer: params => {
                const estados = {
                    'borrador': 'secondary',
                    'enviado': 'primary',
                    'aprobado': 'success',
                    'rechazado': 'danger',
                    'vencido': 'warning'
                };
                return `<span class="badge bg-${estados[params.value]}">${params.value.toUpperCase()}</span>`;
            },
            width: 120
        }
    ];

    const gridOptions = {
        ...gridCommonOptions,
        columnDefs: columnDefs,
        onRowClicked: params => {
            window.location.href = `presupuesto.php?id=${params.data.id}`;
        }
    };

    new agGrid.Grid(document.querySelector('#gridUltimosPresupuestos'), gridOptions);

    // Cargar datos
    await Promise.all([
        cargarCotizaciones(),
        cargarEstadisticas(),
        cargarUltimosPresupuestos(),
        cargarPresupuestosVencer()
    ]);
});

async function cargarCotizaciones() {
    try {
        const response = await fetch('api/cotizaciones.php?actualizar=true');
        const data = await response.json();
        
        document.getElementById('dolarDivisa').textContent = formatCurrency(data.valor_divisa);
        document.getElementById('dolarBillete').textContent = formatCurrency(data.valor_billete);
        document.getElementById('ultimaActualizacion').textContent = formatDate(data.created_at, true);

        if (data.variacion_divisa) {
            const variacionDivisa = document.getElementById('variacionDivisa');
            variacionDivisa.textContent = `${data.variacion_divisa > 0 ? '+' : ''}${data.variacion_divisa.toFixed(2)}%`;
            variacionDivisa.className = `text-${data.variacion_divisa > 0 ? 'danger' : 'success'}`;
        }

        if (data.variacion_billete) {
            const variacionBillete = document.getElementById('variacionBillete');
            variacionBillete.textContent = `${data.variacion_billete > 0 ? '+' : ''}${data.variacion_billete.toFixed(2)}%`;
            variacionBillete.className = `text-${data.variacion_billete > 0 ? 'danger' : 'success'}`;
        }
    } catch (error) {
        console.error('Error al cargar cotizaciones:', error);
    }
}

async function cargarEstadisticas() {
    try {
        const response = await fetch('api/estadisticas.php');
        const data = await response.json();
        
        document.getElementById('presupuestosMes').textContent = data.presupuestos_mes;
        document.getElementById('presupuestosAprobados').textContent = data.presupuestos_aprobados;
        document.getElementById('clientesActivos').textContent = data.clientes_activos;
        document.getElementById('totalMateriales').textContent = data.total_materiales;
    } catch (error) {
        console.error('Error al cargar estadísticas:', error);
    }
}

async function cargarUltimosPresupuestos() {
    try {
        const response = await fetch('api/presupuestos.php?ultimos=10');
        const data = await response.json();
        gridOptions.api.setRowData(data);
    } catch (error) {
        console.error('Error al cargar últimos presupuestos:', error);
    }
}

async function cargarPresupuestosVencer() {
    try {
        const response = await fetch('api/presupuestos.php?por_vencer=true');
        const presupuestos = await response.json();
        
        const container = document.getElementById('presupuestosVencer');
        container.innerHTML = '';
        
        if (presupuestos.length === 0) {
            container.innerHTML = '<p class="text-muted text-center">No hay presupuestos por vencer</p>';
            return;
        }
        
        presupuestos.forEach(p => {
            const diasRestantes = Math.ceil((new Date(p.fecha_validez) - new Date()) / (1000 * 60 * 60 * 24));
            const card = document.createElement('div');
            card.className = 'card mb-2';
            card.innerHTML = `
                <div class="card-body">
                    <h6 class="card-title">${p.numero}</h6>
                    <p class="card-text">
                        ${p.cliente}<br>
                        <small class="text-danger">Vence en ${diasRestantes} días</small>
                    </p>
                    <a href="presupuesto.php?id=${p.id}" class="btn btn-sm btn-primary">Ver</a>
                </div>
            `;
            container.appendChild(card);
        });
    } catch (error) {
        console.error('Error al cargar presupuestos por vencer:', error);
    }
}
</script>

<?php include 'includes/footer.php'; ?> 