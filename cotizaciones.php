<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Cotizaciones Actuales -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center py-2">
                    <h5 class="mb-0">Cotizaciones Actuales</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalCotizacion">
                        <i class="fas fa-plus"></i> Nueva Cotización
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Dólar Divisa</h6>
                                    <h4 class="mb-2" id="dolarDivisa">-</h4>
                                    <small class="text-muted">Variación: <span id="variacionDivisa">-</span></small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Dólar Billete</h6>
                                    <h4 class="mb-2" id="dolarBillete">-</h4>
                                    <small class="text-muted">Variación: <span id="variacionBillete">-</span></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted mt-2">
                        <small>Última actualización: <span id="ultimaActualizacion">-</span></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de Evolución -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="mb-0">Evolución del Dólar</h5>
                </div>
                <div class="card-body">
                    <canvas id="graficoEvolucion"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Cotizaciones -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h5 class="mb-0">Historial de Cotizaciones</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Dólar Divisa</th>
                                    <th>Dólar Billete</th>
                                    <th>Variación Divisa</th>
                                    <th>Variación Billete</th>
                                </tr>
                            </thead>
                            <tbody id="historialCotizaciones">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cotización -->
<div class="modal fade" id="modalCotizacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nueva Cotización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCotizacion">
                    <div class="mb-3">
                        <label class="form-label">Dólar Divisa</label>
                        <input type="number" class="form-control" id="valorDivisa" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dólar Billete</label>
                        <input type="number" class="form-control" id="valorBillete" step="0.01" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarCotizacion()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let graficoEvolucion;

// Función para formatear moneda
function formatCurrency(value) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(value);
}

// Función para formatear fecha
function formatDate(dateStr, includeTime = false) {
    const date = new Date(dateStr);
    const options = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    };
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    return date.toLocaleDateString('es-AR', options);
}

// Función para mostrar error
function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje
    });
}

// Función para mostrar éxito
function mostrarExito(mensaje) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: mensaje
    });
}

// Función para guardar cotización
async function guardarCotizacion() {
    const valorDivisa = document.getElementById('valorDivisa').value;
    const valorBillete = document.getElementById('valorBillete').value;
    
    if (!valorDivisa || !valorBillete || 
        isNaN(valorDivisa) || isNaN(valorBillete) || 
        parseFloat(valorDivisa) <= 0 || parseFloat(valorBillete) <= 0) {
        mostrarError('Por favor ingrese valores numéricos mayores a 0');
        return;
    }
    
    try {
        const response = await fetch('api/cotizaciones.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                valor_divisa: parseFloat(valorDivisa),
                valor_billete: parseFloat(valorBillete)
            })
        });
        
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        
        const data = await response.json();
        
        if (data.success) {
            mostrarExito(data.mensaje);
            document.getElementById('valorDivisa').value = '';
            document.getElementById('valorBillete').value = '';
            bootstrap.Modal.getInstance(document.getElementById('modalCotizacion')).hide();
            await cargarDatos();
        } else {
            throw new Error(data.mensaje || 'Error al guardar la cotización');
        }
    } catch (error) {
        mostrarError(error.message || 'Error al guardar la cotización');
        console.error('Error:', error);
    }
}

// Función para cargar datos
async function cargarDatos() {
    try {
        const response = await fetch('api/cotizaciones.php?historial=true');
        const data = await response.json();
        
        if (data.success && data.data) {
            // Actualizar cotizaciones actuales
            const cotizacionActual = data.data[0];
            document.getElementById('dolarDivisa').textContent = formatCurrency(cotizacionActual.valor_divisa);
            document.getElementById('dolarBillete').textContent = formatCurrency(cotizacionActual.valor_billete);
            document.getElementById('ultimaActualizacion').textContent = formatDate(cotizacionActual.fecha, true);

            // Calcular variaciones
            if (data.data.length > 1) {
                const cotizacionAnterior = data.data[1];
                const variacionDivisa = ((cotizacionActual.valor_divisa - cotizacionAnterior.valor_divisa) / cotizacionAnterior.valor_divisa * 100).toFixed(2);
                const variacionBillete = ((cotizacionActual.valor_billete - cotizacionAnterior.valor_billete) / cotizacionAnterior.valor_billete * 100).toFixed(2);
                
                document.getElementById('variacionDivisa').textContent = `${variacionDivisa}%`;
                document.getElementById('variacionBillete').textContent = `${variacionBillete}%`;
                document.getElementById('variacionDivisa').className = `text-${variacionDivisa > 0 ? 'danger' : 'success'}`;
                document.getElementById('variacionBillete').className = `text-${variacionBillete > 0 ? 'danger' : 'success'}`;
            }

            // Actualizar historial
            const historialHTML = data.data.map(cotizacion => {
                const variacionDivisa = data.data[data.data.indexOf(cotizacion) + 1] ? 
                    ((cotizacion.valor_divisa - data.data[data.data.indexOf(cotizacion) + 1].valor_divisa) / 
                    data.data[data.data.indexOf(cotizacion) + 1].valor_divisa * 100).toFixed(2) : '-';
                const variacionBillete = data.data[data.data.indexOf(cotizacion) + 1] ?
                    ((cotizacion.valor_billete - data.data[data.data.indexOf(cotizacion) + 1].valor_billete) /
                    data.data[data.data.indexOf(cotizacion) + 1].valor_billete * 100).toFixed(2) : '-';
                
                return `
                    <tr>
                        <td>${formatDate(cotizacion.fecha)}</td>
                        <td>${formatCurrency(cotizacion.valor_divisa)}</td>
                        <td>${formatCurrency(cotizacion.valor_billete)}</td>
                        <td class="text-${variacionDivisa > 0 ? 'danger' : 'success'}">${variacionDivisa}%</td>
                        <td class="text-${variacionBillete > 0 ? 'danger' : 'success'}">${variacionBillete}%</td>
                    </tr>
                `;
            }).join('');
            
            document.getElementById('historialCotizaciones').innerHTML = historialHTML;

            // Actualizar gráfico
            actualizarGrafico(data.data.reverse());
        }
    } catch (error) {
        console.error('Error al cargar datos:', error);
        mostrarError('Error al cargar los datos');
    }
}

// Función para actualizar gráfico
function actualizarGrafico(datos) {
    const ctx = document.getElementById('graficoEvolucion').getContext('2d');
    
    if (graficoEvolucion) {
        graficoEvolucion.destroy();
    }
    
    graficoEvolucion = new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.map(d => formatDate(d.fecha)),
            datasets: [
                {
                    label: 'Dólar Divisa',
                    data: datos.map(d => d.valor_divisa),
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                },
                {
                    label: 'Dólar Billete',
                    data: datos.map(d => d.valor_billete),
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
}

// Cargar datos al iniciar
document.addEventListener('DOMContentLoaded', cargarDatos);
</script>

<?php include 'includes/footer.php'; ?> 