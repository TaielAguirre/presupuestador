<?php
namespace App;
require_once '../includes/Database.php';

use \PDO;
use \Exception;

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $data = json_decode(file_get_contents('php://input'), true);
    
    $db->getConnection()->beginTransaction();
    
    foreach ($data['materiales'] as $material) {
        $db->update(
            'materiales',
            ['precio_unitario' => $material['precio_nuevo']],
            'id = ?',
            [$material['id']]
        );
        
        // Registrar en historial
        $db->insert('historial_precios', [
            'material_id' => $material['id'],
            'precio_anterior' => $material['precio_anterior'],
            'precio_nuevo' => $material['precio_nuevo'],
            'fecha_cambio' => date('Y-m-d H:i:s'),
            'motivo' => $data['motivo'] ?? 'Actualización masiva'
        ]);
    }
    
    $db->getConnection()->commit();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Precios actualizados correctamente'
    ]);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->getConnection()->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 