<?php
require_once '../includes/db.php';
require_once '../includes/Notificador.php';

use App\Notificador;

header('Content-Type: application/json');

try {
    $notificador = new Notificador();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Obtener notificaciones del usuario
            $usuario = $_GET['usuario'] ?? null;
            $solo_no_leidas = isset($_GET['no_leidas']) ? (bool)$_GET['no_leidas'] : true;
            
            if (!$usuario) {
                throw new Exception('Usuario no especificado');
            }
            
            $notificaciones = $notificador->obtenerNotificacionesUsuario($usuario, $solo_no_leidas);
            echo json_encode([
                'success' => true,
                'notificaciones' => $notificaciones
            ]);
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (isset($data['marcar_leida'])) {
                // Marcar notificación como leída
                $notificador->marcarComoLeida($data['notificacion_id']);
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Notificación marcada como leída'
                ]);
            } else {
                // Crear nueva notificación
                $notificador->notificar(
                    $data['tipo_evento'],
                    $data['datos'],
                    $data['usuario_destino'] ?? null
                );
                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Notificación creada exitosamente'
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