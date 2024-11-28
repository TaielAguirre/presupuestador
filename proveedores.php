<?php
require_once 'includes/header.php';
require_once 'includes/middleware.php';

use function App\verificarPermiso;
verificarPermiso('material_ver');
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>Gestión de Proveedores</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" id="btnNuevoProveedor">
                <i class="bi bi-plus-circle"></i> Nuevo Proveedor
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div id="gridProveedores" class="ag-theme-alpine" style="height: 600px;"></div>
        </div>
    </div>
</div>

<!-- Modal Proveedor -->
<div class="modal fade" id="modalProveedor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formProveedor">
                    <input type="hidden" id="id" name="id">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cuit" class="form-label">CUIT</label>
                            <input type="text" class="form-control" id="cuit" name="cuit">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="domicilio" class="form-label">Domicilio</label>
                            <input type="text" class="form-control" id="domicilio" name="domicilio">
                        </div>
                        <div class="col-md-4">
                            <label for="localidad" class="form-label">Localidad</label>
                            <input type="text" class="form-control" id="localidad" name="localidad">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono">
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-4">
                            <label for="contacto" class="form-label">Contacto</label>
                            <input type="text" class="form-control" id="contacto" name="contacto">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarProveedor">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Configuración de la grilla
    const gridOptions = {
        ...gridCommonOptions,
        columnDefs: [
            { field: 'id', hide: true },
            { field: 'nombre', headerName: 'Nombre', flex: 2 },
            { field: 'cuit', headerName: 'CUIT', flex: 1 },
            { field: 'telefono', headerName: 'Teléfono', flex: 1 },
            { field: 'email', headerName: 'Email', flex: 1 },
            { field: 'contacto', headerName: 'Contacto', flex: 1 },
            { field: 'localidad', headerName: 'Localidad', flex: 1 },
            {
                headerName: 'Acciones',
                width: 100,
                cellRenderer: params => {
                    return `
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="editarProveedor(${params.data.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarProveedor(${params.data.id})">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
            }
        ]
    };

    // Inicializar grilla
    const gridDiv = document.querySelector('#gridProveedores');
    new agGrid.Grid(gridDiv, gridOptions);

    // Cargar datos
    cargarProveedores();

    // Event Listeners
    document.getElementById('btnNuevoProveedor').addEventListener('click', () => {
        document.getElementById('formProveedor').reset();
        document.getElementById('id').value = '';
        new bootstrap.Modal(document.getElementById('modalProveedor')).show();
    });

    document.getElementById('btnGuardarProveedor').addEventListener('click', guardarProveedor);
});

async function cargarProveedores() {
    try {
        const response = await fetch('api/proveedores.php');
        const data = await response.json();
        if (data.success) {
            gridOptions.api.setRowData(data.proveedores);
        } else {
            showNotification('Error', data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Error al cargar proveedores', 'error');
    }
}

async function guardarProveedor() {
    try {
        const formData = new FormData(document.getElementById('formProveedor'));
        const data = Object.fromEntries(formData.entries());
        
        const response = await fetch('api/proveedores.php', {
            method: data.id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        
        if (result.success) {
            showNotification('Éxito', 'Proveedor guardado correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalProveedor')).hide();
            cargarProveedores();
        } else {
            showNotification('Error', result.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Error al guardar proveedor', 'error');
    }
}

async function editarProveedor(id) {
    try {
        const response = await fetch(`api/proveedores.php?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const proveedor = data.proveedor;
            Object.keys(proveedor).forEach(key => {
                const input = document.getElementById(key);
                if (input) input.value = proveedor[key];
            });
            
            new bootstrap.Modal(document.getElementById('modalProveedor')).show();
        } else {
            showNotification('Error', data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Error al cargar proveedor', 'error');
    }
}

async function eliminarProveedor(id) {
    if (!await confirmarEliminacion()) return;

    try {
        const response = await fetch(`api/proveedores.php?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Éxito', 'Proveedor eliminado correctamente', 'success');
            cargarProveedores();
        } else {
            showNotification('Error', data.mensaje, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error', 'Error al eliminar proveedor', 'error');
    }
}

async function confirmarEliminacion() {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    return result.isConfirmed;
}
</script>

<?php require_once 'includes/footer.php'; ?> 