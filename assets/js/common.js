// Opciones comunes para AG Grid
const gridCommonOptions = {
    defaultColDef: {
        sortable: true,
        filter: true,
        resizable: true
    },
    rowSelection: 'single',
    pagination: true,
    paginationPageSize: 10,
    domLayout: 'autoHeight'
};

// Formatear moneda
function formatCurrency(value, currency = 'ARS') {
    if (!value) return '-';
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: currency
    }).format(value);
}

// Formatear fecha
function formatDate(value, includeTime = false) {
    if (!value) return '-';
    const date = new Date(value);
    const options = {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    };
    
    if (includeTime) {
        options.hour = '2-digit';
        options.minute = '2-digit';
    }
    
    return date.toLocaleDateString('es-AR', options);
}

// Formatear porcentaje
function formatPercent(value) {
    if (!value && value !== 0) return '-';
    return `${value.toFixed(2)}%`;
}

// Mostrar mensaje de error
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message
    });
}

// Mostrar mensaje de éxito
function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Éxito',
        text: message
    });
}

// Confirmar acción
async function confirm(title, text) {
    const result = await Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí',
        cancelButtonText: 'No'
    });
    
    return result.isConfirmed;
}

// Cargar combo de clientes
async function loadClientes(selectElement, selectedValue = null) {
    try {
        const response = await fetch('api/clientes.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.mensaje);
        }
        
        selectElement.innerHTML = '<option value="">Seleccione un cliente</option>';
        data.clientes.forEach(cliente => {
            const option = document.createElement('option');
            option.value = cliente.id;
            option.textContent = `${cliente.razon_social} (${cliente.cuit})`;
            if (selectedValue && cliente.id == selectedValue) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    } catch (error) {
        console.error('Error al cargar clientes:', error);
        showError('Error al cargar la lista de clientes');
    }
}

// Cargar combo de materiales
async function loadMateriales(selectElement, selectedValue = null) {
    try {
        const response = await fetch('api/materiales.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.mensaje);
        }
        
        selectElement.innerHTML = '<option value="">Seleccione un material</option>';
        data.materiales.forEach(material => {
            const option = document.createElement('option');
            option.value = material.id;
            option.textContent = `${material.codigo} - ${material.descripcion}`;
            if (selectedValue && material.id == selectedValue) {
                option.selected = true;
            }
            selectElement.appendChild(option);
        });
    } catch (error) {
        console.error('Error al cargar materiales:', error);
        showError('Error al cargar la lista de materiales');
    }
}

// Obtener cotización actual
async function getCurrentCotizacion() {
    try {
        const response = await fetch('api/cotizaciones.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.mensaje);
        }
        
        return data.data;
    } catch (error) {
        console.error('Error al obtener cotización:', error);
        showError('Error al obtener la cotización actual');
        return null;
    }
} 