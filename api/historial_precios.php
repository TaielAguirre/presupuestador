<?php
namespace App;

require_once '../includes/db.php';
require_once '../includes/middleware.php';
require_once '../includes/Auth.php';
require_once '../includes/NotificadorMateriales.php';

use \PDO;
use \Exception;

header('Content-Type: application/json');

try {
    verificarPermiso('material_historial');
    $pdo = getDB();

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            if (!isset($_GET['material_id'])) {
                throw new Exception('ID de material no especificado');
            }

            // Obtener historial de precios con detalles adicionales
            $stmt = $pdo->prepare("
                SELECT 
                    h.*,
                    u.nombre as usuario_nombre,
                    u.email as usuario_email,
                    m.codigo as material_codigo,
                    m.descripcion as material_descripcion,
                    (h.precio_nuevo - h.precio_anterior) as diferencia,
                    ((h.precio_nuevo - h.precio_anterior) / h.precio_anterior * 100) as porcentaje_cambio
                FROM historial_precios h
                JOIN usuarios u ON h.usuario_id = u.id
                JOIN materiales m ON h.material_id = m.id
                WHERE h.material_id = ?
                ORDER BY h.fecha_cambio DESC
            ");
            
            $stmt->execute([$_GET['material_id']]);
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener estadísticas de cambios
            $stmt = $pdo->prepare("
                SELECT 
                    MIN(precio_anterior) as precio_minimo,
                    MAX(precio_nuevo) as precio_maximo,
                    AVG(precio_nuevo) as precio_promedio,
                    COUNT(*) as total_cambios,
                    MAX(fecha_cambio) as ultimo_cambio,
                    MIN(fecha_cambio) as primer_cambio
                FROM historial_precios
                WHERE material_id = ?
            ");
            
            $stmt->execute([$_GET['material_id']]);
            $estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

            // Obtener datos actuales del material
            $stmt = $pdo->prepare("
                SELECT 
                    m.*,
                    COUNT(i.id) as veces_usado,
                    MAX(p.fecha_creacion) as ultimo_uso
                FROM materiales m
                LEFT JOIN items_presupuesto i ON m.id = i.material_id
                LEFT JOIN presupuestos p ON i.presupuesto_id = p.id
                WHERE m.id = ?
                GROUP BY m.id
            ");
            
            $stmt->execute([$_GET['material_id']]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);

            // Calcular tendencia de precios
            $tendencia = null;
            if (count($historial) >= 2) {
                $primeros = array_slice($historial, -5); // últimos 5 cambios
                $sumaVariaciones = 0;
                foreach ($primeros as $cambio) {
                    $sumaVariaciones += $cambio['porcentaje_cambio'];
                }
                $tendencia = $sumaVariaciones / count($primeros);
            }

            echo json_encode([
                'success' => true,
                'material' => $material,
                'historial' => $historial,
                'estadisticas' => $estadisticas,
                'tendencia' => $tendencia
            ]);
            break;

        case 'POST':
            verificarPermiso('material_editar');
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['material_id']) || !isset($data['precio_nuevo'])) {
                throw new Exception('Datos incompletos');
            }

            // Validar precio nuevo
            if ($data['precio_nuevo'] <= 0) {
                throw new Exception('El precio debe ser mayor a cero');
            }

            // Obtener precio actual y datos del material
            $stmt = $pdo->prepare("
                SELECT m.*, 
                       (SELECT precio_nuevo 
                        FROM historial_precios 
                        WHERE material_id = m.id 
                        ORDER BY fecha_cambio DESC 
                        LIMIT 1) as ultimo_precio_historico
                FROM materiales m
                WHERE m.id = ?
            ");
            $stmt->execute([$data['material_id']]);
            $material = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$material) {
                throw new Exception('Material no encontrado');
            }

            $precioActual = $material['precio_unitario'];
            $variacionPorcentaje = (($data['precio_nuevo'] - $precioActual) / $precioActual) * 100;

            // Validar cambio significativo
            if (abs($variacionPorcentaje) > 50) {
                // Registrar alerta por cambio significativo
                $notificador = NotificadorMateriales::getInstance();
                $notificador->notificarCambioPrecioSignificativo(
                    $data['material_id'],
                    $precioActual,
                    $data['precio_nuevo'],
                    $variacionPorcentaje
                );
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            try {
                // Registrar cambio en historial
                $stmt = $pdo->prepare("
                    INSERT INTO historial_precios (
                        material_id,
                        precio_anterior,
                        precio_nuevo,
                        usuario_id,
                        motivo
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $data['material_id'],
                    $precioActual,
                    $data['precio_nuevo'],
                    1, // Usuario por defecto
                    $data['motivo'] ?? null
                ]);

                // Actualizar precio en materiales
                $stmt = $pdo->prepare("
                    UPDATE materiales 
                    SET precio_unitario = ?,
                        fecha_ultima_actualizacion = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([$data['precio_nuevo'], $data['material_id']]);

                // Notificar cambio de precio
                $notificador = NotificadorMateriales::getInstance();
                $notificador->notificarCambioPrecio(
                    $data['material_id'],
                    $precioActual,
                    $data['precio_nuevo']
                );

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'mensaje' => 'Precio actualizado correctamente',
                    'variacion_porcentaje' => $variacionPorcentaje
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception('Método no permitido');
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