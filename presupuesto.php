<?php include 'includes/header.php'; ?>

<div class="container-fluid px-4">
    <div class="card shadow-sm mb-4">
        <!-- Header del Presupuesto -->
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <h5 class="mb-0 text-primary me-2">
                        <i class="bi bi-file-earmark-text me-2"></i>
                        <span id="tituloPresupuesto">Nuevo Presupuesto</span>
                    </h5>
                    <span id="numeroPresupuesto" class="badge bg-primary"></span>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
                        <i class="bi bi-arrow-left"></i> Volver
                    </button>
                    <button type="button" class="btn btn-primary" id="btnGuardar">
                        <i class="bi bi-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-success" id="btnExportar">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                    <button type="button" class="btn btn-info text-white" id="btnFlexxus">
                        <i class="bi bi-file-earmark-excel"></i> Flexxus
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form id="formPresupuesto">
                <!-- Sección Cliente y Fechas -->
                <div class="row g-3 mb-4">
                    <!-- Cliente -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="card-title mb-0">
                                        <i class="bi bi-person-circle me-2"></i>Información del Cliente
                                    </h6>
                                    <button class="btn btn-outline-primary btn-sm" type="button" id="btnNuevoCliente" 
                                            data-bs-toggle="modal" data-bs-target="#modalNuevoCliente"
                                            title="Agregar nuevo cliente">
                                        <i class="bi bi-person-plus-fill me-1"></i>
                                        Nuevo Cliente
                                    </button>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white">
                                            <i class="bi bi-search"></i>
                                        </span>
                                        <input type="text" id="buscarCliente" class="form-control" 
                                               placeholder="Buscar cliente por nombre o CUIT...">
                                    </div>
                                    <input type="hidden" id="clienteId" name="cliente_id" required>
                                </div>

                                <div id="infoCliente">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="text-muted small">CUIT</label>
                                                <p id="clienteCuit" class="mb-2">-</p>
                                            </div>
                                            <div class="info-group">
                                                <label class="text-muted small">Domicilio</label>
                                                <p id="clienteDomicilio" class="mb-2">-</p>
                                            </div>
                                            <div class="info-group">
                                                <label class="text-muted small">Localidad</label>
                                                <p id="clienteLocalidad" class="mb-2">-</p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="info-group">
                                                <label class="text-muted small">Teléfono</label>
                                                <p id="clienteTelefono" class="mb-2">-</p>
                                            </div>
                                            <div class="info-group">
                                                <label class="text-muted small">Contacto</label>
                                                <p id="clienteContacto" class="mb-2">-</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fechas y Moneda -->
                    <div class="col-md-6">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="bi bi-calendar3 me-2"></i>Datos del Presupuesto
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Emisión</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-calendar-date"></i>
                                            </span>
                                            <input type="date" name="fecha" class="form-control" required 
                                                   value="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Fecha de Validez</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-calendar-check"></i>
                                            </span>
                                            <input type="date" name="fecha_validez" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Moneda de Trabajo</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">
                                                <i class="bi bi-currency-exchange"></i>
                                            </span>
                                            <select name="moneda" id="monedaTrabajo" class="form-select" required>
                                                <option value="ARS">ARS - Peso Argentino</option>
                                                <option value="USD">USD - Dólar</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Cotización USD</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-white">$</span>
                                            <input type="number" id="dolarDivisa" class="form-control" 
                                                   step="0.01" placeholder="Divisa">
                                            <input type="number" id="dolarBillete" class="form-control" 
                                                   step="0.01" placeholder="Billete">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totales y Condiciones -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="bi bi-calculator me-2"></i>Totales
                                </h6>
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                        <span>Subtotal:</span>
                                        <span id="subtotalARS" class="fw-bold">$ 0.00</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light text-danger">
                                        <span>Descuentos:</span>
                                        <span id="descuentosARS" class="fw-bold">$ 0.00</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light text-success">
                                        <span>Costos Extra:</span>
                                        <span id="costosExtraARS" class="fw-bold">$ 0.00</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                        <span class="fw-bold">Total ARS:</span>
                                        <span id="totalARS" class="fw-bold text-primary fs-5">$ 0.00</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center bg-light">
                                        <span class="fw-bold">Total USD:</span>
                                        <span id="totalUSD" class="fw-bold text-success fs-5">U$D 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card h-100 border-0 bg-light">
                            <div class="card-body">
                                <h6 class="card-title mb-3">
                                    <i class="bi bi-clipboard-check me-2"></i>Condiciones
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Condiciones de Pago</label>
                                        <textarea name="condiciones_pago" class="form-control" rows="4"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Plazo de Entrega</label>
                                        <textarea name="plazo_entrega" class="form-control" rows="4"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Notas</label>
                                        <textarea name="notas" class="form-control" rows="4"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items del Presupuesto -->
                <div class="card border-0 bg-light">
                    <div class="card-header bg-light py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-list-check me-2"></i>Items del Presupuesto
                            </h6>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btnBuscarMaterial">
                                    <i class="bi bi-search me-1"></i>Buscar Material
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" id="btnAgregarItem">
                                    <i class="bi bi-plus-lg me-1"></i>Agregar
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" id="btnEliminarItem">
                                    <i class="bi bi-trash me-1"></i>Eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="gridItems" style="height: calc(100vh - 750px);" class="ag-theme-alpine"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus me-2"></i>Nuevo Cliente
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevoCliente">
                    <div class="mb-3">
                        <label class="form-label">Razón Social</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <input type="text" name="razon_social" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CUIT</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-upc"></i></span>
                            <input type="text" name="cuit" class="form-control" required pattern="[0-9]{11}">
                        </div>
                        <div class="form-text">Ingrese solo números, sin guiones</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Domicilio</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                            <input type="text" name="domicilio" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Localidad</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-pin-map"></i></span>
                            <input type="text" name="localidad" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="telefono" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contacto</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="contacto" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="guardarNuevoCliente()">
                    <i class="bi bi-check-lg me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Buscar Material -->
<div class="modal fade" id="modalBuscarMaterial" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-search me-2"></i>Buscar Material
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" id="buscarMaterialInput" 
                           placeholder="Buscar por código o descripción...">
                </div>
                <div id="gridMateriales" style="height: 400px;" class="ag-theme-alpine"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" id="btnSeleccionarMaterial">
                    <i class="bi bi-check-lg me-1"></i>Insertar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts necesarios -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@tarekraafat/autocomplete.js@10.2.7/dist/autoComplete.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ag-grid-community/dist/ag-grid-community.min.js"></script>
<script src="js/presupuestos.js"></script>

<?php include 'includes/footer.php'; ?> 