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

$query = "
    SELECT ci.id_comprobante, ci.id_inscripcion, ci.numero_pago, ci.metodo_pago, ci.referencia_pago, ci.monto_pagado, ci.fecha_carga
    FROM comprobantes_inscripcion ci
    JOIN inscripciones i ON ci.id_inscripcion = i.id_inscripcion
    JOIN participantes p ON i.id_participante = p.id_participante
    WHERE ci.validado = 1
      AND DATE(ci.fecha_carga) BETWEEN ? AND ?
      AND p.email IS NOT NULL AND p.email <> ''
      " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
    UNION ALL
    SELECT NULL AS id_comprobante, i.id_inscripcion, 1 AS numero_pago, i.metodo_pago, NULL AS referencia_pago, i.monto_pagado, i.fecha_inscripcion AS fecha_carga
    FROM inscripciones i
    JOIN participantes p ON i.id_participante = p.id_participante
    WHERE DATE(i.fecha_inscripcion) BETWEEN ? AND ?
      AND i.estado IN ('pago_validado','pagos programados','Revision de pago')
      AND p.email IS NOT NULL AND p.email <> ''
      AND NOT EXISTS (
        SELECT 1 FROM comprobantes_inscripcion ci2
        WHERE ci2.id_inscripcion = i.id_inscripcion AND ci2.validado = 1
      )
      " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
    ORDER BY fecha_carga";

$stmt = $conn->prepare($query);
if ($curso !== '') {
    $curso = (int) $curso;
    $stmt->bind_param('ssissi', $inicio, $fin, $curso, $inicio, $fin, $curso);
} else {
    $stmt->bind_param('ssss', $inicio, $fin, $inicio, $fin);
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
