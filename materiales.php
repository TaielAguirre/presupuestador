<?php
require_once 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h2>Gestión de Materiales</h2>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-primary" onclick="abrirModalMaterial()">
                <i class="bi bi-plus"></i> Nuevo Material
            </button>
            <button type="button" class="btn btn-success" onclick="abrirModalValidacion()">
                <i class="bi bi-check-circle"></i> Validar Códigos Flexxus
            </button>
            <button type="button" class="btn btn-info" onclick="abrirModalImportar()">
                <i class="bi bi-upload"></i> Importar
            </button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaMateriales" class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Precio Unitario</th>
                            <th>Moneda</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Material -->
<div class="modal fade" id="modalMaterial" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formMaterial">
                    <input type="hidden" id="material_id">
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Código</label>
                        <input type="text" class="form-control" id="codigo" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <input type="text" class="form-control" id="descripcion" required>
                    </div>
                    <div class="mb-3">
                        <label for="precio_unitario" class="form-label">Precio Unitario</label>
                        <input type="number" step="0.01" class="form-control" id="precio_unitario" required>
                    </div>
                    <div class="mb-3">
                        <label for="moneda" class="form-label">Moneda</label>
                        <select class="form-select" id="moneda" required>
                            <option value="ARS">ARS</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarMaterial()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Validación -->
<div class="modal fade" id="modalValidacion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Validar Códigos Flexxus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="codigos" class="form-label">Códigos (uno por línea)</label>
                    <textarea class="form-control" id="codigos" rows="10"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="validarCodigos()">Validar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Importar -->
<div class="modal fade" id="modalImportar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Importar Materiales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formImportar">
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Archivo Excel</label>
                        <input type="file" class="form-control" id="archivo" accept=".xlsx,.xls" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="importarMateriales()">Importar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Historial -->
<div class="modal fade" id="modalHistorial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Historial de Precios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="tablaHistorial" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Precio Anterior</th>
                                <th>Precio Nuevo</th>
                                <th>Variación %</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 