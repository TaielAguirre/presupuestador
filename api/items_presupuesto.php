<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // GET - Obtener items
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (!isset($_GET['presupuesto_id'])) {
            throw new \Exception('ID de presupuesto no especificado');
        }
        
        // Verificar que el presupuesto existe
        $presupuesto = $db->fetchOne(
            "SELECT id FROM presupuestos WHERE id = ?",
            [$_GET['presupuesto_id']]
        );
        
        if (!$presupuesto) {
            throw new \Exception('Presupuesto no encontrado');
        }
        
        // Obtener items con información completa
        $items = $db->fetchAll(
            "SELECT i.*, 
                    m.codigo, m.descripcion,
                    COALESCE(i.precio_unitario, m.precio_unitario) as precio_unitario,
                    m.moneda
            FROM items_presupuesto i 
            LEFT JOIN materiales m ON i.material_id = m.id 
            WHERE i.presupuesto_id = ? 
            ORDER BY i.orden",
            [$_GET['presupuesto_id']]
        );
        
        echo json_encode([
            'success' => true,
            'data' => $items
        ]);
        exit;
    }
    
    // POST - Agregar item
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['presupuesto_id'])) {
            throw new \Exception('ID de presupuesto no especificado');
        }
        
        // Verificar que el presupuesto existe
        $presupuesto = $db->fetchOne(
            "SELECT id, estado_id FROM presupuestos WHERE id = ?",
            [$data['presupuesto_id']]
        );
        
        if (!$presupuesto) {
            throw new \Exception('Presupuesto no encontrado');
        }
        
        // Verificar que el presupuesto esté en estado pendiente
        $estado = $db->fetchOne(
            "SELECT nombre FROM estados_presupuesto WHERE id = ?",
            [$presupuesto['estado_id']]
        );
        
        if ($estado['nombre'] !== 'pendiente') {
            throw new \Exception('Solo se pueden modificar presupuestos en estado pendiente');
        }
        
        // Si se especifica un material, validar que exista
        if (isset($data['material_id'])) {
            $material = $db->fetchOne(
                "SELECT id, precio_unitario, moneda FROM materiales WHERE id = ?",
                [$data['material_id']]
            );
            
            if (!$material) {
                throw new \Exception('Material no encontrado');
            }
            
            // Usar el precio del material si no se especifica uno
            if (!isset($data['precio_unitario'])) {
                $data['precio_unitario'] = $material['precio_unitario'];
            }
        }
        
        // Obtener el siguiente orden
        $orden = $db->fetchOne(
            "SELECT COALESCE(MAX(orden), 0) + 1 as siguiente_orden 
            FROM items_presupuesto 
            WHERE presupuesto_id = ?",
            [$data['presupuesto_id']]
        )['siguiente_orden'];
        
        // Insertar item
        $itemId = $db->insert('items_presupuesto', [
            'presupuesto_id' => $data['presupuesto_id'],
            'material_id' => $data['material_id'] ?? null,
            'cantidad' => $data['cantidad'] ?? 1,
            'precio_unitario' => $data['precio_unitario'],
            'orden' => $data['orden'] ?? $orden
        ]);
        
        echo json_encode([
            'success' => true,
            'id' => $itemId,
            'mensaje' => 'Item agregado exitosamente'
        ]);
        exit;
    }
    
    // PUT - Actualizar item
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if (!isset($_GET['id'])) {
            throw new \Exception('ID de item no especificado');
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Verificar que el item existe
        $item = $db->fetchOne(
            "SELECT i.*, p.estado_id 
            FROM items_presupuesto i
            JOIN presupuestos p ON i.presupuesto_id = p.id
            WHERE i.id = ?",
            [$_GET['id']]
        );
        
        if (!$item) {
            throw new \Exception('Item no encontrado');
        }
        
        // Verificar que el presupuesto esté en estado pendiente
        $estado = $db->fetchOne(
            "SELECT nombre FROM estados_presupuesto WHERE id = ?",
            [$item['estado_id']]
        );
        
        if ($estado['nombre'] !== 'pendiente') {
            throw new \Exception('Solo se pueden modificar items de presupuestos en estado pendiente');
        }
        
        // Actualizar item
        $db->update('items_presupuesto', [
            'cantidad' => $data['cantidad'],
            'precio_unitario' => $data['precio_unitario'],
            'orden' => $data['orden'] ?? $item['orden']
        ], 'id = ?', [$_GET['id']]);
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Item actualizado exitosamente'
        ]);
        exit;
    }
    
    // DELETE - Eliminar item
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new \Exception('ID de item no especificado');
        }
        
        // Verificar que el item existe y el estado del presupuesto
        $item = $db->fetchOne(
            "SELECT i.*, p.estado_id 
            FROM items_presupuesto i
            JOIN presupuestos p ON i.presupuesto_id = p.id
            WHERE i.id = ?",
            [$_GET['id']]
        );
        
        if (!$item) {
            throw new \Exception('Item no encontrado');
        }
        
        // Verificar que el presupuesto esté en estado pendiente
        $estado = $db->fetchOne(
            "SELECT nombre FROM estados_presupuesto WHERE id = ?",
            [$item['estado_id']]
        );
        
        if ($estado['nombre'] !== 'pendiente') {
            throw new \Exception('Solo se pueden eliminar items de presupuestos en estado pendiente');
        }
        
        // Eliminar item
        $db->delete('items_presupuesto', 'id = ?', [$_GET['id']]);
        
        // Reordenar items restantes
        $items = $db->fetchAll(
            "SELECT id, orden 
            FROM items_presupuesto 
            WHERE presupuesto_id = ? 
            ORDER BY orden",
            [$item['presupuesto_id']]
        );
        
        foreach ($items as $index => $item) {
            $db->update('items_presupuesto', 
                ['orden' => $index + 1], 
                'id = ?', 
                [$item['id']]
            );
        }
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Item eliminado exitosamente'
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