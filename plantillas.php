<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center py-2">
                    <h4 class="mb-0">Plantillas de Presupuesto</h4>
                    <button class="btn btn-primary" id="btnNuevaPlantilla">
                        <i class="fas fa-plus"></i> Nueva Plantilla
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Plantillas -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Plantillas Disponibles</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="listaPlantillas">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detalle de Plantilla</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-primary" id="btnAgregarItem">
                            <i class="fas fa-plus"></i> Agregar Item
                        </button>
                        <button class="btn btn-sm btn-success" id="btnGuardarPlantilla">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <form id="formPlantilla">
                        <input type="hidden" id="plantillaId">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Descripción</label>
                                <input type="text" class="form-control" id="descripcion">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Condiciones de Pago</label>
                                <textarea class="form-control" id="condicionesPago" rows="2"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Plazo de Entrega</label>
                                <textarea class="form-control" id="plazoEntrega" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea class="form-control" id="notas" rows="2"></textarea>
                            </div>
                        </div>

                        <!-- Grilla de Items -->
                        <div id="gridItems" style="height: 400px;" class="ag-theme-alpine"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buscar Material -->
<div class="modal fade" id="modalBuscarMaterial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buscar Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="buscarMaterial" placeholder="Buscar por código o descripción...">
                </div>
                <div id="gridMateriales" style="height: 300px;" class="ag-theme-alpine"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnSeleccionarMaterial">Insertar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Configuración de la grilla de items
const gridOptions = {
    columnDefs: [
        {
            headerCheckboxSelection: true,
            checkboxSelection: true,
            width: 40,
            pinned: 'left'
        },
        {
            field: 'orden',
            headerName: 'Orden',
            width: 80,
            editable: true,
            valueParser: params => Number(params.newValue)
        },
        {
            field: 'categoria_item',
            headerName: 'Categoría',
            width: 120,
            editable: true
        },
        {
            field: 'cantidad',
            headerName: 'Cantidad',
            width: 100,
            editable: true,
            type: 'numericColumn',
            valueParser: params => Number(params.newValue)
        },
        {
            field: 'descripcion',
            headerName: 'Descripción',
            flex: 1,
            editable: true
        },
        {
            field: 'precio_usd',
            headerName: 'Precio USD',
            width: 120,
            editable: true,
            type: 'numericColumn',
            valueFormatter: params => params.value ? `U$D ${params.value.toFixed(2)}` : ''
        },
        {
            field: 'precio_ars',
            headerName: 'Precio ARS',
            width: 120,
            editable: true,
            type: 'numericColumn',
            valueFormatter: params => params.value ? `$ ${params.value.toFixed(2)}` : ''
        },
        {
            field: 'descuento1',
            headerName: 'Desc 1 %',
            width: 100,
            editable: true,
            type: 'numericColumn'
        },
        {
            field: 'descuento2',
            headerName: 'Desc 2 %',
            width: 100,
            editable: true,
            type: 'numericColumn'
        },
        {
            field: 'subtotal_grupo',
            headerName: 'Subtotal',
            width: 90,
            editable: true,
            cellRenderer: params => {
                return `<div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                        ${params.value ? 'checked' : ''} 
                        onclick="toggleSubtotal(${params.node.id})">
                </div>`;
            }
        }
    ],
    rowData: [],
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true
    },
    rowSelection: 'multiple',
    suppressRowClickSelection: true
};

// Configuración de la grilla de materiales
const gridMaterialesOptions = {
    columnDefs: [
        {
            headerCheckboxSelection: true,
            checkboxSelection: true,
            width: 40
        },
        { field: 'codigo', headerName: 'Código', width: 120 },
        { field: 'descripcion', headerName: 'Descripción', flex: 1 },
        {
            field: 'precio_usd',
            headerName: 'Precio USD',
            width: 120,
            valueFormatter: params => params.value ? `U$D ${params.value.toFixed(2)}` : ''
        },
        {
            field: 'precio_ars',
            headerName: 'Precio ARS',
            width: 120,
            valueFormatter: params => params.value ? `$ ${params.value.toFixed(2)}` : ''
        }
    ],
    rowData: [],
    rowSelection: 'multiple',
    defaultColDef: {
        sortable: true,
        filter: true
    }
};

// Inicializar grillas
document.addEventListener('DOMContentLoaded', () => {
    new agGrid.Grid(document.querySelector('#gridItems'), gridOptions);
    new agGrid.Grid(document.querySelector('#gridMateriales'), gridMaterialesOptions);
    cargarPlantillas();
});

// Cargar plantillas
async function cargarPlantillas() {
    try {
        const response = await fetch('api/plantillas.php');
        const plantillas = await response.json();
        
        const lista = document.getElementById('listaPlantillas');
        lista.innerHTML = plantillas.map(p => `
            <a href="#" class="list-group-item list-group-item-action" 
               onclick="cargarPlantilla(${p.id})">
                ${p.nombre}
                <small class="d-block text-muted">${p.descripcion || ''}</small>
            </a>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar plantillas');
    }
}

// Cargar plantilla
async function cargarPlantilla(id) {
    try {
        const response = await fetch(`api/plantillas.php?id=${id}`);
        const plantilla = await response.json();
        
        document.getElementById('plantillaId').value = plantilla.id;
        document.getElementById('nombre').value = plantilla.nombre;
        document.getElementById('descripcion').value = plantilla.descripcion;
        document.getElementById('condicionesPago').value = plantilla.condiciones_pago;
        document.getElementById('plazoEntrega').value = plantilla.plazo_entrega;
        document.getElementById('notas').value = plantilla.notas;
        
        gridOptions.api.setRowData(plantilla.items || []);
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar la plantilla');
    }
}

// Nueva plantilla
document.getElementById('btnNuevaPlantilla').addEventListener('click', () => {
    document.getElementById('formPlantilla').reset();
    document.getElementById('plantillaId').value = '';
    gridOptions.api.setRowData([]);
});

// Guardar plantilla
document.getElementById('btnGuardarPlantilla').addEventListener('click', async () => {
    if (!document.getElementById('nombre').value) {
        mostrarError('El nombre es obligatorio');
        return;
    }

    const plantilla = {
        id: document.getElementById('plantillaId').value,
        nombre: document.getElementById('nombre').value,
        descripcion: document.getElementById('descripcion').value,
        condiciones_pago: document.getElementById('condicionesPago').value,
        plazo_entrega: document.getElementById('plazoEntrega').value,
        notas: document.getElementById('notas').value,
        items: []
    };

    gridOptions.api.forEachNode(node => {
        if (node.data) {
            plantilla.items.push(node.data);
        }
    });

    try {
        const response = await fetch('api/plantillas.php', {
            method: plantilla.id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(plantilla)
        });

        const data = await response.json();
        
        if (data.success) {
            mostrarExito(data.mensaje);
            cargarPlantillas();
            if (!plantilla.id) {
                cargarPlantilla(data.id);
            }
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al guardar la plantilla');
    }
});

// Agregar items
document.getElementById('btnAgregarItem').addEventListener('click', async () => {
    try {
        const response = await fetch('api/materiales.php');
        const materiales = await response.json();
        gridMaterialesOptions.api.setRowData(materiales);
        new bootstrap.Modal(document.getElementById('modalBuscarMaterial')).show();
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar materiales');
    }
});

// Seleccionar materiales
document.getElementById('btnSeleccionarMaterial').addEventListener('click', () => {
    const selectedRows = gridMaterialesOptions.api.getSelectedRows();
    if (selectedRows.length === 0) {
        mostrarError('Seleccione al menos un material');
        return;
    }

    const items = selectedRows.map(material => ({
        material_id: material.id,
        orden: gridOptions.api.getDisplayedRowCount() + 1,
        cantidad: 1,
        descripcion: material.descripcion,
        precio_usd: material.precio_usd,
        precio_ars: material.precio_ars,
        descuento1: 0,
        descuento2: 0,
        subtotal_grupo: false
    }));
    
    gridOptions.api.applyTransaction({ add: items });
    bootstrap.Modal.getInstance(document.getElementById('modalBuscarMaterial')).hide();
});

// Toggle subtotal
function toggleSubtotal(nodeId) {
    const node = gridOptions.api.getRowNode(nodeId);
    node.setDataValue('subtotal_grupo', !node.data.subtotal_grupo);
}

// Funciones de notificación
function mostrarExito(mensaje) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: mensaje,
        timer: 2000,
        showConfirmButton: false
    });
}

function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje
    });
}
</script>

<?php include 'includes/footer.php'; ?> 