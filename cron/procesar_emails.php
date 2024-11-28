<?php
require_once '../includes/db.php';
require_once '../includes/Notificador.php';

use App\Notificador;

// Establecer tiempo límite de ejecución
set_time_limit(300); // 5 minutos

try {
    $notificador = new Notificador();
    $notificador->procesarEmailsPendientes();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Procesamiento de emails completado'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al procesar emails: ' . $e->getMessage()
    ]);
} 