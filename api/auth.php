<?php
header('Content-Type: application/json');

// Respuesta por defecto para mantener compatibilidad
echo json_encode([
    'success' => true,
    'usuario' => [
        'id' => 1,
        'nombre' => 'Usuario',
        'email' => 'usuario@sistema.com',
        'rol' => 'admin'
    ],
    'permisos' => ['*']
]); 