document.addEventListener('DOMContentLoaded', function() {
    // Configuración de la grilla
    const columnDefs = [
        { field: 'codigo', headerName: 'Código', editable: true },
        { field: 'descripcion', headerName: 'Descripción', editable: true },
        { field: 'cantidad', headerName: 'Cantidad', editable: true, type: 'numericColumn' },
        { field: 'precioUnitario', headerName: 'Precio Unitario', editable: true, type: 'numericColumn' },
        { field: 'plazoEntrega', headerName: 'Plazo de Entrega', editable: true },
        { field: 'proveedor', headerName: 'Proveedor', editable: true },
        { field: 'descuento1', headerName: 'Descuento 1 (%)', editable: true, type: 'numericColumn' },
        { field: 'descuento2', headerName: 'Descuento 2 (%)', editable: true, type: 'numericColumn' },
        { field: 'costoExtra', headerName: 'Costo Extra', editable: true, type: 'numericColumn' },
        { field: 'costoFinalUSD', headerName: 'Costo Final USD', editable: false, type: 'numericColumn',
            valueGetter: (params) => calcularCostoFinalUSD(params.data)
        }
    ];

    // Inicialización de AG-Grid
    const gridOptions = {
        columnDefs: columnDefs,
        rowData: [],
        defaultColDef: {
            flex: 1,
            minWidth: 100,
            resizable: true,
        },
        onCellValueChanged: onCellValueChanged
    };

    const gridDiv = document.querySelector('#gridItems');
    new agGrid.Grid(gridDiv, gridOptions);

    // Función para calcular costo final en USD
    function calcularCostoFinalUSD(data) {
        if (!data) return 0;
        
        const precioBase = data.precioUnitario * data.cantidad;
        const descuento1 = precioBase * (data.descuento1 || 0) / 100;
        const descuento2 = (precioBase - descuento1) * (data.descuento2 || 0) / 100;
        const costoExtra = data.costoExtra || 0;
        
        const dolarDivisa = parseFloat(document.getElementById('dolarDivisa').value) || 1;
        return ((precioBase - descuento1 - descuento2 + costoExtra) / dolarDivisa).toFixed(2);
    }

    // Evento cuando cambia un valor en la grilla
    function onCellValueChanged(params) {
        const field = params.column.colId;
        if (['cantidad', 'precioUnitario', 'descuento1', 'descuento2', 'costoExtra'].includes(field)) {
            params.api.refreshCells({
                columns: ['costoFinalUSD'],
                rowNodes: [params.node]
            });
        }
    }

    // Evento para agregar nuevo item
    document.getElementById('btnAgregarItem').addEventListener('click', () => {
        const newItem = {
            codigo: '',
            descripcion: '',
            cantidad: 0,
            precioUnitario: 0,
            plazoEntrega: '',
            proveedor: '',
            descuento1: 0,
            descuento2: 0,
            costoExtra: 0
        };
        gridOptions.api.applyTransaction({ add: [newItem] });
    });

    // Evento para exportar
    document.getElementById('btnExportar').addEventListener('click', async () => {
        const clienteData = {
            cliente: document.getElementById('cliente').value,
            domicilio: document.getElementById('domicilio').value,
            localidad: document.getElementById('localidad').value,
            cuit: document.getElementById('cuit').value,
            telefono: document.getElementById('telefono').value,
            contacto: document.getElementById('contacto').value
        };

        const items = [];
        gridOptions.api.forEachNode(node => {
            if (node.data.codigo) {
                items.push(node.data);
            }
        });

        const presupuestoData = {
            cliente: clienteData,
            items: items,
            dolarDivisa: document.getElementById('dolarDivisa').value,
            dolarBillete: document.getElementById('dolarBillete').value
        };

        try {
            const response = await fetch('api/exportar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(presupuestoData)
            });

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'presupuesto_flexxus.xlsx';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            } else {
                alert('Error al exportar el presupuesto');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al exportar el presupuesto');
        }
    });

    // Actualizar cotizaciones del dólar automáticamente
    async function actualizarCotizaciones() {
        try {
            const response = await fetch('api/cotizaciones.php');
            const data = await response.json();
            
            document.getElementById('dolarDivisa').value = data.dolarDivisa;
            document.getElementById('dolarBillete').value = data.dolarBillete;
            
            // Actualizar todos los costos en USD
            gridOptions.api.refreshCells({
                columns: ['costoFinalUSD']
            });
        } catch (error) {
            console.error('Error al obtener cotizaciones:', error);
        }
    }

    // Actualizar cotizaciones cada 5 minutos
    actualizarCotizaciones();
    setInterval(actualizarCotizaciones, 5 * 60 * 1000);
}); 