// Variables globales
let presupuestoActual = null;
let gridOptions = null;
let gridMaterialesOptions = null;
let tasasDeCambio = { USD: 0, EUR: 0 };

// Inicialización
document.addEventListener('DOMContentLoaded', async () => {
    await inicializarGrillas();
    await cargarCotizaciones();
    inicializarEventos();
    inicializarAutoComplete();
    
    // Cargar presupuesto si hay ID en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('id')) {
        await cargarPresupuesto(urlParams.get('id'));
    }
});

async function inicializarGrillas() {
    // Configuración de la grilla principal
    gridOptions = {
        columnDefs: [
            { 
                headerCheckboxSelection: true,
                checkboxSelection: true,
                width: 40,
                pinned: 'left'
            },
            { 
                field: 'item', 
                headerName: 'ITEM',
                width: 80,
                editable: true,
                valueParser: params => Number(params.newValue)
            },
            { 
                field: 'codigo',
                headerName: 'CÓDIGO',
                width: 120,
                editable: false
            },
            { 
                field: 'descripcion',
                headerName: 'DESCRIPCIÓN',
                flex: 1,
                editable: true
            },
            { 
                field: 'cantidad',
                headerName: 'CANTIDAD',
                width: 100,
                editable: true,
                type: 'numericColumn',
                valueParser: params => Number(params.newValue)
            },
            { 
                field: 'precio_usd',
                headerName: 'PRECIO U$D',
                width: 120,
                editable: true,
                type: 'numericColumn',
                valueFormatter: params => formatearMoneda(params.value, 'USD'),
                valueParser: params => Number(params.newValue)
            },
            { 
                field: 'precio_ars',
                headerName: 'PRECIO $',
                width: 120,
                editable: true,
                type: 'numericColumn',
                valueFormatter: params => formatearMoneda(params.value, 'ARS'),
                valueParser: params => Number(params.newValue)
            },
            { 
                field: 'subtotal',
                headerName: 'SUBTOTAL',
                width: 120,
                type: 'numericColumn',
                valueFormatter: params => {
                    const moneda = document.getElementById('monedaTrabajo').value;
                    return formatearMoneda(params.value, moneda);
                },
                valueGetter: params => calcularSubtotalItem(params.data)
            },
            {
                field: 'descuento',
                headerName: 'DESC %',
                width: 100,
                editable: true,
                type: 'numericColumn',
                valueParser: params => Number(params.newValue)
            }
        ],
        rowData: [],
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true
        },
        rowSelection: 'multiple',
        suppressRowClickSelection: true,
        onCellValueChanged: onCeldaCambiada,
        onGridReady: params => {
            params.api.sizeColumnsToFit();
        }
    };

    // Configuración de la grilla de materiales
    gridMaterialesOptions = {
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
                headerName: 'Precio U$D',
                width: 120,
                type: 'numericColumn',
                valueFormatter: params => formatearMoneda(params.value, 'USD')
            },
            { 
                field: 'precio_ars',
                headerName: 'Precio $',
                width: 120,
                type: 'numericColumn',
                valueFormatter: params => formatearMoneda(params.value, 'ARS')
            }
        ],
        rowData: [],
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true
        },
        rowSelection: 'multiple',
        onGridReady: params => {
            params.api.sizeColumnsToFit();
        }
    };

    // Inicializar grillas
    new agGrid.Grid(document.querySelector('#gridItems'), gridOptions);
    new agGrid.Grid(document.querySelector('#gridMateriales'), gridMaterialesOptions);
}

function inicializarEventos() {
    // Eventos de botones principales
    document.getElementById('btnGuardar').addEventListener('click', guardarPresupuesto);
    document.getElementById('btnExportar').addEventListener('click', exportarPDF);
    document.getElementById('btnFlexxus').addEventListener('click', exportarFlexxus);
    
    // Eventos de items
    document.getElementById('btnBuscarMaterial').addEventListener('click', abrirBusquedaMaterial);
    document.getElementById('btnAgregarItem').addEventListener('click', agregarItemVacio);
    document.getElementById('btnEliminarItem').addEventListener('click', eliminarItemsSeleccionados);
    document.getElementById('btnSeleccionarMaterial').addEventListener('click', insertarMaterialesSeleccionados);
    
    // Eventos de búsqueda de materiales
    document.getElementById('buscarMaterialInput').addEventListener('input', filtrarMateriales);
    
    // Eventos de cambio de moneda y cotización
    document.getElementById('monedaTrabajo').addEventListener('change', actualizarPrecios);
    document.getElementById('dolarDivisa').addEventListener('change', actualizarPrecios);
    document.getElementById('dolarBillete').addEventListener('change', actualizarPrecios);
}

function inicializarAutoComplete() {
    const autoCompleteJS = new autoComplete({
        selector: '#buscarCliente',
        placeHolder: 'Buscar cliente por nombre o CUIT...',
        data: {
            src: async (query) => {
                try {
                    const response = await fetch(`api/clientes.php?buscar=${query}`);
                    const data = await response.json();
                    return data.success ? data.data : [];
                } catch (error) {
                    console.error('Error:', error);
                    return [];
                }
            },
            keys: ['razon_social', 'cuit']
        },
        resultItem: {
            highlight: true
        },
        resultsList: {
            maxResults: 10,
            noResults: true,
            class: "autoComplete_list"
        },
        events: {
            input: {
                selection: (event) => {
                    const selection = event.detail.selection.value;
                    document.getElementById('buscarCliente').value = selection.razon_social;
                    cargarDatosCliente(selection);
                }
            }
        }
    });
}

// Funciones de carga y guardado
async function cargarPresupuesto(id) {
    try {
        const response = await fetch(`api/presupuestos.php?id=${id}`);
        if (!response.ok) throw new Error('Error al cargar el presupuesto');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.mensaje);
        
        presupuestoActual = data.data;
        
        // Cargar datos básicos
        document.getElementById('clienteId').value = presupuestoActual.cliente_id;
        document.getElementById('buscarCliente').value = presupuestoActual.cliente_nombre;
        document.getElementById('monedaTrabajo').value = presupuestoActual.moneda;
        document.querySelector('input[name="fecha"]').value = presupuestoActual.fecha;
        document.querySelector('input[name="fecha_validez"]').value = presupuestoActual.fecha_validez || '';
        document.querySelector('textarea[name="condiciones_pago"]').value = presupuestoActual.condiciones_pago || '';
        document.querySelector('textarea[name="plazo_entrega"]').value = presupuestoActual.plazo_entrega || '';
        document.querySelector('textarea[name="notas"]').value = presupuestoActual.notas || '';
        
        // Cargar items
        gridOptions.api.setRowData(presupuestoActual.items);
        
        // Actualizar totales
        calcularTotales();
        
        // Mostrar número de presupuesto
        document.getElementById('numeroPresupuesto').textContent = `#${presupuestoActual.numero}`;
        document.getElementById('tituloPresupuesto').textContent = `Presupuesto #${presupuestoActual.numero}`;
        
        // Cargar datos del cliente
        cargarDatosCliente(presupuestoActual.cliente);
        
    } catch (error) {
        mostrarError('Error al cargar el presupuesto: ' + error.message);
    }
}

async function guardarPresupuesto() {
    try {
        // Validaciones
        if (!document.getElementById('clienteId').value) {
            throw new Error('Debe seleccionar un cliente');
        }
        
        const items = [];
        gridOptions.api.forEachNode(node => {
            if (node.data) items.push(node.data);
        });
        
        if (items.length === 0) {
            throw new Error('Debe agregar al menos un item');
        }
        
        // Preparar datos
        const presupuestoData = {
            id: presupuestoActual?.id,
            cliente_id: document.getElementById('clienteId').value,
            fecha: document.querySelector('input[name="fecha"]').value,
            fecha_validez: document.querySelector('input[name="fecha_validez"]').value,
            moneda: document.getElementById('monedaTrabajo').value,
            valor_dolar_divisa: parseFloat(document.getElementById('dolarDivisa').value) || 0,
            valor_dolar_billete: parseFloat(document.getElementById('dolarBillete').value) || 0,
            condiciones_pago: document.querySelector('textarea[name="condiciones_pago"]').value,
            plazo_entrega: document.querySelector('textarea[name="plazo_entrega"]').value,
            notas: document.querySelector('textarea[name="notas"]').value,
            items: items
        };
        
        // Enviar datos
        const response = await fetch('api/presupuestos.php', {
            method: presupuestoActual ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(presupuestoData)
        });
        
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.mensaje);
        
        mostrarExito('Presupuesto guardado correctamente');
        
        // Redireccionar a la lista después de un momento
        setTimeout(() => {
            window.location.href = 'presupuestos.php';
        }, 1500);
        
    } catch (error) {
        mostrarError('Error al guardar el presupuesto: ' + error.message);
    }
}

// Funciones de exportación
async function exportarPDF() {
    if (!presupuestoActual?.id) {
        mostrarError('Debe guardar el presupuesto antes de exportarlo');
        return;
    }
    
    window.open(`api/exportar.php?id=${presupuestoActual.id}&formato=pdf`, '_blank');
}

async function exportarFlexxus() {
    if (!presupuestoActual?.id) {
        mostrarError('Debe guardar el presupuesto antes de exportarlo');
        return;
    }
    
    try {
        const response = await fetch(`api/exportar.php?id=${presupuestoActual.id}&formato=flexxus`);
        const data = await response.json();
        
        if (!data.success) throw new Error(data.mensaje);
        
        // Descargar archivo
        const link = document.createElement('a');
        link.href = data.url;
        link.download = `presupuesto_${presupuestoActual.id}_flexxus.csv`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        mostrarExito('Archivo exportado correctamente');
        
    } catch (error) {
        mostrarError('Error al exportar: ' + error.message);
    }
}

// Funciones de manejo de items
function agregarItemVacio() {
    const newItem = {
        item: gridOptions.api.getDisplayedRowCount() + 1,
        codigo: '',
        descripcion: '',
        cantidad: 1,
        precio_usd: 0,
        precio_ars: 0,
        descuento: 0
    };
    
    gridOptions.api.applyTransaction({ add: [newItem] });
    calcularTotales();
}

async function abrirBusquedaMaterial() {
    try {
        const response = await fetch('api/materiales.php');
        const data = await response.json();
        
        if (!data.success) throw new Error(data.mensaje);
        
        gridMaterialesOptions.api.setRowData(data.data);
        document.getElementById('buscarMaterialInput').value = '';
        
        const modal = new bootstrap.Modal(document.getElementById('modalBuscarMaterial'));
        modal.show();
        
    } catch (error) {
        mostrarError('Error al cargar materiales: ' + error.message);
    }
}

function insertarMaterialesSeleccionados() {
    const selectedRows = gridMaterialesOptions.api.getSelectedRows();
    if (selectedRows.length === 0) {
        mostrarError('Debe seleccionar al menos un material');
        return;
    }
    
    const items = selectedRows.map(material => ({
        item: gridOptions.api.getDisplayedRowCount() + 1,
        codigo: material.codigo,
        descripcion: material.descripcion,
        cantidad: 1,
        precio_usd: material.precio_usd,
        precio_ars: material.precio_ars,
        descuento: 0
    }));
    
    gridOptions.api.applyTransaction({ add: items });
    calcularTotales();
    
    bootstrap.Modal.getInstance(document.getElementById('modalBuscarMaterial')).hide();
}

function eliminarItemsSeleccionados() {
    const selectedRows = gridOptions.api.getSelectedRows();
    if (selectedRows.length === 0) {
        mostrarError('Debe seleccionar al menos un item para eliminar');
        return;
    }
    
    Swal.fire({
        title: '¿Está seguro?',
        text: `Se eliminarán ${selectedRows.length} item(s)`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            gridOptions.api.applyTransaction({ remove: selectedRows });
            renumerarItems();
            calcularTotales();
            mostrarExito('Items eliminados correctamente');
        }
    });
}

// Funciones auxiliares
function calcularTotales() {
    const moneda = document.getElementById('monedaTrabajo').value;
    const dolarDivisa = parseFloat(document.getElementById('dolarDivisa').value) || 0;
    
    let subtotal = 0;
    let descuentos = 0;
    
    gridOptions.api.forEachNode(node => {
        if (node.data) {
            const subtotalItem = calcularSubtotalItem(node.data);
            subtotal += subtotalItem;
            
            const descuento = subtotalItem * (node.data.descuento / 100);
            descuentos += descuento;
        }
    });
    
    const total = subtotal - descuentos;
    
    // Actualizar totales según moneda
    if (moneda === 'USD') {
        document.getElementById('subtotalARS').textContent = formatearMoneda(subtotal * dolarDivisa, 'ARS');
        document.getElementById('descuentosARS').textContent = formatearMoneda(descuentos * dolarDivisa, 'ARS');
        document.getElementById('totalARS').textContent = formatearMoneda(total * dolarDivisa, 'ARS');
        document.getElementById('totalUSD').textContent = formatearMoneda(total, 'USD');
    } else {
        document.getElementById('subtotalARS').textContent = formatearMoneda(subtotal, 'ARS');
        document.getElementById('descuentosARS').textContent = formatearMoneda(descuentos, 'ARS');
        document.getElementById('totalARS').textContent = formatearMoneda(total, 'ARS');
        document.getElementById('totalUSD').textContent = formatearMoneda(total / dolarDivisa, 'USD');
    }
}

function calcularSubtotalItem(item) {
    if (!item) return 0;
    const moneda = document.getElementById('monedaTrabajo').value;
    const cantidad = item.cantidad || 0;
    const precio = moneda === 'USD' ? (item.precio_usd || 0) : (item.precio_ars || 0);
    return cantidad * precio;
}

function onCeldaCambiada(params) {
    const moneda = document.getElementById('monedaTrabajo').value;
    const dolarDivisa = parseFloat(document.getElementById('dolarDivisa').value) || 0;
    
    // Si cambia precio USD, actualizar ARS
    if (params.column.colId === 'precio_usd' && dolarDivisa > 0) {
        params.data.precio_ars = params.value * dolarDivisa;
    }
    // Si cambia precio ARS, actualizar USD
    else if (params.column.colId === 'precio_ars' && dolarDivisa > 0) {
        params.data.precio_usd = params.value / dolarDivisa;
    }
    
    params.api.refreshCells({
        force: true,
        columns: ['precio_usd', 'precio_ars', 'subtotal']
    });
    
    calcularTotales();
}

function actualizarPrecios() {
    const dolarDivisa = parseFloat(document.getElementById('dolarDivisa').value) || 0;
    
    if (dolarDivisa === 0) {
        mostrarError('Debe ingresar un valor válido para el dólar');
        return;
    }
    
    gridOptions.api.forEachNode(node => {
        if (node.data) {
            // Si tiene precio en USD, recalcular ARS
            if (node.data.precio_usd) {
                node.data.precio_ars = node.data.precio_usd * dolarDivisa;
            }
            // Si no tiene USD pero tiene ARS, recalcular USD
            else if (node.data.precio_ars) {
                node.data.precio_usd = node.data.precio_ars / dolarDivisa;
            }
        }
    });
    
    gridOptions.api.refreshCells({ force: true });
    calcularTotales();
}

function renumerarItems() {
    let itemNum = 1;
    gridOptions.api.forEachNode(node => {
        node.data.item = itemNum++;
        node.setData(node.data);
    });
}

function formatearMoneda(valor, moneda) {
    if (!valor) return moneda === 'USD' ? 'U$D 0.00' : '$ 0.00';
    
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: moneda,
        currencyDisplay: 'symbol'
    }).format(valor);
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

// Funciones de cliente
function cargarDatosCliente(cliente) {
    document.getElementById('clienteId').value = cliente.id;
    document.getElementById('clienteCuit').textContent = formatearCuit(cliente.cuit) || '-';
    document.getElementById('clienteDomicilio').textContent = cliente.domicilio || '-';
    document.getElementById('clienteLocalidad').textContent = cliente.localidad || '-';
    document.getElementById('clienteTelefono').textContent = cliente.telefono || '-';
    document.getElementById('clienteContacto').textContent = cliente.contacto || '-';
}

function formatearCuit(cuit) {
    if (!cuit) return null;
    return cuit.replace(/^(\d{2})(\d{8})(\d{1})$/, '$1-$2-$3');
}

async function guardarNuevoCliente() {
    try {
        const form = document.getElementById('formNuevoCliente');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const clienteData = Object.fromEntries(formData);
        
        const response = await fetch('api/clientes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(clienteData)
        });
        
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.mensaje);
        
        // Actualizar cliente seleccionado
        document.getElementById('buscarCliente').value = clienteData.razon_social;
        cargarDatosCliente({
            id: data.id,
            ...clienteData
        });
        
        // Cerrar modal y mostrar mensaje
        bootstrap.Modal.getInstance(document.getElementById('modalNuevoCliente')).hide();
        form.reset();
        mostrarExito('Cliente creado correctamente');
        
    } catch (error) {
        mostrarError('Error al guardar cliente: ' + error.message);
    }
}

// Función para cargar cotizaciones
async function cargarCotizaciones() {
    try {
        const response = await fetch('api/cotizaciones.php');
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        
        const data = await response.json();
        if (!data.success) throw new Error(data.mensaje);
        
        document.getElementById('dolarDivisa').value = data.valor_divisa;
        document.getElementById('dolarBillete').value = data.valor_billete;
        
    } catch (error) {
        console.error('Error al cargar cotizaciones:', error);
        mostrarError('No hay cotización del dólar cargada. Por favor, ingrese los valores manualmente.');
    }
}