<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // GET - Obtener estados
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $estados = $db->fetchAll(
            "SELECT * FROM estados_presupuesto ORDER BY id"
        );
        
        echo json_encode([
            'success' => true,
            'data' => $estados
        ]);
        exit;
    }
    
    // POST - Cambiar estado de un presupuesto
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['presupuesto_id']) || !isset($data['estado_id'])) {
            throw new \Exception('Faltan datos requeridos');
        }
        
        // Verificar que el presupuesto existe
        $presupuesto = $db->fetchOne(
            "SELECT id FROM presupuestos WHERE id = ?",
            [$data['presupuesto_id']]
        );
        
        if (!$presupuesto) {
            throw new \Exception('Presupuesto no encontrado');
        }
        
        // Verificar que el estado existe
        $estado = $db->fetchOne(
            "SELECT id, nombre FROM estados_presupuesto WHERE id = ?",
            [$data['estado_id']]
        );
        
        if (!$estado) {
            throw new \Exception('Estado no válido');
        }
        
        // Actualizar estado
        $db->update('presupuestos', 
            ['estado_id' => $data['estado_id']], 
            'id = ?', 
            [$data['presupuesto_id']]
        );
        
        echo json_encode([
            'success' => true,
            'mensaje' => "Estado actualizado a '{$estado['nombre']}' exitosamente"
        ]);
        exit;
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (\Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 