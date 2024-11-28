<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

function handleGet($conn) {
    if (isset($_GET['id'])) {
        // Obtener una categoría específica
        $id = $_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM categorias_presupuesto WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        // Listar todas las categorías
        $result = $conn->query("SELECT * FROM categorias_presupuesto ORDER BY orden, nombre");
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }
}

function handlePost($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Validar orden único
        if (!empty($data['orden'])) {
            $stmt = $conn->prepare("SELECT id FROM categorias_presupuesto WHERE orden = ?");
            $stmt->bind_param("i", $data['orden']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Ya existe una categoría con ese orden');
            }
        }

        // Insertar categoría
        $stmt = $conn->prepare("INSERT INTO categorias_presupuesto (nombre, descripcion, orden, mostrar_subtotal) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", 
            $data['nombre'],
            $data['descripcion'],
            $data['orden'],
            $data['mostrar_subtotal']
        );
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Categoría creada exitosamente',
            'id' => $conn->insert_id
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al crear la categoría: ' . $e->getMessage()
        ]);
    }
}

function handlePut($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        // Validar orden único
        if (!empty($data['orden'])) {
            $stmt = $conn->prepare("SELECT id FROM categorias_presupuesto WHERE orden = ? AND id != ?");
            $stmt->bind_param("ii", $data['orden'], $data['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Ya existe una categoría con ese orden');
            }
        }

        // Actualizar categoría
        $stmt = $conn->prepare("UPDATE categorias_presupuesto SET nombre = ?, descripcion = ?, orden = ?, mostrar_subtotal = ? WHERE id = ?");
        $stmt->bind_param("ssisi", 
            $data['nombre'],
            $data['descripcion'],
            $data['orden'],
            $data['mostrar_subtotal'],
            $data['id']
        );
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Categoría actualizada exitosamente'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al actualizar la categoría: ' . $e->getMessage()
        ]);
    }
}

function handleDelete($conn) {
    $id = $_GET['id'];
    
    try {
        // Verificar si hay presupuestos usando esta categoría
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM presupuestos WHERE categoria_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['total'] > 0) {
            throw new Exception('No se puede eliminar la categoría porque está siendo utilizada en presupuestos');
        }

        // Eliminar categoría
        $stmt = $conn->prepare("DELETE FROM categorias_presupuesto WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Categoría eliminada exitosamente'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error al eliminar la categoría: ' . $e->getMessage()
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