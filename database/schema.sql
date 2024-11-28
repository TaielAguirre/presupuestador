-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS presupuestador CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE presupuestador;

-- Tabla de estados de presupuesto
CREATE TABLE IF NOT EXISTS estados_presupuesto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT 'primary'
);

-- Insertar estados básicos
INSERT INTO estados_presupuesto (nombre, color) VALUES
('pendiente', 'warning'),
('aprobado', 'success'),
('rechazado', 'danger'),
('vencido', 'secondary');

-- Tabla de clientes
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razon_social VARCHAR(200) NOT NULL,
    cuit VARCHAR(20) NOT NULL,
    domicilio TEXT,
    localidad VARCHAR(100),
    telefono VARCHAR(50),
    contacto VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de materiales
CREATE TABLE IF NOT EXISTS materiales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL,
    descripcion TEXT NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    moneda ENUM('ARS', 'USD') DEFAULT 'ARS',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_codigo (codigo)
);

-- Tabla de presupuestos
CREATE TABLE IF NOT EXISTS presupuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20),
    cliente_id INT NOT NULL,
    fecha DATE NOT NULL,
    fecha_validez DATE,
    estado_id INT NOT NULL DEFAULT 1,
    moneda ENUM('ARS', 'USD') DEFAULT 'ARS',
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (estado_id) REFERENCES estados_presupuesto(id)
);

-- Tabla de items de presupuesto
CREATE TABLE IF NOT EXISTS items_presupuesto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presupuesto_id INT NOT NULL,
    material_id INT NOT NULL,
    cantidad DECIMAL(15,2) NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materiales(id)
);

-- Tabla de cotizaciones
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor_divisa DECIMAL(15,2) NOT NULL,
    valor_billete DECIMAL(15,2) NOT NULL,
    variacion_divisa DECIMAL(5,2),
    variacion_billete DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de historial de precios
CREATE TABLE IF NOT EXISTS historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    precio_anterior DECIMAL(15,2) NOT NULL,
    precio_nuevo DECIMAL(15,2) NOT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    motivo TEXT,
    FOREIGN KEY (material_id) REFERENCES materiales(id)
);

-- Tabla de exportaciones a Flexxus
CREATE TABLE IF NOT EXISTS exportaciones_flexxus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    presupuesto_id INT NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    fecha_exportacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id)
);

-- Trigger para generar número de presupuesto
DELIMITER //
CREATE TRIGGER IF NOT EXISTS generar_numero_presupuesto
BEFORE INSERT ON presupuestos
FOR EACH ROW
BEGIN
    DECLARE next_num INT;
    SET next_num = (SELECT COALESCE(MAX(CAST(SUBSTRING(numero, 1, 8) AS UNSIGNED)), 0) + 1 
                    FROM presupuestos 
                    WHERE YEAR(fecha) = YEAR(NEW.fecha));
    SET NEW.numero = CONCAT(
        LPAD(next_num, 8, '0'),
        '/',
        DATE_FORMAT(NEW.fecha, '%Y')
    );
END;//
DELIMITER ;