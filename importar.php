<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Materiales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h5>Importar Materiales desde Excel</h5>
            </div>
            <div class="card-body">
                <form id="formImportar" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Seleccionar archivo Excel</label>
                        <input type="file" class="form-control" id="archivo" name="archivo" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Importar</button>
                </form>
                <div id="resultado" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('formImportar').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const resultado = document.getElementById('resultado');
            
            try {
                const response = await fetch('api/importar_materiales.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                resultado.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'danger'}">
                        ${data.mensaje}
                    </div>
                `;
            } catch (error) {
                resultado.innerHTML = `
                    <div class="alert alert-danger">
                        Error al procesar la solicitud
                    </div>
                `;
            }
        });
    </script>
</body>
</html> 