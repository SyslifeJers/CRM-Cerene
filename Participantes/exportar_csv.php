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
    i.id_inscripcion,
    p.titulo,
    p.nombre,
    p.apellido,
    p.email,
    p.telefono,
    p.documento AS comprobante_path,
       COALESCE(
                (
                    SELECT SUM(IFNULL(ci.monto_pagado, 0))
                    FROM comprobantes_inscripcion ci
                    WHERE ci.validado = 1
                      AND ci.id_inscripcion = i.id_inscripcion
                ),
                IFNULL(i.monto_pagado, 0)
            ) AS monto_validado
FROM inscripciones i
JOIN participantes p ON i.id_participante = p.id_participante
JOIN cursos c ON i.id_curso = c.id_curso
LEFT JOIN opciones_pago op ON i.IdOpcionPago = op.id_opcion
WHERE i.id_curso = ?
  AND (i.estado = 'pago_validado' OR i.estado = 'pagos programados' OR i.estado = 'Revision de pago')
  AND p.email IS NOT NULL
  AND p.email <> ''

    ");

    $query->bind_param('i', $id_curso);
    $query->execute();
    $result = $query->get_result();

    // Crear Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Encabezados
    // Encabezados en el Excel
    $headers = ['ID Inscripción', 'Título', 'Nombre', 'Apellido', 'Correo', 'Celular', 'Comprobante', 'Monto Validado'];
    $sheet->fromArray($headers, NULL, 'A1');

    $rowNum = 2;
    while ($row = $result->fetch_assoc()) {
        $sheet->setCellValue("A{$rowNum}", $row['id_inscripcion']);
        $sheet->setCellValue("B{$rowNum}", $row['titulo']);
        $sheet->setCellValue("C{$rowNum}", $row['nombre']);
        $sheet->setCellValue("D{$rowNum}", $row['apellido']);
        $sheet->setCellValue("E{$rowNum}", $row['email']);
        $sheet->setCellValue("F{$rowNum}", $row['telefono']);

        if ($row['comprobante_path']) {
            $url = "https://cursos.clinicacerene.com/documentos/" . $row['comprobante_path'];
            $sheet->setCellValue("G{$rowNum}", 'Ver comprobante');
            $sheet->getCell("G{$rowNum}")->getHyperlink()->setUrl($url);
            $sheet->getStyle("G{$rowNum}")->getFont()->getColor()->setARGB('FF0000FF');
            $sheet->getStyle("G{$rowNum}")->getFont()->setUnderline(true);
        } else {
            $sheet->setCellValue("F{$rowNum}", 'faltante');

            // Fondo amarillo a toda la fila si falta comprobante
            $sheet->getStyle("A{$rowNum}:F{$rowNum}")->applyFromArray([
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFFACD']
                ]
            ]);
        }
        $sheet->setCellValue("H{$rowNum}", $row['monto_validado']);
         // Formatear columna H como moneda
        $sheet->getStyle("H{$rowNum}")->getNumberFormat()->setFormatCode('$#,##0.00');

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
