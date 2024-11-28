<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Obtener estadísticas
    $stats = [];
    
    // Presupuestos del mes actual
    $stats['presupuestos_mes'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM presupuestos WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE())"
    )['total'];
    
    // Presupuestos aprobados
    $stats['presupuestos_aprobados'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM presupuestos WHERE estado_id = (SELECT id FROM estados_presupuesto WHERE nombre = 'aprobado')"
    )['total'];
    
    // Clientes activos (con presupuestos en los últimos 6 meses)
    $stats['clientes_activos'] = $db->fetchOne(
        "SELECT COUNT(DISTINCT cliente_id) as total FROM presupuestos WHERE fecha >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)"
    )['total'];
    
    // Total de materiales
    $stats['total_materiales'] = $db->fetchOne(
        "SELECT COUNT(*) as total FROM materiales"
    )['total'];
    
    // Monto total presupuestado en el mes (ARS)
    $stats['monto_total_mes_ars'] = $db->fetchOne(
        "SELECT COALESCE(SUM(i.cantidad * i.precio_unitario), 0) as total 
        FROM presupuestos p 
        JOIN items_presupuesto i ON p.id = i.presupuesto_id 
        WHERE MONTH(p.fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(p.fecha) = YEAR(CURRENT_DATE())
        AND p.moneda = 'ARS'"
    )['total'];
    
    // Monto total presupuestado en el mes (USD)
    $stats['monto_total_mes_usd'] = $db->fetchOne(
        "SELECT COALESCE(SUM(i.cantidad * i.precio_unitario), 0) as total 
        FROM presupuestos p 
        JOIN items_presupuesto i ON p.id = i.presupuesto_id 
        WHERE MONTH(p.fecha) = MONTH(CURRENT_DATE()) 
        AND YEAR(p.fecha) = YEAR(CURRENT_DATE())
        AND p.moneda = 'USD'"
    )['total'];
    
    // Presupuestos por estado
    $stats['presupuestos_por_estado'] = $db->fetchAll(
        "SELECT e.nombre, COUNT(*) as cantidad 
        FROM presupuestos p 
        JOIN estados_presupuesto e ON p.estado_id = e.id 
        GROUP BY e.nombre"
    );
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);

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