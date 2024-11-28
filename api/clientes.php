<?php
namespace App;
require_once '../includes/Database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // GET - Buscar clientes
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['buscar'])) {
            $buscar = '%' . $_GET['buscar'] . '%';
            $clientes = $db->fetchAll(
                "SELECT * FROM clientes WHERE razon_social LIKE ? OR cuit LIKE ? LIMIT 10",
                [$buscar, $buscar]
            );
            echo json_encode([
                'success' => true,
                'clientes' => $clientes
            ]);
            exit;
        }
        
        // Listar todos los clientes
        $clientes = $db->fetchAll("SELECT * FROM clientes ORDER BY razon_social");
        echo json_encode([
            'success' => true,
            'clientes' => $clientes
        ]);
        exit;
    }

    // POST - Crear nuevo cliente
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['razon_social']) || !isset($data['cuit'])) {
            throw new \Exception('Faltan datos requeridos');
        }
        
        $id = $db->insert('clientes', [
            'razon_social' => $data['razon_social'],
            'cuit' => $data['cuit'],
            'domicilio' => $data['domicilio'] ?? null,
            'localidad' => $data['localidad'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'contacto' => $data['contacto'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'mensaje' => 'Cliente creado exitosamente'
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