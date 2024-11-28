<?php
require_once '../includes/db.php';
header('Content-Type: application/json');

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener una categoría específica
                $stmt = $pdo->prepare("SELECT * FROM categorias_presupuesto WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            } else {
                // Listar todas las categorías
                $stmt = $pdo->query("SELECT * FROM categorias_presupuesto ORDER BY orden, nombre");
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar orden único
            if (!empty($data['orden'])) {
                $stmt = $pdo->prepare("SELECT id FROM categorias_presupuesto WHERE orden = ?");
                $stmt->execute([$data['orden']]);
                if ($stmt->fetch()) {
                    throw new Exception('Ya existe una categoría con ese orden');
                }
            }

            // Insertar categoría
            $stmt = $pdo->prepare("
                INSERT INTO categorias_presupuesto (
                    nombre, descripcion, orden, mostrar_subtotal, activo
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['orden'],
                $data['mostrar_subtotal'],
                $data['activo']
            ]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Categoría creada exitosamente',
                'id' => $pdo->lastInsertId()
            ]);
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar orden único
            if (!empty($data['orden'])) {
                $stmt = $pdo->prepare("SELECT id FROM categorias_presupuesto WHERE orden = ? AND id != ?");
                $stmt->execute([$data['orden'], $data['id']]);
                if ($stmt->fetch()) {
                    throw new Exception('Ya existe una categoría con ese orden');
                }
            }

            // Actualizar categoría
            $stmt = $pdo->prepare("
                UPDATE categorias_presupuesto 
                SET nombre = ?, descripcion = ?, orden = ?, 
                    mostrar_subtotal = ?, activo = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['nombre'],
                $data['descripcion'],
                $data['orden'],
                $data['mostrar_subtotal'],
                $data['activo'],
                $data['id']
            ]);
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Categoría actualizada exitosamente'
            ]);
            break;

        case 'PATCH':
            // Actualizar orden de múltiples categorías
            $data = json_decode(file_get_contents('php://input'), true);
            
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE categorias_presupuesto SET orden = ? WHERE id = ?");
                
                foreach ($data['orden'] as $item) {
                    $stmt->execute([$item['orden'], $item['id']]);
                }
                
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Orden actualizado exitosamente'
                ]);
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('ID de categoría no especificado');
            }

            // Verificar si hay presupuestos usando esta categoría
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM items_presupuesto 
                WHERE categoria_item = (
                    SELECT nombre 
                    FROM categorias_presupuesto 
                    WHERE id = ?
                )
            ");
            $stmt->execute([$_GET['id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                // Si hay items usando la categoría, marcarla como inactiva
                $stmt = $pdo->prepare("UPDATE categorias_presupuesto SET activo = 0 WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Categoría marcada como inactiva'
                ]);
            } else {
                // Si no hay items, eliminar la categoría
                $stmt = $pdo->prepare("DELETE FROM categorias_presupuesto WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Categoría eliminada exitosamente'
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 