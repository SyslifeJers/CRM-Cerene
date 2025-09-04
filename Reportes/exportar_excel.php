<?php
require_once '../DB/Conexion.php';
require '../libe/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$inicio = $_GET['inicio'] ?? '';
$fin = $_GET['fin'] ?? '';
$curso = $_GET['curso'] ?? '';

if (!$inicio || !$fin) {
    die('Rango de fechas inválido');
}

$database = new Database();
$conn = $database->getConnection();

$query = "SELECT ci.id_comprobante, ci.id_inscripcion, ci.numero_pago, ci.metodo_pago, ci.referencia_pago, ci.monto_pagado, ci.fecha_carga"
        . " FROM comprobantes_inscripcion ci"
        . " JOIN inscripciones i ON ci.id_inscripcion = i.id_inscripcion"
        . " WHERE ci.validado = 1 AND DATE(ci.fecha_carga) BETWEEN ? AND ?";
if ($curso !== '') {
    $query .= " AND i.id_curso = ?";
}
$stmt = $conn->prepare($query);
if ($curso !== '') {
    $curso = (int) $curso;
    $stmt->bind_param('ssi', $inicio, $fin, $curso);
} else {
    $stmt->bind_param('ss', $inicio, $fin);
}
$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$headers = ['ID Comprobante', 'ID Inscripción', 'Número de Pago', 'Método de Pago', 'Referencia', 'Monto Pagado', 'Fecha'];
$sheet->fromArray($headers, null, 'A1');

$row = 2;
while ($r = $result->fetch_assoc()) {
    $sheet->setCellValue("A{$row}", $r['id_comprobante']);
    $sheet->setCellValue("B{$row}", $r['id_inscripcion']);
    $sheet->setCellValue("C{$row}", $r['numero_pago']);
    $sheet->setCellValue("D{$row}", $r['metodo_pago']);
    $sheet->setCellValue("E{$row}", $r['referencia_pago']);
    $sheet->setCellValue("F{$row}", $r['monto_pagado']);
    $sheet->setCellValue("G{$row}", $r['fecha_carga']);
    $row++;
}

$database->closeConnection();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_pagos_' . $inicio . '_al_' . $fin . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
