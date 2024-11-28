<?php
namespace App;
require_once '../includes/Database.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use \Exception;
use \PDO;

header('Content-Type: application/json');

if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    try {
        $db = Database::getInstance();
        $inputFileName = $_FILES['archivo']['tmp_name'];
        $spreadsheet = IOFactory::load($inputFileName);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Remover encabezados
        array_shift($rows);
        
        $db->getConnection()->beginTransaction();
        
        foreach ($rows as $row) {
            if (empty($row[0])) continue; // Saltar filas vacías
            
            $db->insert('materiales', [
                'codigo' => $row[0],
                'descripcion' => $row[1],
                'precio_unitario' => $row[2],
                'moneda' => $row[3] ?? 'ARS'
            ]);
        }
        
        $db->getConnection()->commit();
        
        echo json_encode([
            'success' => true,
            'mensaje' => 'Materiales importados correctamente'
        ]);
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->getConnection()->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'mensaje' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al subir el archivo'
    ]);
} 