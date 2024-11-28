<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // GET - Obtener cotizaciones
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Si se solicita actualizar, obtener nuevos valores
        if (isset($_GET['actualizar']) && $_GET['actualizar'] === 'true') {
            // Aquí podrías implementar la lógica para obtener cotizaciones de una API externa
            // Por ahora, usaremos valores de ejemplo
            $cotizacion = [
                'valor_divisa' => 850.50,
                'valor_billete' => 920.00,
                'variacion_divisa' => 0.75,
                'variacion_billete' => 1.25,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            // Guardar en la base de datos
            $db->insert('cotizaciones', $cotizacion);
        } else {
            // Obtener la última cotización registrada
            $cotizacion = $db->fetchOne(
                "SELECT * FROM cotizaciones ORDER BY created_at DESC LIMIT 1"
            );
            
            if (!$cotizacion) {
                $cotizacion = [
                    'valor_divisa' => 850.50,
                    'valor_billete' => 920.00,
                    'variacion_divisa' => 0,
                    'variacion_billete' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $db->insert('cotizaciones', $cotizacion);
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $cotizacion
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