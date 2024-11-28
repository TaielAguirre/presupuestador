<?php
require_once '../includes/db.php';
require_once '../includes/Auth.php';
require_once '../includes/middleware.php';

use App\Auth;
use function App\verificarPermiso;

header('Content-Type: application/json');

try {
    $pdo = getDB();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Obtener usuario específico
                verificarPermiso('usuario_ver');
                
                $stmt = $pdo->prepare("
                    SELECT u.*, GROUP_CONCAT(ur.rol_id) as roles
                    FROM usuarios u
                    LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                    WHERE u.id = ?
                    GROUP BY u.id
                ");
                $stmt->execute([$_GET['id']]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    $usuario['roles'] = $usuario['roles'] ? explode(',', $usuario['roles']) : [];
                    echo json_encode([
                        'success' => true,
                        'usuario' => $usuario
                    ]);
                } else {
                    throw new Exception('Usuario no encontrado');
                }
            } else {
                // Listar usuarios
                verificarPermiso('usuario_ver');
                
                $stmt = $pdo->query("
                    SELECT 
                        u.*,
                        GROUP_CONCAT(DISTINCT r.nombre) as roles
                    FROM usuarios u
                    LEFT JOIN usuario_rol ur ON u.id = ur.usuario_id
                    LEFT JOIN roles r ON ur.rol_id = r.id
                    GROUP BY u.id
                    ORDER BY u.username
                ");
                
                echo json_encode([
                    'success' => true,
                    'usuarios' => $stmt->fetchAll(PDO::FETCH_ASSOC)
                ]);
            }
            break;

        case 'POST':
            // Crear usuario
            verificarPermiso('usuario_crear');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validaciones
            if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
                throw new Exception('Faltan datos requeridos');
            }
            
            // Verificar username único
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                throw new Exception('El nombre de usuario ya existe');
            }
            
            // Verificar email único
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                throw new Exception('El email ya está registrado');
            }
            
            $pdo->beginTransaction();
            
            // Insertar usuario
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (username, password, nombre, email, activo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['nombre'],
                $data['email'],
                $data['activo'] ?? true
            ]);
            
            $usuarioId = $pdo->lastInsertId();
            
            // Asignar roles
            if (!empty($data['roles'])) {
                $stmt = $pdo->prepare("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES (?, ?)");
                foreach ($data['roles'] as $rolId) {
                    $stmt->execute([$usuarioId, $rolId]);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Usuario creado exitosamente'
            ]);
            break;

        case 'PUT':
            // Actualizar usuario
            verificarPermiso('usuario_editar');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['id'])) {
                throw new Exception('ID de usuario no especificado');
            }
            
            $pdo->beginTransaction();
            
            // Actualizar datos básicos
            $sql = "UPDATE usuarios SET 
                    nombre = :nombre,
                    email = :email,
                    activo = :activo";
            
            // Agregar password solo si se proporciona uno nuevo
            if (!empty($data['password'])) {
                $sql .= ", password = :password";
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $params = [
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'activo' => $data['activo'],
                'id' => $data['id']
            ];
            
            if (!empty($data['password'])) {
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $stmt->execute($params);
            
            // Actualizar roles
            $stmt = $pdo->prepare("DELETE FROM usuario_rol WHERE usuario_id = ?");
            $stmt->execute([$data['id']]);
            
            if (!empty($data['roles'])) {
                $stmt = $pdo->prepare("INSERT INTO usuario_rol (usuario_id, rol_id) VALUES (?, ?)");
                foreach ($data['roles'] as $rolId) {
                    $stmt->execute([$data['id'], $rolId]);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Usuario actualizado exitosamente'
            ]);
            break;

        case 'DELETE':
            // Eliminar usuario
            verificarPermiso('usuario_eliminar');
            
            if (!isset($_GET['id'])) {
                throw new Exception('ID de usuario no especificado');
            }
            
            $pdo->beginTransaction();
            
            // Eliminar roles
            $stmt = $pdo->prepare("DELETE FROM usuario_rol WHERE usuario_id = ?");
            $stmt->execute([$_GET['id']]);
            
            // Eliminar usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'mensaje' => 'Usuario eliminado exitosamente'
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
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 