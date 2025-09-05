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
    SELECT ci.id_comprobante, i.id_inscripcion, c.nombre_curso,
           CONCAT(p.nombre, ' ', p.apellido) AS participante,
           ci.numero_pago, ci.metodo_pago, ci.referencia_pago,
           ci.monto_pagado, ci.fecha_carga, ci.comprobante_path
    FROM comprobantes_inscripcion ci
    JOIN inscripciones i ON ci.id_inscripcion = i.id_inscripcion
    JOIN cursos c ON i.id_curso = c.id_curso
    JOIN participantes p ON i.id_participante = p.id_participante
    WHERE ci.validado = 1
      AND DATE(ci.fecha_carga) BETWEEN ? AND ?
      AND p.email IS NOT NULL AND p.email <> ''
      " . ($curso !== '' ? " AND i.id_curso = ?" : "") . "
    UNION ALL
    SELECT NULL AS id_comprobante, i.id_inscripcion, c.nombre_curso,
           CONCAT(p.nombre, ' ', p.apellido) AS participante,
           1 AS numero_pago, i.metodo_pago, NULL AS referencia_pago,
           i.monto_pagado, i.fecha_inscripcion AS fecha_carga, i.comprobante_path
    FROM inscripciones i
    JOIN cursos c ON i.id_curso = c.id_curso
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
$headers = ['ID Comprobante', 'ID Inscripción', 'Curso', 'Participante', 'Número de Pago', 'Método de Pago', 'Referencia', 'Monto Pagado', 'Fecha', 'Comprobante'];
$sheet->fromArray($headers, null, 'A1');

$row = 2;
while ($r = $result->fetch_assoc()) {
    $sheet->setCellValue("A{$row}", $r['id_comprobante']);
    $sheet->setCellValue("B{$row}", $r['id_inscripcion']);
    $sheet->setCellValue("C{$row}", $r['nombre_curso']);
    $sheet->setCellValue("D{$row}", $r['participante']);
    $sheet->setCellValue("E{$row}", $r['numero_pago']);
    $sheet->setCellValue("F{$row}", $r['metodo_pago']);
    $sheet->setCellValue("G{$row}", $r['referencia_pago']);
    $sheet->setCellValue("H{$row}", $r['monto_pagado']);
    $sheet->setCellValue("I{$row}", $r['fecha_carga']);
    if ($r['comprobante_path']) {
        $url = 'https://cursos.clinicacerene.com/comprobantes/' . $r['comprobante_path'];
        $sheet->setCellValue("J{$row}", 'Ver');
        $sheet->getCell("J{$row}")->getHyperlink()->setUrl($url);
        $sheet->getStyle("J{$row}")->getFont()->getColor()->setARGB('FF0000FF');
        $sheet->getStyle("J{$row}")->getFont()->setUnderline(true);
    }
    $row++;
}

$database->closeConnection();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="reporte_pagos_' . $inicio . '_al_' . $fin . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
