<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Versiones del Presupuesto</h4>
                    <div class="btn-group">
                        <button class="btn btn-outline-primary" id="btnComparar">
                            <i class="bi bi-file-diff"></i> Comparar Versiones
                        </button>
                        <a href="presupuestos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Árbol de Versiones</h5>
                </div>
                <div class="card-body">
                    <div id="arbolVersiones" class="d-flex flex-wrap gap-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <!-- Lista de Versiones -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Versiones Disponibles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="listaVersiones">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <!-- Detalles de la Versión -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detalles de la Versión</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cliente</label>
                            <p class="form-control-static" id="clienteNombre"></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha</label>
                            <p class="form-control-static" id="fecha"></p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Versión</label>
                            <p class="form-control-static" id="version"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Condiciones de Pago</label>
                            <p class="form-control-static" id="condicionesPago"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Plazo de Entrega</label>
                            <p class="form-control-static" id="plazoEntrega"></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Notas</label>
                            <p class="form-control-static" id="notas"></p>
                        </div>
                    </div>

                    <!-- Grilla de Items -->
                    <div id="gridItems" class="ag-theme-alpine" style="height: 400px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Comparar Versiones -->
<div class="modal fade" id="modalComparar" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Comparar Versiones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Versión Original</label>
                        <select class="form-select" id="versionOriginal"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Versión a Comparar</label>
                        <select class="form-select" id="versionComparar"></select>
                    </div>
                </div>
                
                <!-- Resumen de Cambios -->
                <div class="alert alert-info mb-3">
                    <h6 class="alert-heading">Resumen de Cambios</h6>
                    <div id="resumenCambios"></div>
                </div>

                <!-- Tabla de Comparación -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Campo</th>
                                <th>Versión Original</th>
                                <th>Versión Comparada</th>
                                <th>Diferencia</th>
                            </tr>
                        </thead>
                        <tbody id="tablaComparacion">
                            <!-- Se llena dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- Comparación de Items -->
                <div class="mt-3">
                    <h6>Comparación de Items</h6>
                    <div id="gridComparacion" class="ag-theme-alpine" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración de la grilla
const gridOptions = {
    columnDefs: [
        {
            field: 'categoria_item',
            headerName: 'Categoría',
            width: 150
        },
        {
            field: 'descripcion',
            headerName: 'Descripción',
            flex: 1
        },
        {
            field: 'cantidad',
            headerName: 'Cantidad',
            width: 100,
            type: 'numericColumn'
        },
        {
            field: 'precio_usd',
            headerName: 'Precio USD',
            width: 120,
            type: 'numericColumn',
            valueFormatter: params => params.value ? `U$D ${params.value.toFixed(2)}` : ''
        },
        {
            field: 'precio_ars',
            headerName: 'Precio ARS',
            width: 120,
            type: 'numericColumn',
            valueFormatter: params => params.value ? `$ ${params.value.toFixed(2)}` : ''
        },
        {
            field: 'descuento1',
            headerName: 'Desc 1 %',
            width: 100,
            type: 'numericColumn'
        },
        {
            field: 'descuento2',
            headerName: 'Desc 2 %',
            width: 100,
            type: 'numericColumn'
        }
    ],
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true
    },
    rowData: []
};

// Configuración de la grilla de comparación
const gridComparacionOptions = {
    columnDefs: [
        {
            field: 'descripcion',
            headerName: 'Descripción',
            flex: 1
        },
        {
            field: 'estado',
            headerName: 'Estado',
            width: 120,
            cellStyle: params => {
                switch (params.value) {
                    case 'Nuevo': return { backgroundColor: '#d4edda' };
                    case 'Eliminado': return { backgroundColor: '#f8d7da' };
                    case 'Modificado': return { backgroundColor: '#fff3cd' };
                    default: return null;
                }
            }
        },
        {
            field: 'cambios',
            headerName: 'Cambios',
            flex: 1
        }
    ],
    defaultColDef: {
        sortable: true,
        filter: true
    },
    rowData: []
};

// Cargar versiones
async function cargarVersiones() {
    const params = new URLSearchParams(window.location.search);
    const presupuestoId = params.get('id');
    
    try {
        const response = await fetch(`api/presupuestos.php?id=${presupuestoId}`);
        const presupuesto = await response.json();
        
        if (presupuesto.versiones) {
            const lista = document.getElementById('listaVersiones');
            lista.innerHTML = presupuesto.versiones.map(v => `
                <a href="#" class="list-group-item list-group-item-action" 
                   onclick="cargarVersion(${v.id})">
                    Versión ${v.version}
                    <small class="d-block text-muted">
                        ${new Date(v.fecha).toLocaleDateString()}
                    </small>
                </a>
            `).join('');

            // Llenar selectores de comparación
            const selectOriginal = document.getElementById('versionOriginal');
            const selectComparar = document.getElementById('versionComparar');
            const options = presupuesto.versiones.map(v => `
                <option value="${v.id}">Versión ${v.version} (${new Date(v.fecha).toLocaleDateString()})</option>
            `).join('');
            
            selectOriginal.innerHTML = options;
            selectComparar.innerHTML = options;
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar versiones');
    }
}

// Cargar versión específica
async function cargarVersion(id) {
    try {
        const response = await fetch(`api/presupuestos.php?id=${id}`);
        const presupuesto = await response.json();
        
        // Llenar datos
        document.getElementById('clienteNombre').textContent = presupuesto.cliente_nombre;
        document.getElementById('fecha').textContent = new Date(presupuesto.fecha).toLocaleDateString();
        document.getElementById('version').textContent = `Versión ${presupuesto.version}`;
        document.getElementById('condicionesPago').textContent = presupuesto.condiciones_pago;
        document.getElementById('plazoEntrega').textContent = presupuesto.plazo_entrega;
        document.getElementById('notas').textContent = presupuesto.notas;
        
        // Cargar items
        gridOptions.api.setRowData(presupuesto.items || []);
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar la versión');
    }
}

// Función para generar árbol de versiones
function generarArbolVersiones(versiones) {
    const arbol = document.getElementById('arbolVersiones');
    arbol.innerHTML = '';

    // Ordenar versiones por fecha
    versiones.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

    // Crear nodos
    versiones.forEach((version, index) => {
        const nodo = document.createElement('div');
        nodo.className = 'card border-primary';
        nodo.style.minWidth = '200px';
        
        nodo.innerHTML = `
            <div class="card-body p-2">
                <h6 class="card-title mb-1">Versión ${version.version}</h6>
                <p class="card-text small mb-1">${new Date(version.fecha).toLocaleDateString()}</p>
                <button class="btn btn-sm btn-outline-primary" onclick="cargarVersion(${version.id})">
                    Ver Detalles
                </button>
            </div>
        `;

        // Agregar línea conectora
        if (index < versiones.length - 1) {
            const conector = document.createElement('div');
            conector.className = 'border-primary';
            conector.style.borderRight = '2px solid';
            conector.style.height = '20px';
            conector.style.margin = '0 auto';
            nodo.appendChild(conector);
        }

        arbol.appendChild(nodo);
    });
}

// Función para comparar versiones mejorada
async function compararVersiones() {
    const versionOriginalId = document.getElementById('versionOriginal').value;
    const versionCompararId = document.getElementById('versionComparar').value;
    
    try {
        const [original, comparar] = await Promise.all([
            fetch(`api/presupuestos.php?id=${versionOriginalId}`).then(r => r.json()),
            fetch(`api/presupuestos.php?id=${versionCompararId}`).then(r => r.json())
        ]);

        // Generar resumen de cambios
        const resumen = [];
        let itemsNuevos = 0;
        let itemsEliminados = 0;
        let itemsModificados = 0;

        // Comparar items
        const itemsComparados = [];
        const itemsOriginales = new Map(original.items.map(i => [i.descripcion, i]));
        const itemsComparar = new Map(comparar.items.map(i => [i.descripcion, i]));

        // Buscar items eliminados
        itemsOriginales.forEach((item, desc) => {
            if (!itemsComparar.has(desc)) {
                itemsComparados.push({
                    descripcion: desc,
                    estado: 'Eliminado',
                    cambios: 'Item eliminado'
                });
                itemsEliminados++;
            }
        });

        // Buscar items nuevos y modificados
        itemsComparar.forEach((item, desc) => {
            if (!itemsOriginales.has(desc)) {
                itemsComparados.push({
                    descripcion: desc,
                    estado: 'Nuevo',
                    cambios: 'Item nuevo'
                });
                itemsNuevos++;
            } else {
                const itemOriginal = itemsOriginales.get(desc);
                const cambios = [];
                
                if (item.cantidad !== itemOriginal.cantidad) {
                    cambios.push(`Cantidad: ${itemOriginal.cantidad} → ${item.cantidad}`);
                }
                if (item.precio_usd !== itemOriginal.precio_usd) {
                    cambios.push(`Precio USD: ${itemOriginal.precio_usd} → ${item.precio_usd}`);
                }
                if (item.precio_ars !== itemOriginal.precio_ars) {
                    cambios.push(`Precio ARS: ${itemOriginal.precio_ars} → ${item.precio_ars}`);
                }
                if (item.descuento1 !== itemOriginal.descuento1) {
                    cambios.push(`Descuento 1: ${itemOriginal.descuento1}% → ${item.descuento1}%`);
                }
                if (item.descuento2 !== itemOriginal.descuento2) {
                    cambios.push(`Descuento 2: ${itemOriginal.descuento2}% → ${item.descuento2}%`);
                }

                if (cambios.length > 0) {
                    itemsComparados.push({
                        descripcion: desc,
                        estado: 'Modificado',
                        cambios: cambios.join(', ')
                    });
                    itemsModificados++;
                }
            }
        });

        // Mostrar resumen
        document.getElementById('resumenCambios').innerHTML = `
            <ul class="mb-0">
                <li>${itemsNuevos} items nuevos</li>
                <li>${itemsEliminados} items eliminados</li>
                <li>${itemsModificados} items modificados</li>
                <li>Diferencia total USD: U$D ${(calcularTotal(comparar.items).usd - calcularTotal(original.items).usd).toFixed(2)}</li>
                <li>Diferencia total ARS: $ ${(calcularTotal(comparar.items).ars - calcularTotal(original.items).ars).toFixed(2)}</li>
            </ul>
        `;

        // Actualizar grilla de comparación
        if (!gridComparacionOptions.api) {
            new agGrid.Grid(document.querySelector('#gridComparacion'), gridComparacionOptions);
        }
        gridComparacionOptions.api.setRowData(itemsComparados);

        // Comparar campos principales
        const tabla = document.getElementById('tablaComparacion');
        tabla.innerHTML = '';
        
        compararCampo('Condiciones de Pago', original.condiciones_pago, comparar.condiciones_pago);
        compararCampo('Plazo de Entrega', original.plazo_entrega, comparar.plazo_entrega);
        compararCampo('Notas', original.notas, comparar.notas);
        
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al comparar versiones');
    }
}

// Función auxiliar para comparar campos
function compararCampo(nombre, valorOriginal, valorComparar, diferencia = null) {
    const tabla = document.getElementById('tablaComparacion');
    const row = document.createElement('tr');
    
    let clase = '';
    if (valorOriginal !== valorComparar) {
        clase = 'table-warning';
    }
    
    row.innerHTML = `
        <td>${nombre}</td>
        <td>${valorOriginal || ''}</td>
        <td class="${clase}">${valorComparar || ''}</td>
        <td>${diferencia || ''}</td>
    `;
    
    tabla.appendChild(row);
}

// Calcular totales
function calcularTotal(items) {
    return items.reduce((acc, item) => {
        const descuento1 = 1 - (item.descuento1 || 0) / 100;
        const descuento2 = 1 - (item.descuento2 || 0) / 100;
        
        acc.usd += (item.precio_usd || 0) * (item.cantidad || 0) * descuento1 * descuento2;
        acc.ars += (item.precio_ars || 0) * (item.cantidad || 0) * descuento1 * descuento2;
        
        return acc;
    }, { usd: 0, ars: 0 });
}

// Event Listeners
document.getElementById('btnComparar').addEventListener('click', () => {
    new bootstrap.Modal(document.getElementById('modalComparar')).show();
});

document.getElementById('versionOriginal').addEventListener('change', compararVersiones);
document.getElementById('versionComparar').addEventListener('change', compararVersiones);

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    new agGrid.Grid(document.querySelector('#gridItems'), gridOptions);
    new agGrid.Grid(document.querySelector('#gridComparacion'), gridComparacionOptions);
    
    const params = new URLSearchParams(window.location.search);
    if (params.get('id')) {
        cargarVersiones().then(() => {
            // Cargar la primera versión por defecto
            const primerVersionId = document.querySelector('#listaVersiones a')?.getAttribute('onclick')?.match(/\d+/)?.[0];
            if (primerVersionId) {
                cargarVersion(primerVersionId);
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?> 