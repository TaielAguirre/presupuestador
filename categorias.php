<?php include 'includes/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Categorías de Presupuesto</h4>
                    <button class="btn btn-primary" id="btnNuevaCategoria">
                        <i class="bi bi-plus-lg"></i> Nueva Categoría
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Lista de Categorías -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Categorías</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="listaCategorias">
                        <!-- Se llena dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <!-- Detalles de Categoría -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detalles de Categoría</h5>
                </div>
                <div class="card-body">
                    <form id="formCategoria">
                        <input type="hidden" id="categoriaId">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre *</label>
                                <input type="text" class="form-control" id="nombre" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Orden</label>
                                <input type="number" class="form-control" id="orden" min="1">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" rows="3"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="mostrarSubtotal">
                                    <label class="form-check-label">
                                        Mostrar subtotal al final de la categoría
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar
                                </button>
                                <button type="button" class="btn btn-danger" id="btnEliminar">
                                    <i class="bi bi-trash"></i> Eliminar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Cargar categorías
async function cargarCategorias() {
    try {
        const response = await fetch('api/categorias.php');
        const categorias = await response.json();
        
        const lista = document.getElementById('listaCategorias');
        lista.innerHTML = categorias.map(c => `
            <a href="#" class="list-group-item list-group-item-action" 
               onclick="cargarCategoria(${c.id})">
                ${c.nombre}
                <small class="d-block text-muted">Orden: ${c.orden || 'No especificado'}</small>
            </a>
        `).join('');
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar categorías');
    }
}

// Cargar categoría
async function cargarCategoria(id) {
    try {
        const response = await fetch(`api/categorias.php?id=${id}`);
        const categoria = await response.json();
        
        document.getElementById('categoriaId').value = categoria.id;
        document.getElementById('nombre').value = categoria.nombre;
        document.getElementById('descripcion').value = categoria.descripcion;
        document.getElementById('orden').value = categoria.orden;
        document.getElementById('mostrarSubtotal').checked = categoria.mostrar_subtotal;
        
        document.getElementById('btnEliminar').style.display = 'inline-block';
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar la categoría');
    }
}

// Nueva categoría
document.getElementById('btnNuevaCategoria').addEventListener('click', () => {
    document.getElementById('formCategoria').reset();
    document.getElementById('categoriaId').value = '';
    document.getElementById('btnEliminar').style.display = 'none';
});

// Guardar categoría
document.getElementById('formCategoria').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const categoria = {
        id: document.getElementById('categoriaId').value,
        nombre: document.getElementById('nombre').value,
        descripcion: document.getElementById('descripcion').value,
        orden: document.getElementById('orden').value,
        mostrar_subtotal: document.getElementById('mostrarSubtotal').checked
    };

    try {
        const response = await fetch('api/categorias.php', {
            method: categoria.id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(categoria)
        });

        const data = await response.json();
        if (data.success) {
            mostrarExito(data.mensaje);
            cargarCategorias();
            if (!categoria.id) {
                document.getElementById('categoriaId').value = data.id;
            }
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al guardar la categoría');
    }
});

// Eliminar categoría
document.getElementById('btnEliminar').addEventListener('click', async () => {
    const id = document.getElementById('categoriaId').value;
    if (!id) return;

    if (!await confirmar('¿Está seguro de eliminar esta categoría?')) return;

    try {
        const response = await fetch(`api/categorias.php?id=${id}`, {
            method: 'DELETE'
        });

        const data = await response.json();
        if (data.success) {
            mostrarExito(data.mensaje);
            cargarCategorias();
            document.getElementById('formCategoria').reset();
            document.getElementById('categoriaId').value = '';
            document.getElementById('btnEliminar').style.display = 'none';
        } else {
            throw new Error(data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message || 'Error al eliminar la categoría');
    }
});

// Funciones auxiliares
async function confirmar(mensaje) {
    const result = await Swal.fire({
        title: '¿Está seguro?',
        text: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    return result.isConfirmed;
}

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

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    cargarCategorias();
    document.getElementById('btnEliminar').style.display = 'none';
});
</script>

<?php include 'includes/footer.php'; ?> 