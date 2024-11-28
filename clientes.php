<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Gestión de Clientes</h5>
        <button class="btn btn-primary" id="btnNuevoCliente">
            <i class="bi bi-person-plus"></i> Nuevo Cliente
        </button>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="buscarCliente" placeholder="Buscar cliente...">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" id="buscarCUIT" placeholder="Buscar por CUIT...">
            </div>
            <div class="col-md-4">
                <select class="form-select" id="filtroLocalidad">
                    <option value="">Todas las localidades</option>
                </select>
            </div>
        </div>
        <div id="gridClientes" class="ag-theme-alpine" style="height: 600px;"></div>
    </div>
</div>

<!-- Modal Cliente -->
<div class="modal fade" id="modalCliente" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalClienteTitle">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formCliente">
                    <input type="hidden" id="clienteId">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre/Razón Social</label>
                            <input type="text" class="form-control" id="nombre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">CUIT</label>
                            <input type="text" class="form-control" id="cuit" required>
                            <div class="invalid-feedback">CUIT inválido</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Domicilio</label>
                            <input type="text" class="form-control" id="domicilio">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Localidad</label>
                            <input type="text" class="form-control" id="localidad">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contacto</label>
                            <input type="text" class="form-control" id="contacto">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarCliente">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial de Presupuestos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="gridHistorial" class="ag-theme-alpine" style="height: 400px;"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuración de la grilla de clientes
    const columnDefs = [
        { field: 'nombre', headerName: 'Nombre', filter: 'agTextColumnFilter' },
        { field: 'cuit', headerName: 'CUIT', filter: 'agTextColumnFilter' },
        { field: 'localidad', headerName: 'Localidad', filter: 'agTextColumnFilter' },
        { field: 'telefono', headerName: 'Teléfono' },
        { field: 'email', headerName: 'Email' },
        {
            headerName: 'Acciones',
            cellRenderer: params => `
                <button class="btn btn-sm btn-primary" onclick="editarCliente('${params.data.id}')">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-info" onclick="verHistorial('${params.data.id}')">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="nuevoPresupuesto('${params.data.id}')">
                    <i class="bi bi-file-earmark-plus"></i>
                </button>
            `
        }
    ];

    const gridOptions = {
        ...gridCommonOptions,
        columnDefs: columnDefs,
        onGridReady: cargarClientes
    };

    new agGrid.Grid(document.querySelector('#gridClientes'), gridOptions);

    // Configuración de la grilla de historial
    const historialColumnDefs = [
        { field: 'numero', headerName: 'Número' },
        { field: 'fecha', headerName: 'Fecha', valueFormatter: params => formatDate(params.value) },
        { 
            field: 'total', 
            headerName: 'Total', 
            valueFormatter: params => formatCurrency(params.value)
        },
        { field: 'estado', headerName: 'Estado' },
        {
            headerName: 'Acciones',
            cellRenderer: params => `
                <button class="btn btn-sm btn-primary" onclick="verPresupuesto('${params.data.id}')">
                    <i class="bi bi-eye"></i>
                </button>
                <button class="btn btn-sm btn-success" onclick="duplicarPresupuesto('${params.data.id}')">
                    <i class="bi bi-files"></i>
                </button>
            `
        }
    ];

    const historialGridOptions = {
        ...gridCommonOptions,
        columnDefs: historialColumnDefs
    };

    new agGrid.Grid(document.querySelector('#gridHistorial'), historialGridOptions);

    // Eventos
    document.getElementById('buscarCliente').addEventListener('input', e => {
        gridOptions.api.setQuickFilter(e.target.value);
    });

    document.getElementById('buscarCUIT').addEventListener('input', e => {
        const cuit = e.target.value.replace(/\D/g, '');
        if (cuit.length === 11) {
            gridOptions.api.setFilterModel({
                cuit: { type: 'equals', filter: cuit }
            });
        } else {
            gridOptions.api.setFilterModel(null);
        }
    });

    document.getElementById('cuit').addEventListener('input', function(e) {
        const cuit = e.target.value.replace(/\D/g, '');
        this.value = cuit;
        
        if (cuit.length === 11) {
            if (validarCUIT(cuit)) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
            }
        } else {
            this.classList.remove('is-valid', 'is-invalid');
        }
    });

    document.getElementById('btnNuevoCliente').addEventListener('click', () => {
        document.getElementById('formCliente').reset();
        document.getElementById('clienteId').value = '';
        document.getElementById('modalClienteTitle').textContent = 'Nuevo Cliente';
        new bootstrap.Modal(document.getElementById('modalCliente')).show();
    });

    document.getElementById('btnGuardarCliente').addEventListener('click', async () => {
        const form = document.getElementById('formCliente');
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const cuit = document.getElementById('cuit').value;
        if (!validarCUIT(cuit)) {
            document.getElementById('cuit').classList.add('is-invalid');
            return;
        }

        const clienteData = {
            id: document.getElementById('clienteId').value,
            nombre: document.getElementById('nombre').value,
            cuit: cuit,
            domicilio: document.getElementById('domicilio').value,
            localidad: document.getElementById('localidad').value,
            telefono: document.getElementById('telefono').value,
            email: document.getElementById('email').value,
            contacto: document.getElementById('contacto').value,
            notas: document.getElementById('notas').value
        };

        try {
            const response = await fetch('api/clientes.php', {
                method: clienteData.id ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(clienteData)
            });

            const data = await response.json();

            if (data.success) {
                showNotification('Éxito', data.mensaje);
                bootstrap.Modal.getInstance(document.getElementById('modalCliente')).hide();
                cargarClientes();
            } else {
                showNotification('Error', data.mensaje, 'error');
            }
        } catch (error) {
            showNotification('Error', 'Error al procesar la solicitud', 'error');
        }
    });

    // Cargar localidades únicas
    cargarLocalidades();
});

async function cargarClientes() {
    try {
        const response = await fetch('api/clientes.php');
        const data = await response.json();
        gridOptions.api.setRowData(data);
    } catch (error) {
        showNotification('Error', 'Error al cargar clientes', 'error');
    }
}

async function cargarLocalidades() {
    try {
        const response = await fetch('api/clientes.php?localidades=true');
        const localidades = await response.json();
        const select = document.getElementById('filtroLocalidad');
        
        localidades.forEach(localidad => {
            if (localidad) {
                const option = document.createElement('option');
                option.value = localidad;
                option.textContent = localidad;
                select.appendChild(option);
            }
        });
    } catch (error) {
        console.error('Error al cargar localidades:', error);
    }
}

async function editarCliente(id) {
    try {
        const response = await fetch(`api/clientes.php?id=${id}`);
        const cliente = await response.json();
        
        document.getElementById('clienteId').value = cliente.id;
        document.getElementById('nombre').value = cliente.nombre;
        document.getElementById('cuit').value = cliente.cuit;
        document.getElementById('domicilio').value = cliente.domicilio;
        document.getElementById('localidad').value = cliente.localidad;
        document.getElementById('telefono').value = cliente.telefono;
        document.getElementById('email').value = cliente.email;
        document.getElementById('contacto').value = cliente.contacto;
        document.getElementById('notas').value = cliente.notas;
        
        document.getElementById('modalClienteTitle').textContent = 'Editar Cliente';
        new bootstrap.Modal(document.getElementById('modalCliente')).show();
    } catch (error) {
        showNotification('Error', 'Error al cargar el cliente', 'error');
    }
}

async function verHistorial(clienteId) {
    try {
        const response = await fetch(`api/presupuestos.php?cliente_id=${clienteId}`);
        const presupuestos = await response.json();
        
        const gridHistorial = document.querySelector('#gridHistorial');
        const historialGrid = gridHistorial.gridOptions;
        historialGrid.api.setRowData(presupuestos);
        
        new bootstrap.Modal(document.getElementById('modalHistorial')).show();
    } catch (error) {
        showNotification('Error', 'Error al cargar el historial', 'error');
    }
}

function nuevoPresupuesto(clienteId) {
    window.location.href = `presupuesto.php?cliente_id=${clienteId}`;
}

function verPresupuesto(id) {
    window.location.href = `presupuesto.php?id=${id}`;
}

function duplicarPresupuesto(id) {
    window.location.href = `presupuesto.php?duplicar=${id}`;
}
</script>

<?php include 'includes/footer.php'; ?> 