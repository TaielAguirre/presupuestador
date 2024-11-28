<?php
namespace App;

require_once '../vendor/autoload.php';
require_once '../includes/Database.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use \Exception;

header('Content-Type: application/json');

try {
    if (!isset($_GET['presupuesto_id'])) {
        throw new Exception('ID de presupuesto no especificado');
    }

    $pdo = Database::getInstance()->getConnection();
    
    // Obtener items del presupuesto
    $stmt = $pdo->prepare("
        SELECT m.codigo as codigo_articulo, 
               m.descripcion,
               i.cantidad,
               i.precio_unitario,
               '' as lote_talle
        FROM items_presupuesto i
        JOIN materiales m ON m.id = i.material_id
        WHERE i.presupuesto_id = ?
        ORDER BY i.orden
    ");
    
    $stmt->execute([$_GET['presupuesto_id']]);
    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // Crear nuevo documento Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Establecer encabezados
    $sheet->setCellValue('A1', 'CODIGOARTICULO');
    $sheet->setCellValue('B1', 'DESCRIPCION');
    $sheet->setCellValue('C1', 'CANTIDAD');
    $sheet->setCellValue('D1', 'PRECIOUNITARIO');
    $sheet->setCellValue('E1', 'LOTE/TALLE');

    // Agregar datos
    $row = 2;
    foreach ($items as $item) {
        $sheet->setCellValue('A' . $row, $item['codigo_articulo']);
        $sheet->setCellValue('B' . $row, $item['descripcion']);
        $sheet->setCellValue('C' . $row, $item['cantidad']);
        $sheet->setCellValue('D' . $row, $item['precio_unitario']);
        $sheet->setCellValue('E' . $row, $item['lote_talle']);
        $row++;
    }

    // Autoajustar columnas
    foreach (range('A', 'E') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Crear directorio temporal si no existe
    $tempDir = '../temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }

    // Generar nombre de archivo
    $filename = 'presupuesto_' . $_GET['presupuesto_id'] . '_' . date('Y-m-d_His') . '.xlsx';
    $filepath = $tempDir . '/' . $filename;

    // Guardar archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save($filepath);

    // Registrar la exportación
    $stmt = $pdo->prepare("
        INSERT INTO exportaciones_flexxus (
            presupuesto_id,
            usuario_id,
            nombre_archivo
        ) VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $_GET['presupuesto_id'],
        1, // Usuario por defecto
        $filename
    ]);

    // Devolver URL del archivo
    echo json_encode([
        'success' => true,
        'url' => 'temp/' . $filename,
        'filename' => $filename
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
} 