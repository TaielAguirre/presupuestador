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
            verificarPermiso('usuario_ver');
            
            if (isset($_GET['id'])) {
                // Obtener rol específico con sus permisos
                $stmt = $pdo->prepare("
                    SELECT r.*, GROUP_CONCAT(p.codigo) as permisos
                    FROM roles r
                    LEFT JOIN rol_permiso rp ON r.id = rp.rol_id
                    LEFT JOIN permisos p ON rp.permiso_id = p.id
                    WHERE r.id = ?
                    GROUP BY r.id
                ");
                $stmt->execute([$_GET['id']]);
                $rol = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($rol) {
                    $rol['permisos'] = $rol['permisos'] ? explode(',', $rol['permisos']) : [];
                    echo json_encode([
                        'success' => true,
                        'rol' => $rol
                    ]);
                } else {
                    throw new Exception('Rol no encontrado');
                }
            } else {
                // Listar todos los roles
                $stmt = $pdo->query("
                    SELECT r.*, GROUP_CONCAT(p.codigo) as permisos
                    FROM roles r
                    LEFT JOIN rol_permiso rp ON r.id = rp.rol_id
                    LEFT JOIN permisos p ON rp.permiso_id = p.id
                    GROUP BY r.id
                    ORDER BY r.nivel DESC
                ");
                
                $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($roles as &$rol) {
                    $rol['permisos'] = $rol['permisos'] ? explode(',', $rol['permisos']) : [];
                }
                
                echo json_encode([
                    'success' => true,
                    'roles' => $roles
                ]);
            }
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