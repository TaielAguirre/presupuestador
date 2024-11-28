<?php
namespace App;

require_once '../vendor/autoload.php';
require_once '../includes/db.php';
require_once '../includes/middleware.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use \Exception;

header('Content-Type: application/json');

try {
    verificarPermiso('material_validar');
    
    if (!isset($_FILES['archivo_flexxus'])) {
        throw new Exception('No se ha proporcionado el archivo de Flexxus');
    }

    $archivo = $_FILES['archivo_flexxus'];
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, ['xlsx', 'xls'])) {
        throw new Exception('El archivo debe ser un Excel (.xlsx o .xls)');
    }

    // Cargar Excel
    $spreadsheet = IOFactory::load($archivo['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $codigosFlexxus = [];

    // Leer códigos del Excel
    foreach ($worksheet->getRowIterator() as $row) {
        $rowIndex = $row->getRowIndex();
        if ($rowIndex === 1) continue; // Saltar encabezados

        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        $cells = iterator_to_array($cellIterator);
        
        $codigo = $cells[0]->getValue();
        $descripcion = $cells[1]->getValue();
        
        if ($codigo) {
            $codigosFlexxus[$codigo] = $descripcion;
        }
    }

    $pdo = getDB();
    
    // Obtener todos los materiales
    $stmt = $pdo->query("SELECT id, codigo, descripcion FROM materiales");
    $materiales = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    $resultados = [
        'validados' => [],
        'no_encontrados' => [],
        'diferentes_descripciones' => []
    ];

    // Validar cada material
    foreach ($materiales as $material) {
        $stmt = $pdo->prepare("
            UPDATE materiales 
            SET codigo_flexxus_validado = ?,
                ultima_validacion = NOW(),
                error_validacion = ?
            WHERE id = ?
        ");

        if (isset($codigosFlexxus[$material['codigo']])) {
            // El código existe en Flexxus
            if ($codigosFlexxus[$material['codigo']] === $material['descripcion']) {
                $stmt->execute([true, null, $material['id']]);
                $resultados['validados'][] = $material;
            } else {
                $error = "Descripción diferente en Flexxus: " . $codigosFlexxus[$material['codigo']];
                $stmt->execute([false, $error, $material['id']]);
                $resultados['diferentes_descripciones'][] = [
                    'material' => $material,
                    'descripcion_flexxus' => $codigosFlexxus[$material['codigo']]
                ];
            }
        } else {
            // El código no existe en Flexxus
            $stmt->execute([false, "Código no encontrado en Flexxus", $material['id']]);
            $resultados['no_encontrados'][] = $material;
        }
    }

    echo json_encode([
        'success' => true,
        'mensaje' => 'Validación completada',
        'resultados' => $resultados
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 