<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

function handleGet($conn) {
    if (isset($_GET['id'])) {
        // Obtener una plantilla específica con sus items
        $id = $_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM plantillas_presupuesto WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $plantilla = $result->fetch_assoc();

        if ($plantilla) {
            // Obtener items de la plantilla
            $stmt = $conn->prepare("SELECT * FROM items_plantilla WHERE plantilla_id = ? ORDER BY orden");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $plantilla['items'] = $items;
        }

        echo json_encode($plantilla);
    } else {
        // Listar todas las plantillas
        $result = $conn->query("SELECT * FROM plantillas_presupuesto WHERE activo = 1 ORDER BY nombre");
        $plantillas = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($plantillas);
    }
}

function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO plantillas_presupuesto (nombre, descripcion, condiciones_pago, plazo_entrega, notas) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", 
            $data['nombre'],
            $data['descripcion'],
            $data['condiciones_pago'],
            $data['plazo_entrega'],
            $data['notas']
        );
        $stmt->execute();
        $plantillaId = $conn->insert_id;

        if (isset($data['items']) && is_array($data['items'])) {
            $stmt = $conn->prepare("INSERT INTO items_plantilla (plantilla_id, material_id, cantidad, descripcion, precio_usd, precio_ars, descuento1, descuento2, orden, categoria_item, subtotal_grupo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($data['items'] as $item) {
                $stmt->bind_param("iidsddddiss",
                    $plantillaId,
                    $item['material_id'],
                    $item['cantidad'],
                    $item['descripcion'],
                    $item['precio_usd'],
                    $item['precio_ars'],
                    $item['descuento1'],
                    $item['descuento2'],
                    $item['orden'],
                    $item['categoria_item'],
                    $item['subtotal_grupo']
                );
                $stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'mensaje' => 'Plantilla creada exitosamente',
            'id' => $plantillaId
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al crear la plantilla: ' . $e->getMessage()
        ]);
    }
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE plantillas_presupuesto SET nombre = ?, descripcion = ?, condiciones_pago = ?, plazo_entrega = ?, notas = ? WHERE id = ?");
        $stmt->bind_param("sssssi", 
            $data['nombre'],
            $data['descripcion'],
            $data['condiciones_pago'],
            $data['plazo_entrega'],
            $data['notas'],
            $data['id']
        );
        $stmt->execute();

        // Eliminar items existentes
        $stmt = $conn->prepare("DELETE FROM items_plantilla WHERE plantilla_id = ?");
        $stmt->bind_param("i", $data['id']);
        $stmt->execute();

        // Insertar nuevos items
        if (isset($data['items']) && is_array($data['items'])) {
            $stmt = $conn->prepare("INSERT INTO items_plantilla (plantilla_id, material_id, cantidad, descripcion, precio_usd, precio_ars, descuento1, descuento2, orden, categoria_item, subtotal_grupo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($data['items'] as $item) {
                $stmt->bind_param("iidsddddiss",
                    $data['id'],
                    $item['material_id'],
                    $item['cantidad'],
                    $item['descripcion'],
                    $item['precio_usd'],
                    $item['precio_ars'],
                    $item['descuento1'],
                    $item['descuento2'],
                    $item['orden'],
                    $item['categoria_item'],
                    $item['subtotal_grupo']
                );
                $stmt->execute();
            }
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'mensaje' => 'Plantilla actualizada exitosamente'
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al actualizar la plantilla: ' . $e->getMessage()
        ]);
    }
}

function handleDelete($conn) {
    $id = $_GET['id'];
    
    try {
        // Soft delete - solo marcar como inactivo
        $stmt = $conn->prepare("UPDATE plantillas_presupuesto SET activo = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'mensaje' => 'Plantilla eliminada exitosamente'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al eliminar la plantilla: ' . $e->getMessage()
        ]);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        handleGet($conn);
        break;
    case 'POST':
        handlePost($conn);
        break;
    case 'PUT':
        handlePut($conn);
        break;
    case 'DELETE':
        handleDelete($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
} 