<?php
require_once '../DB/Conexion.php';
require '../libe/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

try {
    $database = new Database();
    $id_curso = isset($_POST['id_curso']) ? intval($_POST['id_curso']) : (isset($_GET['id_curso']) ? intval($_GET['id_curso']) : 0);

    $conn = $database->getConnection();

    $query = $conn->prepare("
        SELECT
            ci.id_comprobante,
            i.id_inscripcion,
            p.titulo,
            p.nombre,
            p.apellido,
            p.email,
            p.telefono,
            ci.numero_pago,
            ci.metodo_pago,
            ci.referencia_pago,
            ci.monto_pagado,
            ci.fecha_carga,
            ci.validado,
            ci.comprobante_path
        FROM comprobantes_inscripcion ci
        JOIN inscripciones i ON ci.id_inscripcion = i.id_inscripcion
        JOIN participantes p ON i.id_participante = p.id_participante
        WHERE i.id_curso = ?
          AND p.email IS NOT NULL
          AND p.email <> ''
          AND ci.comprobante_path IS NOT NULL
          AND ci.comprobante_path <> ''

        UNION ALL

        SELECT
            NULL AS id_comprobante,
            i.id_inscripcion,
            p.titulo,
            p.nombre,
            p.apellido,
            p.email,
            p.telefono,
            1 AS numero_pago,
            i.metodo_pago,
            i.referencia_pago,
            i.monto_pagado,
            i.fecha_cambio_estado AS fecha_carga,
            CASE WHEN i.estado = 'pago_validado' THEN 1 ELSE 0 END AS validado,
            i.comprobante_path
        FROM inscripciones i
        JOIN participantes p ON i.id_participante = p.id_participante
        WHERE i.id_curso = ?
          AND p.email IS NOT NULL
          AND p.email <> ''
          AND i.comprobante_path IS NOT NULL
          AND i.comprobante_path <> ''
          AND NOT EXISTS (
              SELECT 1
              FROM comprobantes_inscripcion ci2
              WHERE ci2.id_inscripcion = i.id_inscripcion
                AND ci2.comprobante_path IS NOT NULL
                AND ci2.comprobante_path <> ''
          )
        ORDER BY id_inscripcion, numero_pago
    ");

    $query->bind_param('ii', $id_curso, $id_curso);
    $query->execute();
    $result = $query->get_result();

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    // Encabezados en el Excel
    $headers = ['ID Comprobante', 'ID Inscripción', 'Título', 'Nombre', 'Apellido', 'Correo', 'Celular', 'Número de Pago', 'Método', 'Referencia', 'Fecha', 'Estado Comprobante', 'Comprobante', 'Monto Pagado'];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $estadoComprobante = [
            0 => 'Pendiente',
            1 => 'Correcto',
            3 => 'Rechazado'
        ];

        $sheet->setCellValue("A{$rowNum}", $row['id_comprobante']);
        $sheet->setCellValue("B{$rowNum}", $row['id_inscripcion']);
        $sheet->setCellValue("C{$rowNum}", $row['titulo']);
        $sheet->setCellValue("D{$rowNum}", $row['nombre']);
        $sheet->setCellValue("E{$rowNum}", $row['apellido']);
        $sheet->setCellValue("F{$rowNum}", $row['email']);
        $sheet->setCellValue("G{$rowNum}", $row['telefono']);
        $sheet->setCellValue("H{$rowNum}", $row['numero_pago']);
        $sheet->setCellValue("I{$rowNum}", $row['metodo_pago']);
        $sheet->setCellValue("J{$rowNum}", $row['referencia_pago']);
        $sheet->setCellValue("K{$rowNum}", $row['fecha_carga']);
        $sheet->setCellValue("L{$rowNum}", $estadoComprobante[(int) $row['validado']] ?? 'Desconocido');

        if ($row['comprobante_path']) {
            $url = "https://cursos.clinicacerene.com/comprobantes/" . $row['comprobante_path'];
            $sheet->setCellValue("M{$rowNum}", 'Ver comprobante');
            $sheet->getCell("M{$rowNum}")->getHyperlink()->setUrl($url);
            $sheet->getStyle("M{$rowNum}")->getFont()->getColor()->setARGB('FF0000FF');
            $sheet->getStyle("M{$rowNum}")->getFont()->setUnderline(true);
        }
        $sheet->setCellValue("N{$rowNum}", $row['monto_pagado']);
        $sheet->getStyle("N{$rowNum}")->getNumberFormat()->setFormatCode('$#,##0.00');

        $rowNum++;
    }


    // Descargar Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="reporte_curso_' . $id_curso . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
