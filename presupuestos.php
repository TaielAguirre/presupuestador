<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Presupuestos</h5>
        <div>
            <button class="btn btn-success" id="btnNuevoPresupuesto">
                <i class="bi bi-plus-circle"></i> Nuevo Presupuesto
            </button>
            <button class="btn btn-outline-success" id="btnExportar">
                <i class="bi bi-file-earmark-excel"></i> Exportar
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <input type="text" class="form-control" id="buscarPresupuesto" placeholder="Buscar por número...">
            </div>
            <div class="col-md-3">
                <input type="text" class="form-control" id="buscarCliente" placeholder="Buscar por cliente...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="filtroEstado">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="enviado">Enviado</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="rechazado">Rechazado</option>
                    <option value="vencido">Vencido</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <input type="date" class="form-control" id="fechaDesde">
                    <input type="date" class="form-control" id="fechaHasta">
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Categoría</label>
                <select class="form-select" id="categoriaId">
                    <option value="">Sin categoría</option>
                    <!-- Se llena dinámicamente -->
                </select>
            </div>
        </div>
        <div id="gridPresupuestos" class="ag-theme-alpine" style="height: 600px;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuración de la grilla
    const columnDefs = [
        { 
            field: 'numero', 
            headerName: 'Número',
            filter: 'agTextColumnFilter',
            width: 120
        },
        { 
            field: 'fecha', 
            headerName: 'Fecha',
            valueFormatter: params => formatDate(params.value),
            filter: 'agDateColumnFilter',
            width: 120
        },
        { field: 'cliente', headerName: 'Cliente', filter: 'agTextColumnFilter' },
        { 
            field: 'total_ars', 
            headerName: 'Total ARS',
            valueFormatter: params => formatCurrency(params.value, 'ARS'),
            type: 'numericColumn'
        },
        { 
            field: 'total_usd', 
            headerName: 'Total USD',
            valueFormatter: params => formatCurrency(params.value, 'USD'),
            type: 'numericColumn'
        },
        { 
            field: 'fecha_validez', 
            headerName: 'Válido hasta',
            valueFormatter: params => formatDate(params.value),
            filter: 'agDateColumnFilter',
            width: 120
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
            filter: 'agSetColumnFilter',
            width: 120
        },
        {
            field: 'categoria_item',
            headerName: 'Categoría',
            editable: true,
            width: 150
        },
        {
            field: 'orden',
            headerName: 'Orden',
            editable: true,
            width: 100,
            type: 'numericColumn'
        },
        {
            field: 'subtotal_grupo',
            headerName: 'Subtotal',
            width: 100,
            cellRenderer: params => {
                return `<div class="form-check">
                    <input class="form-check-input" type="checkbox" 
                        ${params.value ? 'checked' : ''} 
                        onclick="toggleSubtotal(${params.node.id})">
                </div>`;
            }
        },
        {
            headerName: 'Acciones',
            width: 180,
            cellRenderer: params => `
                <button class="btn btn-sm btn-primary" onclick="editarPresupuesto('${params.data.id}')">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-info" onclick="verPresupuesto('${params.data.id}')">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="duplicarPresupuesto('${params.data.id}')">
                    <i class="bi bi-files"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="eliminarPresupuesto('${params.data.id}')" 
                        ${params.data.estado !== 'borrador' ? 'disabled' : ''}>
                    <i class="bi bi-trash"></i>
                </button>
            `
        }
    ];

    const gridOptions = {
        ...gridCommonOptions,
        columnDefs: columnDefs,
        onGridReady: cargarPresupuestos,
        rowClassRules: {
            'table-warning': params => params.data.estado === 'vencido',
            'table-danger': params => params.data.estado === 'rechazado'
        }
    };

    new agGrid.Grid(document.querySelector('#gridPresupuestos'), gridOptions);

    // Eventos de filtrado
    document.getElementById('buscarPresupuesto').addEventListener('input', e => {
        gridOptions.api.setQuickFilter(e.target.value);
    });

    document.getElementById('buscarCliente').addEventListener('input', e => {
        const filterInstance = gridOptions.api.getFilterInstance('cliente');
        filterInstance.setModel({
            type: 'contains',
            filter: e.target.value
        });
        gridOptions.api.onFilterChanged();
    });

    document.getElementById('filtroEstado').addEventListener('change', e => {
        const filterInstance = gridOptions.api.getFilterInstance('estado');
        if (e.target.value) {
            filterInstance.setModel({
                type: 'equals',
                filter: e.target.value
            });
        } else {
            filterInstance.setModel(null);
        }
        gridOptions.api.onFilterChanged();
    });

    ['fechaDesde', 'fechaHasta'].forEach(id => {
        document.getElementById(id).addEventListener('change', filtrarPorFecha);
    });

    // Botón nuevo presupuesto
    document.getElementById('btnNuevoPresupuesto').addEventListener('click', () => {
        window.location.href = 'presupuesto.php';
    });

    // Cargar categorías
    cargarCategorias();

    document.getElementById('btnVerVersiones').style.display = 'none';
});

async function cargarPresupuestos() {
    try {
        const response = await fetch('api/presupuestos.php');
        const data = await response.json();
        gridOptions.api.setRowData(data);
    } catch (error) {
        showNotification('Error', 'Error al cargar presupuestos', 'error');
    }
}

function filtrarPorFecha() {
    const desde = document.getElementById('fechaDesde').value;
    const hasta = document.getElementById('fechaHasta').value;
    
    const filterInstance = gridOptions.api.getFilterInstance('fecha');
    if (desde || hasta) {
        filterInstance.setModel({
            type: 'inRange',
            from: desde,
            to: hasta
        });
    } else {
        filterInstance.setModel(null);
    }
    gridOptions.api.onFilterChanged();
}

function editarPresupuesto(id) {
    window.location.href = `presupuesto.php?id=${id}`;
}

function verPresupuesto(id) {
    window.location.href = `presupuesto.php?id=${id}&view=true`;
}

function duplicarPresupuesto(id) {
    window.location.href = `presupuesto.php?duplicar=${id}`;
}

async function eliminarPresupuesto(id) {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch(`api/presupuestos.php?id=${id}`, {
                method: 'DELETE'
            });
            const data = await response.json();
            
            if (data.success) {
                showNotification('Éxito', data.mensaje);
                cargarPresupuestos();
            } else {
                showNotification('Error', data.mensaje, 'error');
            }
        } catch (error) {
            showNotification('Error', 'Error al eliminar el presupuesto', 'error');
        }
    }
}

// Cargar plantillas
async function cargarPlantillas() {
    try {
        const response = await fetch('api/plantillas.php');
        const plantillas = await response.json();
        
        const lista = document.getElementById('listaPlantillas');
        lista.innerHTML = plantillas.map(p => `
            <a href="#" class="list-group-item list-group-item-action" 
               onclick="usarPlantilla(${p.id})">
                ${p.nombre}
                <small class="d-block text-muted">${p.descripcion || ''}</small>
            </a>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar plantillas');
    }
}

// Usar plantilla
async function usarPlantilla(id) {
    try {
        const response = await fetch(`api/plantillas.php?id=${id}`);
        const plantilla = await response.json();
        
        // Llenar campos del presupuesto
        document.getElementById('condicionesPago').value = plantilla.condiciones_pago;
        document.getElementById('plazoEntrega').value = plantilla.plazo_entrega;
        document.getElementById('notas').value = plantilla.notas;
        
        // Agregar items de la plantilla
        const items = plantilla.items.map(item => ({
            ...item,
            id: null // Nuevo item para el presupuesto
        }));
        gridOptions.api.setRowData(items);
        
        bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarPlantilla')).hide();
        calcularTotales();
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar la plantilla');
    }
}

// Nueva versión
async function crearNuevaVersion() {
    const presupuestoId = document.getElementById('presupuestoId').value;
    if (!presupuestoId) {
        mostrarError('Debe guardar el presupuesto antes de crear una nueva versión');
        return;
    }

    try {
        // Obtener datos actuales
        const presupuesto = {
            cliente_id: document.getElementById('clienteId').value,
            fecha: document.getElementById('fecha').value,
            condiciones_pago: document.getElementById('condicionesPago').value,
            plazo_entrega: document.getElementById('plazoEntrega').value,
            notas: document.getElementById('notas').value,
            presupuesto_original_id: presupuestoId,
            items: []
        };

        // Obtener items
        gridOptions.api.forEachNode(node => {
            if (node.data) {
                presupuesto.items.push({
                    ...node.data,
                    id: null // Nueva versión = nuevos items
                });
            }
        });

        // Crear nueva versión
        const response = await fetch('api/presupuestos.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(presupuesto)
        });

        const data = await response.json();
        if (data.success) {
            mostrarExito('Nueva versión creada exitosamente');
            window.location.href = `presupuestos.php?id=${data.id}`;
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al crear nueva versión');
    }
}

// Event Listeners
document.getElementById('btnUsarPlantilla').addEventListener('click', () => {
    cargarPlantillas();
    new bootstrap.Modal(document.getElementById('modalSeleccionarPlantilla')).show();
});

document.getElementById('btnNuevaVersion').addEventListener('click', crearNuevaVersion);

// Duplicar presupuesto
async function duplicarPresupuesto() {
    const presupuestoId = document.getElementById('presupuestoId').value;
    if (!presupuestoId) {
        mostrarError('Debe guardar el presupuesto antes de duplicarlo');
        return;
    }

    try {
        const response = await fetch(`api/presupuestos.php?id=${presupuestoId}&accion=duplicar`, {
            method: 'POST'
        });

        const data = await response.json();
        if (data.success) {
            mostrarExito('Presupuesto duplicado exitosamente');
            window.location.href = `presupuestos.php?id=${data.id}`;
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al duplicar el presupuesto');
    }
}

// Event Listeners
document.getElementById('btnDuplicar').addEventListener('click', duplicarPresupuesto);

// Cargar categorías
async function cargarCategorias() {
    try {
        const response = await fetch('api/categorias.php');
        const categorias = await response.json();
        
        const select = document.getElementById('categoriaId');
        select.innerHTML = '<option value="">Sin categoría</option>' + 
            categorias.map(c => `
                <option value="${c.id}">${c.nombre}</option>
            `).join('');
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar categorías');
    }
}

// Función para calcular subtotales
function calcularSubtotales() {
    let subtotalARS = 0;
    let subtotalUSD = 0;
    let rows = [];

    gridOptions.api.forEachNode(node => {
        if (node.data) {
            const total_ars = calcularTotalItem(node.data, 'ARS');
            const total_usd = calcularTotalItem(node.data, 'USD');
            
            subtotalARS += total_ars;
            subtotalUSD += total_usd;

            rows.push(node.data);

            if (node.data.subtotal_grupo) {
                rows.push({
                    descripcion: `Subtotal ${node.data.categoria_item || ''}`,
                    precio_ars: subtotalARS,
                    precio_usd: subtotalUSD,
                    isSubtotal: true
                });
                subtotalARS = 0;
                subtotalUSD = 0;
            }
        }
    });

    // Agregar total final
    rows.push({
        descripcion: 'TOTAL FINAL',
        precio_ars: calcularTotalPresupuesto('ARS'),
        precio_usd: calcularTotalPresupuesto('USD'),
        isTotal: true
    });

    gridOptions.api.setRowData(rows);
}

// Función para alternar subtotal
function toggleSubtotal(nodeId) {
    const node = gridOptions.api.getRowNode(nodeId);
    node.setDataValue('subtotal_grupo', !node.data.subtotal_grupo);
    calcularSubtotales();
}

// Modificar la función de guardar para incluir categorías
async function guardarPresupuesto() {
    // ... existing code ...
    const presupuesto = {
        // ... existing fields ...
        categoria_id: document.getElementById('categoriaId').value || null,
        items: []
    };

    gridOptions.api.forEachNode(node => {
        if (node.data && !node.data.isSubtotal && !node.data.isTotal) {
            presupuesto.items.push({
                // ... existing fields ...
                categoria_item: node.data.categoria_item,
                orden: node.data.orden,
                subtotal_grupo: node.data.subtotal_grupo
            });
        }
    });

    // ... rest of the function ...
}

// Actualizar enlace a versiones cuando se carga un presupuesto
function actualizarEnlaceVersiones(presupuestoId) {
    const btnVerVersiones = document.getElementById('btnVerVersiones');
    if (presupuestoId) {
        btnVerVersiones.href = `versiones.php?id=${presupuestoId}`;
        btnVerVersiones.style.display = 'inline-block';
    } else {
        btnVerVersiones.style.display = 'none';
    }
}

// Modificar la función de cargar presupuesto
async function cargarPresupuesto(id) {
    try {
        const response = await fetch(`api/presupuestos.php?id=${id}`);
        const presupuesto = await response.json();
        
        // ... existing code ...
        
        actualizarEnlaceVersiones(id);
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar el presupuesto');
    }
}

// Función para exportar
async function exportarPresupuesto() {
    const presupuestoId = document.getElementById('presupuestoId').value;
    if (!presupuestoId) {
        mostrarError('Debe guardar el presupuesto antes de exportarlo');
        return;
    }

    try {
        const response = await fetch(`api/exportar.php?id=${presupuestoId}`);
        const data = await response.json();
        
        if (data.success) {
            // Crear enlace temporal para descargar
            const link = document.createElement('a');
            link.href = data.url;
            link.download = data.file;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            mostrarExito('Presupuesto exportado exitosamente');
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al exportar el presupuesto');
    }
}

// Event Listeners
document.getElementById('btnExportar').addEventListener('click', exportarPresupuesto);

// Variables globales para estados
let estadosDisponibles = [];
let estadoActual = null;

// Cargar estados
async function cargarEstados() {
    try {
        const response = await fetch('api/estados.php');
        estadosDisponibles = await response.json();
        
        // Llenar selector de estados
        const select = document.getElementById('nuevoEstado');
        select.innerHTML = estadosDisponibles.map(e => `
            <option value="${e.id}" data-requiere-comentario="${e.requiere_comentario}">
                ${e.nombre}
            </option>
        `).join('');

        // Event listener para mostrar/ocultar campo de comentario
        select.addEventListener('change', () => {
            const requiereComentario = select.selectedOptions[0].dataset.requiereComentario === '1';
            document.getElementById('divComentario').style.display = requiereComentario ? 'block' : 'none';
        });
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar estados');
    }
}

// Actualizar estado
async function actualizarEstado(estadoId, comentario = null) {
    const presupuestoId = document.getElementById('presupuestoId').value;
    if (!presupuestoId) {
        mostrarError('Debe guardar el presupuesto primero');
        return;
    }

    try {
        const response = await fetch('api/estados.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                presupuesto_id: presupuestoId,
                estado_nuevo_id: estadoId,
                comentario: comentario,
                usuario: 'Usuario Actual' // TODO: Implementar sistema de usuarios
            })
        });

        const data = await response.json();
        if (data.success) {
            mostrarExito(data.mensaje);
            cargarPresupuesto(presupuestoId);
            bootstrap.Modal.getInstance(document.getElementById('modalCambiarEstado')).hide();
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al actualizar estado');
    }
}

// Cargar historial
async function cargarHistorial(presupuestoId) {
    try {
        const response = await fetch(`api/estados.php?presupuesto_id=${presupuestoId}`);
        const historial = await response.json();
        
        const timeline = document.getElementById('timelineEstados');
        timeline.innerHTML = historial.map(h => `
            <div class="timeline-item">
                <div class="timeline-badge" style="background-color: ${h.color_nuevo}"></div>
                <div class="timeline-content">
                    <h6 class="mb-1">
                        ${h.estado_anterior ? `${h.estado_anterior} → ` : ''}${h.estado_nuevo}
                    </h6>
                    <p class="mb-1">${h.comentario || ''}</p>
                    <small class="text-muted">
                        ${h.usuario ? `Por ${h.usuario} - ` : ''}
                        ${new Date(h.fecha).toLocaleString()}
                    </small>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar historial');
    }
}

// Event Listeners
document.getElementById('btnCambiarEstado').addEventListener('click', () => {
    new bootstrap.Modal(document.getElementById('modalCambiarEstado')).show();
});

document.getElementById('btnGuardarEstado').addEventListener('click', () => {
    const estadoId = document.getElementById('nuevoEstado').value;
    const comentario = document.getElementById('comentarioEstado').value;
    actualizarEstado(estadoId, comentario);
});

document.getElementById('btnAprobar').addEventListener('click', async () => {
    const comentario = await pedirComentario('Comentario de aprobación');
    if (comentario !== null) {
        actualizarEstado(6, comentario); // 6 = Aprobado
    }
});

document.getElementById('btnRechazar').addEventListener('click', async () => {
    const comentario = await pedirComentario('Motivo del rechazo', true);
    if (comentario !== null) {
        actualizarEstado(7, comentario); // 7 = Rechazado
    }
});

// Función auxiliar para pedir comentario
async function pedirComentario(titulo, requerido = false) {
    const { value: comentario } = await Swal.fire({
        title: titulo,
        input: 'textarea',
        inputPlaceholder: 'Ingrese un comentario...',
        showCancelButton: true,
        inputValidator: (value) => {
            if (requerido && !value) {
                return 'Debe ingresar un comentario';
            }
        }
    });
    return comentario;
}

// Modificar la función de cargar presupuesto
async function cargarPresupuesto(id) {
    try {
        const response = await fetch(`api/presupuestos.php?id=${id}`);
        const presupuesto = await response.json();
        
        // ... existing code ...

        // Actualizar estado
        if (presupuesto.estado_id) {
            const estado = estadosDisponibles.find(e => e.id == presupuesto.estado_id);
            if (estado) {
                const badge = document.getElementById('estadoActual');
                badge.textContent = estado.nombre;
                badge.style.backgroundColor = estado.color;
                badge.style.color = '#fff';
            }
        }

        // Actualizar validez
        const validezInfo = document.getElementById('validezInfo');
        if (presupuesto.fecha) {
            const fechaValidez = new Date(presupuesto.fecha);
            fechaValidez.setDate(fechaValidez.getDate() + (presupuesto.validez_dias || 30));
            const diasRestantes = Math.ceil((fechaValidez - new Date()) / (1000 * 60 * 60 * 24));
            
            if (diasRestantes > 0) {
                validezInfo.textContent = `Válido por ${diasRestantes} días más`;
                validezInfo.className = 'text-success';
            } else {
                validezInfo.textContent = 'Presupuesto vencido';
                validezInfo.className = 'text-danger';
            }
        }

        // Habilitar/deshabilitar botones según estado
        const btnAprobar = document.getElementById('btnAprobar');
        const btnRechazar = document.getElementById('btnRechazar');
        const estadoFinal = estado?.es_final || false;
        
        btnAprobar.disabled = estadoFinal;
        btnRechazar.disabled = estadoFinal;

    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar el presupuesto');
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    // ... existing code ...
    cargarEstados();
});
</script>

<?php include 'includes/footer.php'; ?> 