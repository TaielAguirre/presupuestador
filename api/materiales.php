<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $material = $db->fetchOne(
                    "SELECT * FROM materiales WHERE id = ?", 
                    [$_GET['id']]
                );
                
                if ($material) {
                    echo json_encode(['success' => true, 'material' => $material]);
                } else {
                    throw new \Exception('Material no encontrado');
                }
            } else {
                $materiales = $db->fetchAll("SELECT * FROM materiales ORDER BY codigo");
                echo json_encode(['success' => true, 'materiales' => $materiales]);
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos requeridos
            if (!isset($data['codigo']) || !isset($data['descripcion']) || !isset($data['precio_unitario'])) {
                throw new \Exception('Faltan datos requeridos');
            }
            
            $db->insert('materiales', [
                'codigo' => $data['codigo'],
                'descripcion' => $data['descripcion'],
                'precio_unitario' => $data['precio_unitario'],
                'moneda' => $data['moneda'] ?? 'ARS'
            ]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Material creado correctamente'
            ]);
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                throw new \Exception('ID no especificado');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $db->update(
                'materiales',
                [
                    'codigo' => $data['codigo'],
                    'descripcion' => $data['descripcion'],
                    'precio_unitario' => $data['precio_unitario'],
                    'moneda' => $data['moneda'] ?? 'ARS'
                ],
                'id = ?',
                [$_GET['id']]
            );
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Material actualizado correctamente'
            ]);
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new \Exception('ID no especificado');
            }
            
            $db->delete('materiales', 'id = ?', [$_GET['id']]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Material eliminado correctamente'
            ]);
            break;

        default:
            throw new \Exception('Método no permitido');
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 