<?php
require_once '../includes/db.php';
require_once '../includes/middleware.php';

use function App\verificarPermiso;

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            verificarPermiso('material_ver');
            
            if (isset($_GET['id'])) {
                // Obtener proveedor específico
                $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = ? AND activo = 1");
                $stmt->execute([$_GET['id']]);
                $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($proveedor) {
                    echo json_encode([
                        'success' => true,
                        'proveedor' => $proveedor
                    ]);
                } else {
                    throw new Exception('Proveedor no encontrado');
                }
            } else {
                // Listar todos los proveedores activos
                $stmt = $pdo->query("SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre");
                echo json_encode([
                    'success' => true,
                    'proveedores' => $stmt->fetchAll(PDO::FETCH_ASSOC)
                ]);
            }
            break;

        case 'POST':
            verificarPermiso('material_crear');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                INSERT INTO proveedores (
                    nombre, cuit, domicilio, localidad, 
                    telefono, email, contacto, notas
                ) VALUES (
                    :nombre, :cuit, :domicilio, :localidad,
                    :telefono, :email, :contacto, :notas
                )
            ");
            
            $stmt->execute([
                'nombre' => $data['nombre'],
                'cuit' => $data['cuit'],
                'domicilio' => $data['domicilio'],
                'localidad' => $data['localidad'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'contacto' => $data['contacto'],
                'notas' => $data['notas']
            ]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Proveedor creado exitosamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            verificarPermiso('material_editar');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE proveedores SET
                    nombre = :nombre,
                    cuit = :cuit,
                    domicilio = :domicilio,
                    localidad = :localidad,
                    telefono = :telefono,
                    email = :email,
                    contacto = :contacto,
                    notas = :notas
                WHERE id = :id AND activo = 1
            ");
            
            $stmt->execute([
                'id' => $data['id'],
                'nombre' => $data['nombre'],
                'cuit' => $data['cuit'],
                'domicilio' => $data['domicilio'],
                'localidad' => $data['localidad'],
                'telefono' => $data['telefono'],
                'email' => $data['email'],
                'contacto' => $data['contacto'],
                'notas' => $data['notas']
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Proveedor no encontrado o sin cambios');
            }
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Proveedor actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            verificarPermiso('material_eliminar');
            
            if (!isset($_GET['id'])) {
                throw new Exception('ID no especificado');
            }
            
            // Eliminación lógica
            $stmt = $pdo->prepare("UPDATE proveedores SET activo = 0 WHERE id = ? AND activo = 1");
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Proveedor no encontrado');
            }
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Proveedor eliminado exitosamente'
            ]);
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'mensaje' => 'Método no permitido'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 