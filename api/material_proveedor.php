<?php
require_once __DIR__ . '/../includes/config.php';
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
                // Obtener relación específica
                $stmt = $pdo->prepare("
                    SELECT mp.*, p.nombre as nombre_proveedor 
                    FROM material_proveedor mp
                    JOIN proveedores p ON p.id = mp.proveedor_id
                    WHERE mp.id = ? AND mp.activo = 1
                ");
                $stmt->execute([$_GET['id']]);
                $proveedor_material = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($proveedor_material) {
                    echo json_encode([
                        'success' => true,
                        'proveedor_material' => $proveedor_material
                    ]);
                } else {
                    throw new Exception('Relación no encontrada');
                }
            } else if (isset($_GET['material_id'])) {
                // Listar proveedores de un material
                $stmt = $pdo->prepare("
                    SELECT mp.*, p.nombre as nombre_proveedor 
                    FROM material_proveedor mp
                    JOIN proveedores p ON p.id = mp.proveedor_id
                    WHERE mp.material_id = ? AND mp.activo = 1
                    ORDER BY p.nombre
                ");
                $stmt->execute([$_GET['material_id']]);
                echo json_encode([
                    'success' => true,
                    'proveedores' => $stmt->fetchAll(PDO::FETCH_ASSOC)
                ]);
            } else {
                throw new Exception('ID no especificado');
            }
            break;

        case 'POST':
            verificarPermiso('material_crear');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Verificar si ya existe la relación
            $stmt = $pdo->prepare("
                SELECT id FROM material_proveedor 
                WHERE material_id = ? AND proveedor_id = ? AND activo = 1
            ");
            $stmt->execute([$data['material_id'], $data['proveedor_id']]);
            
            if ($stmt->fetch()) {
                throw new Exception('Este proveedor ya está asociado al material');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO material_proveedor (
                    material_id, proveedor_id, codigo_proveedor,
                    precio_unitario, moneda
                ) VALUES (
                    :material_id, :proveedor_id, :codigo_proveedor,
                    :precio_unitario, :moneda
                )
            ");
            
            $stmt->execute([
                'material_id' => $data['material_id'],
                'proveedor_id' => $data['proveedor_id'],
                'codigo_proveedor' => $data['codigo_proveedor'],
                'precio_unitario' => $data['precio_unitario_proveedor'],
                'moneda' => $data['moneda']
            ]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Proveedor asociado exitosamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            verificarPermiso('material_editar');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $pdo->prepare("
                UPDATE material_proveedor SET
                    proveedor_id = :proveedor_id,
                    codigo_proveedor = :codigo_proveedor,
                    precio_unitario = :precio_unitario,
                    moneda = :moneda
                WHERE id = :id AND activo = 1
            ");
            
            $stmt->execute([
                'id' => $data['material_proveedor_id'],
                'proveedor_id' => $data['proveedor_id'],
                'codigo_proveedor' => $data['codigo_proveedor'],
                'precio_unitario' => $data['precio_unitario_proveedor'],
                'moneda' => $data['moneda']
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Relación no encontrada o sin cambios');
            }
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Relación actualizada exitosamente'
            ]);
            break;

        case 'DELETE':
            verificarPermiso('material_eliminar');
            
            if (!isset($_GET['id'])) {
                throw new Exception('ID no especificado');
            }
            
            // Eliminación lógica
            $stmt = $pdo->prepare("UPDATE material_proveedor SET activo = 0 WHERE id = ? AND activo = 1");
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Relación no encontrada');
            }
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Relación eliminada exitosamente'
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