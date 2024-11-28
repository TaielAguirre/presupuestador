<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // GET - Obtener presupuestos
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Obtener últimos presupuestos para el dashboard
        if (isset($_GET['ultimos'])) {
            $limit = intval($_GET['ultimos']);
            $presupuestos = $db->fetchAll(
                "SELECT p.*, c.razon_social as cliente_nombre, e.nombre as estado,
                        (SELECT SUM(i.cantidad * i.precio_unitario) 
                         FROM items_presupuesto i 
                         WHERE i.presupuesto_id = p.id) as total
                FROM presupuestos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN estados_presupuesto e ON p.estado_id = e.id
                ORDER BY p.fecha DESC
                LIMIT ?",
                [$limit]
            );
            
            echo json_encode([
                'success' => true,
                'data' => $presupuestos
            ]);
            exit;
        }
        
        // Obtener presupuestos por vencer
        if (isset($_GET['por_vencer'])) {
            $presupuestos = $db->fetchAll(
                "SELECT p.*, c.razon_social as cliente_nombre, e.nombre as estado,
                        DATEDIFF(p.fecha_validez, CURRENT_DATE) as dias_restantes,
                        (SELECT SUM(i.cantidad * i.precio_unitario) 
                         FROM items_presupuesto i 
                         WHERE i.presupuesto_id = p.id) as total
                FROM presupuestos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN estados_presupuesto e ON p.estado_id = e.id
                WHERE p.fecha_validez IS NOT NULL 
                AND p.fecha_validez >= CURRENT_DATE
                AND p.fecha_validez <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)
                AND p.estado_id = (SELECT id FROM estados_presupuesto WHERE nombre = 'pendiente')
                ORDER BY p.fecha_validez ASC
                LIMIT 5"
            );
            
            echo json_encode([
                'success' => true,
                'data' => $presupuestos
            ]);
            exit;
        }
        
        // Obtener un presupuesto específico
        if (isset($_GET['id'])) {
            $presupuesto = $db->fetchOne(
                "SELECT p.*, c.razon_social as cliente_nombre, c.cuit, c.domicilio, 
                        c.localidad, c.telefono, c.contacto,
                        e.nombre as estado
                FROM presupuestos p 
                LEFT JOIN clientes c ON p.cliente_id = c.id
                LEFT JOIN estados_presupuesto e ON p.estado_id = e.id
                WHERE p.id = ?",
                [$_GET['id']]
            );
            
            if (!$presupuesto) {
                throw new \Exception('Presupuesto no encontrado');
            }
            
            $items = $db->fetchAll(
                "SELECT i.*, m.codigo, m.descripcion 
                FROM items_presupuesto i 
                LEFT JOIN materiales m ON i.material_id = m.id 
                WHERE i.presupuesto_id = ? 
                ORDER BY i.orden",
                [$_GET['id']]
            );
            
            $presupuesto['items'] = $items;
            
            echo json_encode([
                'success' => true,
                'data' => $presupuesto
            ]);
            exit;
        }
        
        // Listar todos los presupuestos
        $presupuestos = $db->fetchAll(
            "SELECT p.*, c.razon_social as cliente_nombre, e.nombre as estado,
                    (SELECT SUM(i.cantidad * i.precio_unitario) 
                     FROM items_presupuesto i 
                     WHERE i.presupuesto_id = p.id) as total
            FROM presupuestos p 
            LEFT JOIN clientes c ON p.cliente_id = c.id
            LEFT JOIN estados_presupuesto e ON p.estado_id = e.id
            ORDER BY p.fecha DESC"
        );
        
        echo json_encode([
            'success' => true,
            'data' => $presupuestos
        ]);
        exit;
    }

    // POST - Crear o actualizar presupuesto
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['cliente_id']) || !isset($data['fecha']) || !isset($data['items'])) {
            throw new \Exception('Faltan datos requeridos');
        }
        
        // Validar cliente
        $cliente = $db->fetchOne(
            "SELECT id FROM clientes WHERE id = ?",
            [$data['cliente_id']]
        );
        
        if (!$cliente) {
            throw new \Exception('Cliente no válido');
        }
        
        // Validar items
        if (empty($data['items'])) {
            throw new \Exception('Debe incluir al menos un item');
        }
        
        // Iniciar transacción
        $db->getConnection()->beginTransaction();
        
        try {
            $presupuestoData = [
                'cliente_id' => $data['cliente_id'],
                'fecha' => $data['fecha'],
                'estado_id' => $data['estado_id'] ?? 1, // Pendiente por defecto
                'moneda' => $data['moneda'] ?? 'ARS',
                'observaciones' => $data['observaciones'] ?? null,
                'fecha_validez' => $data['fecha_validez'] ?? null
            ];
            
            // Crear o actualizar presupuesto
            if (isset($data['id'])) {
                // Verificar que el presupuesto existe
                $presupuesto = $db->fetchOne(
                    "SELECT id FROM presupuestos WHERE id = ?",
                    [$data['id']]
                );
                
                if (!$presupuesto) {
                    throw new \Exception('Presupuesto no encontrado');
                }
                
                $db->update('presupuestos', $presupuestoData, 'id = ?', [$data['id']]);
                $presupuestoId = $data['id'];
                
                // Eliminar items existentes
                $db->delete('items_presupuesto', 'presupuesto_id = ?', [$presupuestoId]);
            } else {
                $presupuestoId = $db->insert('presupuestos', $presupuestoData);
            }
            
            // Insertar items
            foreach ($data['items'] as $index => $item) {
                // Validar material si existe
                if (isset($item['material_id'])) {
                    $material = $db->fetchOne(
                        "SELECT id FROM materiales WHERE id = ?",
                        [$item['material_id']]
                    );
                    
                    if (!$material) {
                        throw new \Exception('Material no válido: ' . $item['material_id']);
                    }
                }
                
                $db->insert('items_presupuesto', [
                    'presupuesto_id' => $presupuestoId,
                    'material_id' => $item['material_id'] ?? null,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'orden' => $index + 1
                ]);
            }
            
            $db->getConnection()->commit();
            
            echo json_encode([
                'success' => true,
                'id' => $presupuestoId,
                'mensaje' => isset($data['id']) ? 'Presupuesto actualizado exitosamente' : 'Presupuesto creado exitosamente'
            ]);
        } catch (\Exception $e) {
            $db->getConnection()->rollBack();
            throw $e;
        }
        exit;
    }

    // DELETE - Eliminar presupuesto
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new \Exception('ID no especificado');
        }
        
        // Verificar que el presupuesto existe y está en estado borrador
        $presupuesto = $db->fetchOne(
            "SELECT p.id, e.nombre as estado 
            FROM presupuestos p 
            JOIN estados_presupuesto e ON p.estado_id = e.id 
            WHERE p.id = ?",
            [$_GET['id']]
        );
        
        if (!$presupuesto) {
            throw new \Exception('Presupuesto no encontrado');
        }
        
        if ($presupuesto['estado'] !== 'pendiente') {
            throw new \Exception('Solo se pueden eliminar presupuestos en estado pendiente');
        }
        
        $db->getConnection()->beginTransaction();
        
        try {
            // Eliminar items
            $db->delete('items_presupuesto', 'presupuesto_id = ?', [$_GET['id']]);
            
            // Eliminar presupuesto
            $db->delete('presupuestos', 'id = ?', [$_GET['id']]);
            
            $db->getConnection()->commit();
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Presupuesto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            $db->getConnection()->rollBack();
            throw $e;
        }
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